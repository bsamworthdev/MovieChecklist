<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Exceptions\CatalogueUpdateException;
use Throwable;

class TmdbUkAvailability
{
    public function configured(): bool
    {
        return filled(config('services.tmdb.token')) || filled(config('services.tmdb.api_key'));
    }

    public function providerIds(array $service, string $media = 'movie'): array
    {
        $this->validateMedia($media);
        if (!$this->configured()) {
            throw new CatalogueUpdateException('Add your TMDB API Read Access Token as TMDB_API_TOKEN in the server configuration before updating catalogues.');
        }
        $cache = Cache::store(config('catalogues.cache_store', 'file'));
        $providers = $cache->get('tmdb:uk:providers:v1'.($media === 'tv' ? ':tv' : ''));
        if ($providers === null) {
            try {
                $response = Http::withHeaders($this->headers())->connectTimeout(5)->timeout(15)
                    ->get('https://api.themoviedb.org/3/watch/providers/'.$media, $this->query(['watch_region' => 'GB', 'language' => 'en-GB']));
            } catch (Throwable $exception) {
                throw new CatalogueUpdateException('Could not connect to TMDB. Try the catalogue update again.');
            }
            $json = $this->json($response);
            if (!isset($json['results']) || !is_array($json['results']) || !$json['results']) {
                throw new CatalogueUpdateException('TMDB returned an incomplete UK provider list. No availability was changed.');
            }
            $providers = $json['results'];
            $cache->put('tmdb:uk:providers:v1'.($media === 'tv' ? ':tv' : ''), $providers, 3600);
        }
        $names = array_map(fn ($name) => $this->name($name), ($media === 'tv' ? ($service['tv_provider_names'] ?? $service['provider_names']) : $service['provider_names']));
        $ids = [];
        foreach ($providers as $provider) {
            if (isset($provider['provider_name'], $provider['provider_id'])
                && in_array($this->name($provider['provider_name']), $names, true)
                && $this->validId($provider['provider_id'])) {
                $ids[] = (int) $provider['provider_id'];
            }
        }
        if (!$ids) {
            throw new CatalogueUpdateException('TMDB did not list '.$service['label'].' as a UK '.$media.' provider. No availability was changed.');
        }
        return array_values(array_unique($ids));
    }

