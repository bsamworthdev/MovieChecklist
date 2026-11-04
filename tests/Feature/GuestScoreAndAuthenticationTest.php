<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuestScoreAndAuthenticationTest extends TestCase
{
    use \Tests\Concerns\CreatesAccountTables;
    use \Tests\Concerns\CreatesLegacyTrackingTables;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->createAccountTables();
        $this->createLegacyTrackingTables();
        (require database_path('migrations/2026_11_04_000001_create_tv_series_tables.php'))->up();
        (require database_path('migrations/2026_11_04_000004_rename_tv_series_to_tv_shows.php'))->up();
        (require database_path('migrations/2026_11_04_000005_move_tv_tracking_to_shared_tables.php'))->up();
        Schema::create('movies', function ($table) {$table->id();});
        DB::table('movies')->insert(['id' => 1]);
    }

    private function registration(): array
    {
        return ['username' => 'new-user', 'name' => 'New User', 'email' => 'new@example.com', 'password' => 'correct-password', 'password_confirmation' => 'correct-password'];
    }

    public function test_guest_watched_favourites_and_watchlist_are_isolated_from_other_sessions_and_tv(): void
    {
        $this->withSession(['test' => true]);
        $session = app('session')->getId();
        $this->withCredentials()->withCookie(config('session.cookie'), $session);
        DB::table('movie_user_non_auth')->insert([
            ['session_id' => 'another-session', 'movie_or_tv' => 'movie', 'movie_id' => 1, 'favourite' => 1, 'watched' => 1],
            ['session_id' => $session, 'movie_or_tv' => 'tv', 'movie_id' => 1, 'favourite' => 1, 'watched' => 1],
        ]);
        foreach (range(1, 3) as $repeat) $this->postJson('/saveMovieUserNonAuth', ['movie_id' => 1, 'watched' => true])->assertOk();
        $this->assertSame(3, DB::table('movie_user_non_auth')->count());
        $this->postJson('/setMovieAsFavouriteNonAuth', ['movie_id' => 1, 'favourite' => true])->assertOk();
        $this->postJson('/saveMovieUserNonAuth', ['movie_id' => 1, 'watched' => true])->assertOk();
        $this->assertDatabaseHas('movie_user_non_auth', ['session_id' => $session, 'movie_or_tv' => 'movie', 'favourite' => 1]);
        foreach (range(1, 3) as $repeat) $this->postJson('/toggleMovieInWatchListNonAuth', ['movie_id' => 1, 'onWatchList' => true])->assertOk();
        $this->assertSame(1, DB::table('watch_list_non_auth')->count());
        $this->postJson('/saveMovieUserNonAuth', ['movie_id' => 1, 'watched' => false])->assertOk();
        $this->assertSame(2, DB::table('movie_user_non_auth')->count());
        $this->assertDatabaseHas('movie_user_non_auth', ['session_id' => 'another-session', 'favourite' => 1]);
        $this->assertDatabaseHas('movie_user_non_auth', ['session_id' => $session, 'movie_or_tv' => 'tv', 'watched' => 1]);
        $this->postJson('/toggleMovieInWatchListNonAuth', ['movie_id' => 1, 'onWatchList' => false])->assertOk();
        $this->assertSame(0, DB::table('watch_list_non_auth')->count());
    }

    public function test_save_my_score_registers_and_copies_both_catalogues_without_another_guests_history(): void
    {
        $this->withSession(['test' => true]);
        $session = app('session')->getId();
        foreach (['movie', 'tv'] as $media) {
            DB::table('movie_user_non_auth')->insert(['session_id' => $session, 'movie_or_tv' => $media, 'movie_id' => 1, 'watched' => $media === 'movie', 'favourite' => 1]);
            DB::table('watch_list_non_auth')->insert(['session_id' => $session, 'movie_or_tv' => $media, 'movie_id' => 1]);
        }
        DB::table('movie_user_non_auth')->insert(['session_id' => 'someone-else', 'movie_or_tv' => 'movie', 'movie_id' => 2]);
        $this->withCookie(config('session.cookie'), $session)->post('/register', $this->registration() + ['role' => 'admin'])->assertRedirect('/home');
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertTrue(Hash::check('correct-password', $user->password));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user']);
        $this->assertSame(2, DB::table('movie_user')->count());
        $this->assertSame(2, DB::table('watch_list')->count());
        $this->assertDatabaseHas('movie_user', ['user_id' => $user->id, 'movie_or_tv' => 'movie', 'movie_id' => 1, 'watched' => 1, 'favourite' => 1]);
        $this->assertDatabaseHas('movie_user', ['user_id' => $user->id, 'movie_or_tv' => 'tv', 'movie_id' => 1, 'watched' => 0, 'favourite' => 1]);
        $this->assertDatabaseMissing('movie_user', ['movie_id' => 2]);
    }

    public function test_invalid_registration_does_not_create_account_or_copy_history(): void
    {
        $this->postJson('/register', array_replace($this->registration(), ['password_confirmation' => 'wrong']))->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('movie_user')->count());
        $this->account(1);
        $this->postJson('/register', array_replace($this->registration(), ['username' => 'user1']))->assertUnprocessable()->assertJsonValidationErrors('username');
        $this->assertSame(1, DB::table('users')->count());
    }

    public function test_login_uses_username_rejects_wrong_password_and_logout_ends_authentication(): void
    {
        $this->account(1);
        $this->postJson('/login', ['username' => 'user1', 'password' => 'incorrect'])->assertUnprocessable();
        $this->assertGuest();
        $this->post('/login', ['username' => 'user1', 'password' => 'correct-password'])->assertRedirect('/home');
        $this->assertAuthenticatedAs(\App\User::find(1));
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_history_transfer_failure_rolls_back_the_account_and_all_copied_rows(): void
    {
        $this->withSession(['test' => true]);
        $session = app('session')->getId();
        DB::table('watch_list_non_auth')->insert(['session_id' => $session, 'movie_id' => 1]);
        DB::table('movie_user_non_auth')->insert(['session_id' => $session, 'movie_id' => 1]);
        DB::statement("CREATE TRIGGER fail_history BEFORE INSERT ON movie_user BEGIN SELECT RAISE(ABORT, 'simulated history write failure'); END");
        $this->withCookie(config('session.cookie'), $session)->post('/register', $this->registration())->assertStatus(500);
        $this->assertGuest();
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('watch_list')->count());
        $this->assertSame(1, DB::table('movie_user_non_auth')->count());
    }

    public function test_member_favourite_and_watchlist_edits_only_affect_own_movie_rows(): void
    {
        $user = $this->account(1); $this->account(2);
        DB::table('movie_user')->insert([
            ['user_id' => 1, 'movie_id' => 1, 'movie_or_tv' => 'movie', 'favourite' => 0],
            ['user_id' => 2, 'movie_id' => 1, 'movie_or_tv' => 'movie', 'favourite' => 1],
            ['user_id' => 1, 'movie_id' => 1, 'movie_or_tv' => 'tv', 'favourite' => 1],
        ]);
        DB::table('watch_list')->insert([
            ['user_id' => 1, 'movie_id' => 1, 'movie_or_tv' => 'movie'],
            ['user_id' => 2, 'movie_id' => 1, 'movie_or_tv' => 'movie'],
            ['user_id' => 1, 'movie_id' => 1, 'movie_or_tv' => 'tv'],
        ]);
        $this->actingAs($user)->postJson('/setMovieAsFavourite', ['movie_id' => 1, 'favourite' => true, 'user_id' => 2])->assertOk();
        $this->assertDatabaseHas('movie_user', ['user_id' => 1, 'movie_or_tv' => 'movie', 'favourite' => 1]);
        $this->postJson('/setMovieAsFavourite', ['movie_id' => 1, 'favourite' => false])->assertOk();
        foreach (range(1, 3) as $repeat) $this->postJson('/toggleMovieInWatchList', ['movie_id' => 1, 'onWatchList' => false, 'user_id' => 2])->assertOk();
        $this->assertDatabaseHas('movie_user', ['user_id' => 2, 'movie_or_tv' => 'movie', 'favourite' => 1]);
        $this->assertDatabaseHas('movie_user', ['user_id' => 1, 'movie_or_tv' => 'tv', 'favourite' => 1]);
        $this->assertDatabaseMissing('watch_list', ['user_id' => 1, 'movie_or_tv' => 'movie']);
        $this->assertDatabaseHas('watch_list', ['user_id' => 2, 'movie_or_tv' => 'movie']);
        $this->assertDatabaseHas('watch_list', ['user_id' => 1, 'movie_or_tv' => 'tv']);
    }

    public function test_bad_guest_writes_and_unauthenticated_member_writes_leave_history_unchanged(): void
    {
        foreach ([['/saveMovieUserNonAuth', 'watched'], ['/setMovieAsFavouriteNonAuth', 'favourite'], ['/toggleMovieInWatchListNonAuth', 'onWatchList']] as [$route, $flag]) {
            $this->postJson($route, ['movie_id' => 999, $flag => true])->assertUnprocessable();
            $this->postJson($route, ['movie_id' => 1, $flag => 'not-a-boolean'])->assertUnprocessable();
        }
        foreach (['/saveMovieUser', '/setMovieAsFavourite', '/toggleMovieInWatchList'] as $route) $this->postJson($route, ['movie_id' => 1])->assertUnauthorized();
        $this->assertSame(0, DB::table('movie_user_non_auth')->count());
        $this->assertSame(0, DB::table('watch_list_non_auth')->count());
        $this->assertSame(0, DB::table('movie_user')->count());
        $this->assertSame(0, DB::table('watch_list')->count());
    }
}
