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

    public function providerIds(array $service): array
    {
        if (!$this->configured()) {
            throw new CatalogueUpdateException('Add your TMDB API Read Access Token as TMDB_API_TOKEN in the server configuration before updating catalogues.');
        }
        $cache = Cache::store(config('catalogues.cache_store', 'file'));
        $providers = $cache->get('tmdb:uk:providers:v1');
        if ($providers === null) {
            try {
                $response = Http::withHeaders($this->headers())->connectTimeout(5)->timeout(15)
                    ->get('https://api.themoviedb.org/3/watch/providers/movie', $this->query(['watch_region' => 'GB', 'language' => 'en-GB']));
            } catch (Throwable $exception) {
                throw new CatalogueUpdateException('Could not connect to TMDB. Try the catalogue update again.');
            }
            $json = $this->json($response);
            if (!isset($json['results']) || !is_array($json['results']) || !$json['results']) {
                throw new CatalogueUpdateException('TMDB returned an incomplete UK provider list. No availability was changed.');
            }
            $providers = $json['results'];
            $cache->put('tmdb:uk:providers:v1', $providers, 3600);
        }
        $names = array_map(fn ($name) => $this->name($name), $service['provider_names']);
        $ids = [];
        foreach ($providers as $provider) {
            if (isset($provider['provider_name'], $provider['provider_id'])
                && in_array($this->name($provider['provider_name']), $names, true)
                && $this->validId($provider['provider_id'])) {
                $ids[] = (int) $provider['provider_id'];
            }
        }
        if (!$ids) {
            throw new CatalogueUpdateException('TMDB did not list '.$service['label'].' as a UK movie provider. No availability was changed.');
        }
        return array_values(array_unique($ids));
    }

    public function check(array $movies, array $providerIds): array
    {
        $cache = Cache::store(config('catalogues.cache_store', 'file'));
        $identities = [];
        $pending = [];
        foreach ($movies as $movie) {
            $code = $movie['imdb_id'] ?? '';
            if (!is_string($code) || !preg_match('/\Att\d{5,12}\z/', $code)) {
                continue;
            }
            $identity = $cache->get('tmdb:imdb:'.$code);
            if ($identity === null) {
                $pending[$code] = ['path' => '/find/'.$code, 'query' => ['external_source' => 'imdb_id']];
            } else {
                $identities[$code] = $identity;
            }
        }
        foreach ($this->pool($pending) as $code => $json) {
            if (!isset($json['movie_results']) || !is_array($json['movie_results'])) {
                throw new CatalogueUpdateException('TMDB returned an incomplete IMDb lookup. No availability was changed.');
            }
            $results = $json['movie_results'];
            $id = count($results) === 1 && $this->validId($results[0]['id'] ?? null) ? (int) $results[0]['id'] : null;
            $identity = ['id' => $id];
            $identities[$code] = $identity;
            $cache->put('tmdb:imdb:'.$code, $identity, $id ? 2592000 : 3600);
        }
        $offers = [];
        $pending = [];
        foreach ($identities as $identity) {
            if (!$identity['id']) {
                continue;
            }
            $id = $identity['id'];
            $offer = $cache->get('tmdb:gb:subscriptions:'.$id);
            if ($offer === null) {
                $pending[$id] = ['path' => '/movie/'.$id.'/watch/providers', 'query' => []];
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
            if (isset($regions['GB']) && array_key_exists('flatrate', $regions['GB']) && !is_array($regions['GB']['flatrate'])) {
                throw new CatalogueUpdateException('TMDB returned invalid UK subscription offers. No availability was changed.');
            }
            $subscriptions = $regions['GB']['flatrate'] ?? [];
            if (!is_array($subscriptions)) {
                throw new CatalogueUpdateException('TMDB returned invalid UK subscription offers. No availability was changed.');
            }
            $ids = [];
            foreach ($subscriptions as $provider) {
                if (!$this->validId($provider['provider_id'] ?? null)) {
                    throw new CatalogueUpdateException('TMDB returned an invalid subscription provider. No availability was changed.');
                }
                $ids[] = (int) $provider['provider_id'];
            }
            // Share recent UK results across the service buttons; rentals, purchases and other countries are excluded.
            $offers[$id] = array_values(array_unique($ids));
            $cache->put('tmdb:gb:subscriptions:'.$id, $offers[$id], 900);
        }
        $outcomes = [];
        foreach ($movies as $movie) {
            $code = $movie['imdb_id'] ?? '';
            $identity = $identities[$code] ?? null;
            if (!$identity || !$identity['id']) {
                $outcomes[$movie['id']] = ['skipped' => $code ? 'IMDb code could not be resolved to one TMDB movie.' : 'No IMDb code.'];
            } else {
                $outcomes[$movie['id']] = ['available' => count(array_intersect($providerIds, $offers[$identity['id']])) > 0];
            }
        }
        return $outcomes;
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
