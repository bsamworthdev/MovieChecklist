<?php

namespace Tests\Feature;

use App\Mail\FriendInvitationMailable;
use App\Mail\FriendRequestAcceptMailable;
use App\Mail\FriendRequestMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FriendshipFlowTest extends TestCase
{
    use \Tests\Concerns\CreatesAccountTables;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->createAccountTables();
        foreach (['friend_requests', 'friendships', 'friend_invitations'] as $name) {
            Schema::create($name, function ($table) use ($name) {
                $table->id(); $table->timestamps();
                if ($name === 'friend_requests') {$table->integer('sender_user_id'); $table->integer('recipient_user_id'); $table->string('token')->unique(); $table->string('status');}
                elseif ($name === 'friendships') {$table->integer('person_A_user_id'); $table->integer('person_B_user_id');}
                else {$table->integer('user_id'); $table->string('email');}
            });
        }
        Mail::fake();
    }

    public function test_repeated_request_cancels_old_token_and_only_new_token_can_accept_once(): void
    {
        $sender = $this->account(1); $this->account(2);
        $this->actingAs($sender);
        $this->post('/createFriendRequest', ['email' => 'user2@example.com'])->assertRedirect();
        $first = DB::table('friend_requests')->first();
        Mail::assertSent(FriendRequestMailable::class, fn ($mail) => $mail->hasTo('user2@example.com') && (string) $mail->token === $first->token);
        $this->post('/createFriendRequest', ['email' => 'user2@example.com'])->assertRedirect();
        $second = DB::table('friend_requests')->orderByDesc('id')->first();
        $this->assertNotSame($first->token, $second->token);
        $this->get('/acceptFriendRequest/'.$first->token)->assertOk()->assertViewIs('friend-request-void');
        $this->assertSame(0, DB::table('friendships')->count());
        $this->get('/acceptFriendRequest/'.$second->token)->assertOk()->assertViewIs('friend-request-accept');
        $this->get('/acceptFriendRequest/'.$second->token)->assertOk()->assertViewIs('friend-request-void');
        $this->assertSame(1, DB::table('friendships')->count());
        $this->assertDatabaseHas('friend_requests', ['id' => $second->id, 'status' => 'active']);
        Mail::assertSent(FriendRequestAcceptMailable::class, 1);
    }

    public function test_existing_friendship_is_recognised_in_either_direction(): void
    {
        $sender = $this->account(1); $this->account(2);
        DB::table('friendships')->insert(['person_A_user_id' => 2, 'person_B_user_id' => 1]);
        $this->actingAs($sender)->post('/createFriendRequest', ['email' => 'user2@example.com'])->assertRedirect()->assertSessionHas('message', 'Already friends with this person');
        $this->assertSame(0, DB::table('friend_requests')->count());
        Mail::assertNothingSent();
    }

    public function test_unknown_email_sends_an_invitation_without_creating_a_friendship(): void
    {
        $this->actingAs($this->account(1))->post('/createFriendRequest', ['email' => 'new@example.com'])->assertRedirect();
        $this->assertDatabaseHas('friend_invitations', ['user_id' => 1, 'email' => 'new@example.com']);
        $this->assertSame(0, DB::table('friendships')->count());
        Mail::assertSent(FriendInvitationMailable::class, fn ($mail) => $mail->hasTo('new@example.com'));
        $this->get('/acceptFriendRequest/unknown-token')->assertOk()->assertViewIs('friend-request-not-found');
    }

    public function test_recipient_can_remove_friendship_without_touching_anyone_elses(): void
    {
        $this->account(1); $recipient = $this->account(2); $this->account(3);
        DB::table('friendships')->insert([
            ['person_A_user_id' => 1, 'person_B_user_id' => 2],
            ['person_A_user_id' => 1, 'person_B_user_id' => 3],
        ]);
        $this->actingAs($recipient)->post('/deletefriend', ['id' => 1])->assertRedirect();
        $this->assertSame(1, DB::table('friendships')->count());
        $this->assertDatabaseHas('friendships', ['person_A_user_id' => 1, 'person_B_user_id' => 3]);
        $this->post('/deletefriend', ['id' => 3])->assertNotFound();
        $this->assertSame(1, DB::table('friendships')->count());
        $this->actingAs(\App\User::find(1))->post('/deletefriend', ['id' => 3])->assertRedirect();
        $this->assertSame(0, DB::table('friendships')->count());
    }
}
