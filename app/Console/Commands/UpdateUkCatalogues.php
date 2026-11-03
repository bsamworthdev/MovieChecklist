<?php

namespace App\Console\Commands;

use App\Exceptions\CatalogueUpdateException;
use App\Services\TmdbUkAvailability;
use App\Services\UkCatalogueUpdate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateUkCatalogues extends Command
{
    protected $signature = 'catalogues:update {--service=* : Update only these service keys; defaults to all services}';

    protected $description = 'Update UK subscription availability for all configured streaming services';

    public function handle(UkCatalogueUpdate $updater, TmdbUkAvailability $api): int
    {
        $services = config('catalogues.services');
        $selected = array_unique($this->option('service') ?: array_keys($services));
        foreach ($selected as $key) {
            if (!isset($services[$key])) {
                $this->error('Unknown service: '.$key.'. Choose '.implode(', ', array_keys($services)).'.');
                return self::INVALID;
            }
        }
        if (!$api->configured()) {
            $this->error('Configure TMDB_API_TOKEN or TMDB_API_KEY before running catalogue updates.');
            return self::FAILURE;
        }
        $lock = Cache::store(config('catalogues.cache_store', 'file'))->lock('uk-catalogue-command', 10800);
        if (!$lock->get()) {
            $this->warn('A catalogue command is already running; this invocation was skipped.');
            return self::SUCCESS;
        }
        $failed = false;
        try {
            $this->info('UK catalogue update started: '.now('Europe/London')->toDateTimeString().' Europe/London');
            foreach ($selected as $key) {
                $label = $services[$key]['label'];
                try {
                    $state = $updater->start($key, 'catalogue-command');
                    while (!$state['complete']) {
                        $state = $updater->step($state['token'], 'catalogue-command', $state['processed']);
                        $this->line($label.': '.$state['processed'].'/'.$state['total'].' checked');
                        if (!$state['complete']) {
                            usleep(max(0, (int) config('catalogues.batch_delay_ms', 500)) * 1000);
                        }
                    }
                    $result = $state['result'];
                    $this->info($label.': '.$result['verified'].' checked, '.$result['available'].' available, '
                        .$result['added'].' newly available, '.$result['removed'].' removed, '.$result['skipped'].' skipped.');
                    Log::info('Scheduled UK catalogue updated', ['service' => $key, 'result' => $result]);
                } catch (Throwable $exception) {
                    $failed = true;
                    $message = $exception instanceof ValidationException
                        ? implode(' ', array_merge(...array_values($exception->errors()))) : $exception->getMessage();
                    $this->error($label.' failed: '.$message);
                    Log::error('Scheduled UK catalogue update failed', ['service' => $key, 'error' => $message]);
                    if (!$exception instanceof CatalogueUpdateException && !$exception instanceof ValidationException) {
                        report($exception);
                    }
                    // Each provider uses its own transaction; keep processing the other services.
                }
            }
        } finally {
            $lock->release();
        }
        $this->line('UK catalogue update finished: '.now('Europe/London')->toDateTimeString().' Europe/London');
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
