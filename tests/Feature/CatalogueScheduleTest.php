<?php

namespace Tests\Feature;

use App\Exceptions\CatalogueUpdateException;
use App\Services\TmdbUkAvailability;
use App\Services\UkCatalogueUpdate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogueScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['catalogues.cache_store' => 'array', 'catalogues.batch_delay_ms' => 0]);
        Cache::store('array')->flush();
    }

    private function configured(): void
    {
        $this->mock(TmdbUkAvailability::class)->shouldReceive('configured')->once()->andReturn(true);
    }

    private function successful($mock, string $key): void
    {
        $mock->shouldReceive('start')->once()->with($key, 'catalogue-command')->andReturn([
            'token' => $key, 'complete' => false, 'processed' => 0, 'total' => 1,
        ]);
        $mock->shouldReceive('step')->once()->with($key, 'catalogue-command', 0)->andReturn([
            'token' => $key, 'complete' => true, 'processed' => 1, 'total' => 1,
            'result' => ['verified' => 1, 'available' => 1, 'added' => 1, 'removed' => 0, 'skipped' => 0],
        ]);
    }

    public function test_command_runs_all_five_services_without_a_browser(): void
    {
        $this->configured();
        $mock = $this->mock(UkCatalogueUpdate::class);
        foreach (array_keys(config('catalogues.services')) as $key) $this->successful($mock, $key);
        $this->artisan('catalogues:update')->assertExitCode(0);
    }

    public function test_one_failed_service_does_not_stop_other_services_and_returns_failure(): void
    {
        $this->configured();
        $mock = $this->mock(UkCatalogueUpdate::class);
        $mock->shouldReceive('start')->once()->with('prime', 'catalogue-command')->andThrow(new CatalogueUpdateException('TMDB is unavailable'));
        foreach (array_diff(array_keys(config('catalogues.services')), ['prime']) as $key) $this->successful($mock, $key);
        $this->artisan('catalogues:update')->expectsOutput('Prime Video failed: TMDB is unavailable')->assertExitCode(1);
    }

    public function test_only_selected_service_is_run(): void
    {
        $this->configured();
        $mock = $this->mock(UkCatalogueUpdate::class);
        $this->successful($mock, 'hbo_max');
        $this->artisan('catalogues:update', ['--service' => ['hbo_max']])->assertExitCode(0);
    }

    public function test_an_overlapping_command_is_skipped(): void
    {
        $this->configured();
        $this->mock(UkCatalogueUpdate::class)->shouldNotReceive('start');
        $lock = Cache::store('array')->lock('uk-catalogue-command', 60);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('catalogues:update')->expectsOutput('A catalogue command is already running; this invocation was skipped.')->assertExitCode(0);
        } finally {
            $lock->release();
        }
    }

    public function test_missing_credentials_fail_without_starting_updates(): void
    {
        $this->mock(TmdbUkAvailability::class)->shouldReceive('configured')->once()->andReturn(false);
        $this->mock(UkCatalogueUpdate::class)->shouldNotReceive('start');
        $this->artisan('catalogues:update')->assertExitCode(1);
    }

    public function test_schedule_uses_london_midnight_during_gmt_and_bst(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'catalogues:update'));
        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertSame('Europe/London', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(storage_path('logs/catalogue-updates.log'), $event->output);
        try {
            Carbon::setTestNow(Carbon::parse('2026-11-04 00:00:00', 'UTC'));
            $this->assertTrue($event->isDue($this->app));
            Carbon::setTestNow(Carbon::parse('2026-07-01 23:00:00', 'UTC'));
            $this->assertTrue($event->isDue($this->app));
            Carbon::setTestNow(Carbon::parse('2026-07-01 00:00:00', 'UTC'));
            $this->assertFalse($event->isDue($this->app));
        } finally {
            Carbon::setTestNow();
        }
    }
}
