<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UkCatalogueUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'catalogues.cache_store' => 'array', 'catalogues.batch_size' => 1,
            'services.tmdb.token' => 'test-token-not-for-the-browser', 'services.tmdb.api_key' => null,
        ]);
        DB::purge('sqlite');
        Cache::store('array')->flush();
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id')->nullable();
            $table->string('name');
        });
        foreach (config('catalogues.services') as $service) {
            Schema::create($service['table'], function (Blueprint $table) use ($service) {
                $table->id();
                $table->integer('movie_id');
                if (!in_array($service['table'], ['amazon', 'netflix', 'disney_plus', 'nowtv'], true)) $table->unique('movie_id');
                $table->tinyInteger($service['column']);
                $table->timestamps();
            });
        }
    }

    private function admin(int $id = 99): void
    {
        $this->actingAs(new GenericUser(['id' => $id, 'role' => 'admin']));
    }

    private function movie(?string $code = 'tt1000001'): int
    {
        return DB::table('movies')->insertGetId(['imdb_id' => $code, 'name' => 'Movie '.$code]);
    }

    private function flag(string $service, int $movie, int $flag): void
    {
        $config = config('catalogues.services.'.$service);
        DB::table($config['table'])->insert(['movie_id' => $movie, $config['column'] => $flag, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function fakeApi(array $offers = [], array $overrides = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(function ($request) use ($offers, $overrides) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (isset($overrides[$path])) {
                return $overrides[$path];
            }
            if ($path === '/3/watch/providers/movie') {
                return Http::response(['results' => [
                    ['provider_id' => 9, 'provider_name' => 'Amazon Prime Video'],
                    ['provider_id' => 2100, 'provider_name' => 'Amazon Prime Video with Ads'],
                    ['provider_id' => 8, 'provider_name' => 'Netflix'],
                    ['provider_id' => 1796, 'provider_name' => 'Netflix basic with Ads'],
                    ['provider_id' => 337, 'provider_name' => 'Disney Plus'],
                    ['provider_id' => 39, 'provider_name' => 'Now TV Cinema'],
                    ['provider_id' => 1899, 'provider_name' => 'HBO Max'],
                    ['provider_id' => 1825, 'provider_name' => 'HBO Max Amazon Channel'],
                    ['provider_id' => 350, 'provider_name' => 'Apple TV'],
                    ['provider_id' => 2, 'provider_name' => 'Apple TV Store'],
                    ['provider_id' => 531, 'provider_name' => 'Paramount Plus'],
                    ['provider_id' => 2303, 'provider_name' => 'Paramount Plus Premium'],
                    ['provider_id' => 2304, 'provider_name' => 'Paramount Plus Basic with Ads'],
                    ['provider_id' => 582, 'provider_name' => 'Paramount+ Amazon Channel'],
                    ['provider_id' => 38, 'provider_name' => 'BBC iPlayer'],
                    ['provider_id' => 41, 'provider_name' => 'ITVX'],
                    ['provider_id' => 2300, 'provider_name' => 'ITVX Premium'],
                    ['provider_id' => 103, 'provider_name' => 'Channel 4'],
                    ['provider_id' => 2311, 'provider_name' => 'Channel 4 Plus'],
                ]]);
            }
            if (preg_match('~/find/tt(\d+)$~', $path, $matches)) {
                return Http::response(['movie_results' => [['id' => (int) $matches[1] - 1000000]]]);
            }
            if (preg_match('~/movie/(\d+)/watch/providers$~', $path, $matches)) {
                return Http::response(['results' => $offers[(int) $matches[1]] ?? []]);
            }
            $this->fail('Unexpected API path: '.$path);
        });
    }

    private function start(string $service = 'prime'): array
    {
        return $this->postJson(route('admin.catalogues.start'), ['service' => $service])->assertOk()->json();
    }

    private function step(array $state)
    {
        return $this->postJson(route('admin.catalogues.step'), ['token' => $state['token'], 'processed' => $state['processed']]);
    }

    private function finish(string $service = 'prime'): array
    {
        $state = $this->start($service);
        while (!$state['complete']) {
            $state = $this->step($state)->assertOk()->json();
        }
        return $state;
    }

    public function test_routes_require_admin_and_valid_service(): void
    {
        Http::fake();
        $this->postJson(route('admin.catalogues.start'), ['service' => 'prime'])->assertUnauthorized();
        $this->actingAs(new GenericUser(['id' => 3, 'role' => 'user']));
        $this->postJson(route('admin.catalogues.start'), ['service' => 'prime'])->assertForbidden();
        $this->postJson(route('admin.catalogues.step'))->assertForbidden();
        $this->postJson('/updatenetflixstatuses')->assertForbidden();
        $this->postJson('/updateamazonstatuses')->assertForbidden();
        $this->admin();
        $this->postJson(route('admin.catalogues.start'), ['service' => 'movies; DROP TABLE movies'])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_credentials_are_required_without_changes_or_http_requests(): void
    {
        $this->admin();
        $movie = $this->movie();
        $this->flag('prime', $movie, 1);
        config(['services.tmdb.token' => null]);
        Http::fake();
        $this->postJson(route('admin.catalogues.start'), ['service' => 'prime'])->assertUnprocessable()->assertJsonValidationErrors('catalogue');
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
        Http::assertNothingSent();
    }

    public function test_batches_commit_at_the_end_and_only_include_uk_subscription_offers(): void
    {
        $this->admin();
        $first = $this->movie();
        $second = $this->movie('tt1000002');
        $this->flag('prime', $second, 1);
        $this->flag('netflix', $second, 1);
        $this->fakeApi([
            1 => ['GB' => ['flatrate' => [['provider_id' => 2100]]]],
            2 => ['GB' => ['rent' => [['provider_id' => 9]], 'buy' => [['provider_id' => 9]], 'flatrate' => [['provider_id' => 777]]],
                'US' => ['flatrate' => [['provider_id' => 9]]]],
        ]);
        $start = $this->start();
        $this->assertLessThan(1024, strlen(base64_encode(serialize(session()->all()))));
        $middle = $this->step($start)->assertOk()->json();
        $this->assertFalse($middle['complete']);
        $this->assertSame(1, DB::table('amazon')->where('movie_id', $second)->value('on_amazon'));
        $this->assertSame(1, DB::table('amazon')->count());
        $done = $this->step($middle)->assertOk()->json();
        $this->assertTrue($done['complete']);
        $this->assertSame(1, DB::table('amazon')->where('movie_id', $first)->value('on_amazon'));
        $this->assertSame(0, DB::table('amazon')->where('movie_id', $second)->value('on_amazon'));
        $this->assertSame(1, DB::table('netflix')->where('movie_id', $second)->value('on_netflix'));
        $this->assertSame(1, $done['result']['added']);
        $this->assertSame(1, $done['result']['removed']);
        $this->assertSame(1, $done['result']['available']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/find/tt1000001')
            && $request['external_source'] === 'imdb_id' && $request->hasHeader('Authorization', 'Bearer test-token-not-for-the-browser'));
    }

    public function test_each_service_uses_its_own_table_and_provider_and_shared_recent_checks(): void
    {
        $this->admin();
        $this->movie();
        $this->fakeApi([1 => ['GB' => ['flatrate' => [
            ['provider_id' => 9], ['provider_id' => 1796], ['provider_id' => 337], ['provider_id' => 39], ['provider_id' => 1899],
            ['provider_id' => 350], ['provider_id' => 531], ['provider_id' => 38], ['provider_id' => 41], ['provider_id' => 103],
        ]]]]);
        foreach (config('catalogues.services') as $key => $config) {
            $result = $this->finish($key);
            $this->assertSame(1, $result['result']['available']);
            $this->assertSame(1, DB::table($config['table'])->value($config['column']));
        }
        Http::assertSentCount(3); // Provider list, IMDb lookup, UK offers; reused across services.
    }

    public function test_failed_batch_never_clears_availability_and_can_be_retried(): void
    {
        $this->admin();
        $this->movie();
        $second = $this->movie('tt1000002');
        $this->flag('prime', $second, 1);
        $this->fakeApi([], ['/3/movie/2/watch/providers' => Http::response(['error' => true], 503)]);
        $middle = $this->step($this->start())->assertOk()->json();
        $this->step($middle)->assertUnprocessable()->assertJsonValidationErrors('catalogue');
        $this->assertSame(1, DB::table('amazon')->count());
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
        $this->fakeApi();
        $this->step($middle)->assertOk()->assertJsonPath('complete', true);
        $this->assertSame(0, DB::table('amazon')->where('movie_id', $second)->value('on_amazon'));
    }

    public function test_missing_and_unresolved_imdb_ids_preserve_existing_availability(): void
    {
        $this->admin();
        $missing = $this->movie(null);
        $unknown = $this->movie('tt1000002');
        $this->flag('prime', $missing, 1);
        $this->flag('prime', $unknown, 1);
        $this->fakeApi([], ['/3/find/tt1000002' => Http::response(['movie_results' => [], 'tv_results' => [['id' => 22]]])]);
        $result = $this->finish();
        $this->assertSame(2, $result['result']['skipped']);
        $this->assertSame(0, $result['result']['verified']);
        $this->assertCount(2, $result['skipped']);
        $this->assertSame(2, DB::table('amazon')->where('on_amazon', 1)->count());
    }

    public function test_duplicate_rows_are_collapsed_and_repeated_requests_are_idempotent(): void
    {
        $this->admin();
        $movie = $this->movie();
        $this->flag('prime', $movie, 0);
        $this->flag('prime', $movie, 1);
        $this->fakeApi([1 => ['GB' => ['flatrate' => [['provider_id' => 9]]]]]);
        $start = $this->start();
        $done = $this->step($start)->assertOk()->json();
        $this->assertSame(1, $done['result']['duplicates_removed']);
        $this->assertSame(1, DB::table('amazon')->count());
        $this->step($start)->assertOk()->assertExactJson($done);
        Http::assertSentCount(3);
    }

    public function test_changed_movie_list_or_foreign_session_cannot_apply_snapshot(): void
    {
        $this->admin();
        $movie = $this->movie();
        $this->flag('prime', $movie, 1);
        $this->fakeApi();
        $start = $this->start();
        $this->admin(100);
        $this->step($start)->assertUnprocessable();
        $this->admin();
        DB::table('movies')->where('id', $movie)->update(['imdb_id' => 'tt1000002']);
        $this->step($start)->assertUnprocessable();
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
    }

    public function test_database_failure_rolls_back_all_availability_changes(): void
    {
        $this->admin();
        $first = $this->movie();
        $second = $this->movie('tt1000002');
        $this->flag('prime', $first, 1);
        $this->flag('prime', $second, 1);
        DB::unprepared("CREATE TRIGGER fail_availability_update BEFORE UPDATE ON amazon WHEN OLD.movie_id = $second BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->fakeApi();
        $middle = $this->step($this->start())->assertOk()->json();
        $this->step($middle)->assertServerError();
        $this->assertSame(2, DB::table('amazon')->where('on_amazon', 1)->count());
    }

    public function test_missing_provider_or_malformed_api_data_never_becomes_unavailable(): void
    {
        $this->admin();
        $movie = $this->movie();
        $this->flag('prime', $movie, 1);
        $this->fakeApi([], ['/3/watch/providers/movie' => Http::response(['results' => [['provider_id' => 8, 'provider_name' => 'Netflix']]])]);
        $this->postJson(route('admin.catalogues.start'), ['service' => 'prime'])->assertUnprocessable();
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
        Cache::store('array')->flush();
        $this->fakeApi([], ['/3/movie/1/watch/providers' => Http::response(['unexpected' => []])]);
        $this->step($this->start())->assertUnprocessable();
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
    }

    public function test_v3_api_key_authentication_is_supported_without_exposing_it(): void
    {
        $this->admin();
        $this->movie();
        config(['services.tmdb.token' => null, 'services.tmdb.api_key' => 'test-v3-secret']);
        $this->fakeApi();
        $result = $this->finish();
        $this->assertSame(1, $result['result']['verified']);
        $this->assertStringNotContainsString('test-v3-secret', json_encode($result));
        Http::assertSent(fn ($request) => $request['api_key'] === 'test-v3-secret' && !$request->hasHeader('Authorization'));
    }

    public function test_rate_limited_or_null_region_responses_preserve_availability(): void
    {
        $this->admin();
        $movie = $this->movie();
        $this->flag('prime', $movie, 1);
        $this->fakeApi([], ['/3/movie/1/watch/providers' => Http::response([], 429)]);
        $start = $this->start();
        $this->step($start)->assertUnprocessable()->assertJsonValidationErrors('catalogue');
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
        $this->fakeApi([], ['/3/movie/1/watch/providers' => Http::response(['results' => ['GB' => null]])]);
        $this->step($start)->assertUnprocessable();
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
        $this->fakeApi([], ['/3/movie/1/watch/providers' => Http::response(['results' => ['GB' => ['flatrate' => null]]])]);
        $this->step($start)->assertUnprocessable();
        $this->assertSame(1, DB::table('amazon')->value('on_amazon'));
    }

    public function test_hbo_max_checks_uk_subscription_without_including_amazon_channel(): void
    {
        $this->admin();
        $standalone = $this->movie();
        $channelOnly = $this->movie('tt1000002');
        $this->fakeApi([
            1 => ['GB' => ['flatrate' => [['provider_id' => 1899]]]],
            2 => ['GB' => ['flatrate' => [['provider_id' => 1825]]]],
        ]);
        $result = $this->finish('hbo_max');
        $this->assertSame(1, $result['result']['available']);
        $this->assertSame(1, DB::table('hbo_max')->where('movie_id', $standalone)->value('on_hbo_max'));
        $this->assertSame(0, DB::table('hbo_max')->where('movie_id', $channelOnly)->value('on_hbo_max'));
        $this->assertSame(0, DB::table('nowtv')->count());
    }

    public function test_hbo_max_manual_controls_validate_permissions_and_do_not_duplicate_rows(): void
    {
        $this->admin();
        $movie = $this->movie();
        $payload = ['platform' => 'hbo_max', 'movie_id' => $movie, 'status' => 1];
        $this->postJson('/setMovieStreamStatus', $payload)->assertOk();
        $this->postJson('/setMovieStreamStatus', array_replace($payload, ['status' => 0]))->assertOk();
        $this->assertSame(1, DB::table('hbo_max')->count());
        $this->assertSame(0, DB::table('hbo_max')->value('on_hbo_max'));
        $this->postJson('/setMovieStreamStatus', array_replace($payload, ['status' => 7]))->assertUnprocessable();
        $this->actingAs(new GenericUser(['id' => 8, 'role' => 'user']));
        $this->postJson('/setMovieStreamStatus', $payload)->assertForbidden();
    }

    public function test_hbo_max_filter_is_included_in_movie_query_and_preserves_pagination_contract(): void
    {
        $this->admin();
        $queries = DB::connection()->pretend(function () {
            app(\App\Http\Controllers\MovieController::class)->getMovies(
                'all', 'all', 0, 0, 0, 0, '', 0, 0, 0, 0, '', 100, 1
            );
        });
        $query = $queries[0]['query'];
        $this->assertStringContainsString('left join "hbo_max"', $query);
        $this->assertStringContainsString('"hbo_max"."on_hbo_max" = \'1\'', $query);
        $this->assertStringContainsString('offset 100', $query);
        $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/getMoreMovies/100/all/all/0/0/0/0/null/0/0/0/0/null/1', 'POST'));
        $this->assertSame('1', $route->parameter('hbo_max_only'));
        $this->assertSame('null', $route->parameter('unwatched_by_friends'));
    }

    public function test_broadcasters_match_free_ads_and_premium_but_exclude_rentals_and_other_countries(): void
    {
        $this->admin();
        $free = $this->movie();
        $rental = $this->movie('tt1000002');
        $premium = $this->movie('tt1000003');
        $this->fakeApi([
            1 => ['GB' => ['free' => [['provider_id' => 38], ['provider_id' => 103]], 'ads' => [['provider_id' => 41]]]],
            2 => ['GB' => ['rent' => [['provider_id' => 38], ['provider_id' => 41], ['provider_id' => 103]]],
                'US' => ['free' => [['provider_id' => 38], ['provider_id' => 41], ['provider_id' => 103]]]],
            3 => ['GB' => ['flatrate' => [['provider_id' => 2300], ['provider_id' => 2311]]]],
        ]);
        foreach (['bbc_iplayer', 'itvx', 'channel4'] as $service) {
            $this->finish($service);
            $this->assertSame(1, DB::table($service)->where('movie_id', $free)->value('on_'.$service));
            $this->assertSame(0, DB::table($service)->where('movie_id', $rental)->value('on_'.$service));
            $this->assertSame($service === 'bbc_iplayer' ? 0 : 1, DB::table($service)->where('movie_id', $premium)->value('on_'.$service));
        }
        Http::assertSentCount(7);
    }

    public function test_apple_subscription_excludes_store_and_paramount_excludes_amazon_addon(): void
    {
        $this->admin();
        $included = $this->movie();
        $excluded = $this->movie('tt1000002');
        $this->fakeApi([
            1 => ['GB' => ['flatrate' => [['provider_id' => 350], ['provider_id' => 2304]]]],
            2 => ['GB' => ['flatrate' => [['provider_id' => 2], ['provider_id' => 582]], 'buy' => [['provider_id' => 350], ['provider_id' => 531]]]],
        ]);
        foreach (['apple_tv', 'paramount_plus'] as $service) {
            $this->finish($service);
            $this->assertSame(1, DB::table($service)->where('movie_id', $included)->value('on_'.$service));
            $this->assertSame(0, DB::table($service)->where('movie_id', $excluded)->value('on_'.$service));
        }
    }

    public function test_cached_free_offers_do_not_mark_paid_services_as_subscriptions(): void
    {
        $this->admin();
        $this->movie();
        $this->fakeApi([1 => ['GB' => ['free' => [['provider_id' => 8], ['provider_id' => 38]]]]]);
        $this->assertSame(0, $this->finish('netflix')['result']['available']);
        $this->assertSame(1, $this->finish('bbc_iplayer')['result']['available']);
        Http::assertSentCount(3);
    }

    public function test_new_services_have_working_manual_controls_and_filters(): void
    {
        $this->admin();
        $movie = $this->movie();
        $newServices = ['apple_tv', 'paramount_plus', 'bbc_iplayer', 'itvx', 'channel4'];
        foreach ($newServices as $index => $service) {
            $payload = ['platform' => $service, 'movie_id' => $movie, 'status' => 1];
            $this->postJson('/setMovieStreamStatus', $payload)->assertOk();
            $this->postJson('/setMovieStreamStatus', $payload)->assertOk();
            $this->assertSame(1, DB::table($service)->count());
            $this->assertSame(1, DB::table($service)->value('on_'.$service));
            $filters = array_fill(0, 5, 0);
            $filters[$index] = 1;
            $queries = DB::connection()->pretend(function () use ($filters) {
                app(\App\Http\Controllers\MovieController::class)->getMovies('all', 'all', 0, 0, 0, 0, '', 0, 0, 0, 0, '', 100, 0, ...$filters);
            });
            $this->assertStringContainsString('"'.$service.'"."on_'.$service.'" = \'1\'', $queries[0]['query']);
        }
    }
}
