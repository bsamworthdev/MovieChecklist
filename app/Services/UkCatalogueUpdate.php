<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Exceptions\CatalogueUpdateException;

class UkCatalogueUpdate
{
    public function __construct(private TmdbUkAvailability $api)
    {
    }

    public function start(string $service, $userId): array
    {
        $settings = config('catalogues.services.'.$service);
        if (!$settings) {
            throw new CatalogueUpdateException('Unknown streaming service.');
        }
        $providerIds = $this->api->providerIds($settings);
        $movies = DB::table('movies')->orderBy('id')->get(['id', 'name', 'imdb_id'])->map(fn ($movie) => (array) $movie)->all();
        if (!$movies) {
            throw new CatalogueUpdateException('There are no movies in the database to check.');
        }
        $state = [
            'token' => (string) Str::uuid(), 'user_id' => $userId, 'service' => $service,
            'provider_ids' => $providerIds, 'movies' => $movies, 'fingerprint' => $this->fingerprint($movies),
            'processed' => 0, 'statuses' => [], 'skipped' => [], 'expires_at' => time() + 7200,
        ];
        if (!$this->cache()->put($this->key($state['token']), $state, 7200)) {
            throw new CatalogueUpdateException('The catalogue update could not be saved. Check cache storage.');
        }
        return $this->progress($state);
    }

    public function step(string $token, $userId, int $processed): array
    {
        $lock = $this->cache()->lock('uk-catalogue-lock:'.$token, 90);
        if (!$lock->get()) {
            throw new CatalogueUpdateException('This update is already processing a batch. Wait a moment and retry.');
        }
        try {
            $state = $this->cache()->get($this->key($token));
            if (!$state || $state['user_id'] !== $userId || $state['expires_at'] <= time()) {
                throw ValidationException::withMessages(['token' => 'This catalogue update has expired. Start the update again.']);
            }
            if (isset($state['result']) || $processed !== $state['processed']) {
                return $this->progress($state);
            }
            $batch = array_slice($state['movies'], $state['processed'], max(1, min(20, (int) config('catalogues.batch_size', 10))));
            $outcomes = $this->api->check($batch, $state['provider_ids']);
            foreach ($batch as $movie) {
                $outcome = $outcomes[$movie['id']];
                if (isset($outcome['skipped'])) {
                    $state['skipped'][] = ['name' => $movie['name'], 'imdb_id' => $movie['imdb_id'], 'reason' => $outcome['skipped']];
                } else {
                    $state['statuses'][$movie['id']] = $outcome['available'] ? 1 : 0;
                }
            }
            $state['processed'] += count($batch);
            if ($state['processed'] === count($state['movies'])) {
                $state['result'] = $this->persist($state);
            }
            $this->cache()->put($this->key($token), $state, max(1, $state['expires_at'] - time()));
            return $this->progress($state);
        } finally {
            $lock->release();
        }
    }

    private function persist(array $state): array
    {
        return DB::transaction(function () use ($state) {
            $current = DB::table('movies')->orderBy('id')->lockForUpdate()->get(['id', 'imdb_id'])->map(fn ($movie) => (array) $movie)->all();
            if ($this->fingerprint($current) !== $state['fingerprint']) {
                throw ValidationException::withMessages(['token' => 'The movie list changed during this update. Start again to include the current movies. No availability was changed.']);
            }
            $settings = config('catalogues.services.'.$state['service']);
            $table = $settings['table'];
            $column = $settings['column'];
            $rows = DB::table($table)->whereIn('movie_id', array_keys($state['statuses']))->orderBy('id')->lockForUpdate()->get()->groupBy('movie_id');
            $result = [
                'verified' => count($state['statuses']), 'available' => array_sum($state['statuses']),
                'added' => 0, 'removed' => 0, 'skipped' => count($state['skipped']), 'duplicates_removed' => 0,
            ];
            $now = now();
            foreach ($state['statuses'] as $movieId => $available) {
                $existing = $rows->get($movieId, collect());
                $before = (int) ($existing->max($column) ?? 0);
                $result['added'] += !$before && $available ? 1 : 0;
                $result['removed'] += $before && !$available ? 1 : 0;
                if ($existing->isEmpty()) {
                    DB::table($table)->insert(['movie_id' => $movieId, $column => $available, 'created_at' => $now, 'updated_at' => $now]);
                } else {
                    DB::table($table)->where('id', $existing->first()->id)->update([$column => $available, 'updated_at' => $now]);
                    $duplicates = $existing->skip(1)->pluck('id')->all();
                    if ($duplicates) {
                        DB::table($table)->whereIn('id', $duplicates)->delete();
                        $result['duplicates_removed'] += count($duplicates);
                    }
                }
            }
            return $result;
        });
    }

    private function fingerprint(array $movies): string
    {
        return hash('sha256', json_encode(array_map(fn ($movie) => [(int) $movie['id'], $movie['imdb_id']], $movies)));
    }

    private function progress(array $state): array
    {
        return [
            'token' => $state['token'], 'service' => $state['service'], 'processed' => $state['processed'],
            'total' => count($state['movies']), 'complete' => isset($state['result']),
            'result' => $state['result'] ?? null, 'skipped' => isset($state['result']) ? $state['skipped'] : [],
        ];
    }

    private function key(string $token): string
    {
        return 'uk-catalogue-run:'.$token;
    }

    private function cache()
    {
        return Cache::store(config('catalogues.cache_store', 'file'));
    }
}