    public function check(array $movies, array $providerIds, array $offerTypes = ['flatrate'], string $media = 'movie'): array
    {
        $this->validateMedia($media);
        $identityPrefix = $media === 'tv' ? 'tmdb:tv:imdb:' : 'tmdb:imdb:';
        $offerPrefix = $media === 'tv' ? 'tmdb:tv:gb:offers:v2:' : 'tmdb:gb:offers:v2:';
        $resultKey = $media === 'tv' ? 'tv_results' : 'movie_results';
        $cache = Cache::store(config('catalogues.cache_store', 'file'));
        $identities = [];
        $pending = [];
        foreach ($movies as $movie) {
            $code = $movie['imdb_id'] ?? '';
            if (!is_string($code) || !preg_match('/\Att\d{5,12}\z/', $code)) {
                continue;
            }
            $identity = $cache->get($identityPrefix.$code);
            if ($identity === null) {
                $pending[$code] = ['path' => '/find/'.$code, 'query' => ['external_source' => 'imdb_id']];
            } else {
                $identities[$code] = $identity;
            }
        }
        foreach ($this->pool($pending) as $code => $json) {
            if (!isset($json[$resultKey]) || !is_array($json[$resultKey])) {
                throw new CatalogueUpdateException('TMDB returned an incomplete IMDb lookup. No availability was changed.');
            }
            $results = $json[$resultKey];
            $id = count($results) === 1 && $this->validId($results[0]['id'] ?? null) ? (int) $results[0]['id'] : null;
            $identity = ['id' => $id];
            $identities[$code] = $identity;
            $cache->put($identityPrefix.$code, $identity, $id ? 2592000 : 3600);
        }
        $offers = [];
        $pending = [];
        foreach ($identities as $identity) {
            if (!$identity['id']) {
                continue;
            }
            $id = $identity['id'];
            $offer = $cache->get($offerPrefix.$id);
            if ($offer === null) {
                $pending[$id] = ['path' => '/'.$media.'/'.$id.'/watch/providers', 'query' => []];
            } else {
                $offers[$id] = $offer;
            }
        }
        foreach ($this->pool($pending) as $id => $json) {
            if (!isset($json['results']) || !is_array($json['results'])) {
                throw new CatalogueUpdateException('TMDB returned incomplete availability data. No availability was changed.');
            }
            $regions = $json['results'];
            foreach ($regions as $country => $region) {
                if (!is_string($country) || !preg_match('/\A[A-Z]{2}\z/', $country) || !is_array($region)) {
                    throw new CatalogueUpdateException('TMDB returned invalid regional availability. No availability was changed.');
                }
            }
            $groups = [];
            foreach (['flatrate', 'free', 'ads'] as $type) {
                if (isset($regions['GB']) && array_key_exists($type, $regions['GB']) && !is_array($regions['GB'][$type])) {
                    throw new CatalogueUpdateException('TMDB returned invalid UK streaming offers. No availability was changed.');
                }
                $ids = [];
                foreach ($regions['GB'][$type] ?? [] as $provider) {
                    if (!$this->validId($provider['provider_id'] ?? null)) {
                        throw new CatalogueUpdateException('TMDB returned an invalid streaming provider. No availability was changed.');
                    }
                    $ids[] = (int) $provider['provider_id'];
                }
                $groups[$type] = array_values(array_unique($ids));
            }
            // Cache all supported access types, but never rentals/purchases, for sharing across services.
            $offers[$id] = $groups;
            $cache->put($offerPrefix.$id, $groups, 900);
        }
        $metadata = [];
        if ($media === 'tv') {
            $pending = [];
            $metadataIds = [];
            foreach ($movies as $show) {
                $code = $show['imdb_id'] ?? '';
                $id = config('tv_metadata.sources.'.$code) ?? ($identities[$code]['id'] ?? null);
                if (!$id) continue;
                $metadataIds[$show['id']] = $id;
                $details = $cache->get('tmdb:tv:metadata:v1:'.$id);
                if ($details === null) $pending[$id] = ['path' => '/tv/'.$id, 'query' => []];
                else $metadata[$id] = $details;
            }
            foreach ($this->pool($pending) as $id => $details) {
                $values = [];
                if (is_string($details['original_language'] ?? null) && preg_match('/^[a-z]{2,3}$/', $details['original_language'])) {
                    $values['language'] = $details['original_language'];
                }
                if (isset($details['genres']) && is_array($details['genres'])) {
                    $genres = array_filter(array_column($details['genres'], 'name'), fn ($name) => is_string($name) && $name !== '');
                    if ($genres) $values['genre'] = mb_substr(implode(', ', $genres), 0, 512);
                }
                $metadata[$id] = $values;
                $cache->put('tmdb:tv:metadata:v1:'.$id, $values, 86400);
            }
        }
        $outcomes = [];
        foreach ($movies as $movie) {
            $code = $movie['imdb_id'] ?? '';
            $identity = $identities[$code] ?? null;
            if (!$identity || !$identity['id']) {
                $outcomes[$movie['id']] = ['skipped' => $code ? 'IMDb code could not be resolved to one TMDB '.$media.'.' : 'No IMDb code.'];
            } else {
                $allowed = [];
                foreach ($offerTypes as $type) {
                    if (!in_array($type, ['flatrate', 'free', 'ads'], true)) {
                        throw new CatalogueUpdateException('Unsupported streaming access type. No availability was changed.');
                    }
                    $allowed = array_merge($allowed, $offers[$identity['id']][$type] ?? []);
                }
                $outcomes[$movie['id']] = ['available' => count(array_intersect($providerIds, $allowed)) > 0];

            }
            if ($media === 'tv') {
                $outcomes[$movie['id']]['metadata'] = config('tv_metadata.corrections.'.$code)
                    ?? ($metadata[$metadataIds[$movie['id']] ?? 0] ?? []);
            }
        }
        return $outcomes;
    }

    private function validateMedia(string $media): void
    {
        if (!in_array($media, ['movie', 'tv'], true)) throw new CatalogueUpdateException('Unknown catalogue type.');
    }

    private function pool(array $requests): array
    {
        if (!$requests) {
            return [];
        }
        try {
            $responses = Http::pool(function (Pool $pool) use ($requests) {
                $pending = [];
                foreach ($requests as $key => $request) {
                    $pending[] = $pool->as((string) $key)->withHeaders($this->headers())->connectTimeout(5)->timeout(15)
                        ->get('https://api.themoviedb.org/3'.$request['path'], $this->query($request['query']));
                }
                return $pending;
            });
        } catch (Throwable $exception) {
            throw new CatalogueUpdateException('Could not connect to TMDB. Retry the update; existing availability has not been changed.');
        }
        $data = [];
        foreach ($responses as $key => $response) {
            $data[$key] = $this->json($response);
        }
        return $data;
    }

    private function json($response): array
    {
        if (!$response instanceof Response) {
            throw new CatalogueUpdateException('A TMDB request timed out. Retry the update.');
        }
        if (in_array($response->status(), [401, 403], true)) {
            throw new CatalogueUpdateException('TMDB rejected the API credentials. Check TMDB_API_TOKEN or TMDB_API_KEY on the server.');
        }
        if ($response->status() === 429) {
            throw new CatalogueUpdateException('TMDB’s request limit was reached. Wait a moment, then retry the update.');
        }
        if (!$response->successful() || !is_array($response->json())) {
            throw new CatalogueUpdateException('TMDB returned an unsuccessful or invalid response. Retry the update.');
        }
        return $response->json();
    }

    private function headers(): array
    {
        $headers = ['Accept' => 'application/json'];
        if (filled(config('services.tmdb.token'))) {
            $headers['Authorization'] = 'Bearer '.config('services.tmdb.token');
        }
        return $headers;
    }

    private function query(array $query): array
    {
        if (!filled(config('services.tmdb.token'))) {
            $query['api_key'] = config('services.tmdb.api_key');
        }
        return $query;
    }

    private function name(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function validId($id): bool
    {
        return filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0;
    }
}
