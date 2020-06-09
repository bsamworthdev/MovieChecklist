<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFriendTagsTableAddForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('friend_tags', function (Blueprint $table) {
            $table->foreign('subject_user_id')->references('id')->on('users');
            $table->foreign('object_user_id')->references('id')->on('users');
            $table->foreign('tag_id')->references('id')->on('friend_tag_names');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('friend_tags', function (Blueprint $table) {
            $table->dropForeign('subject_user_id');
            $table->dropForeign('object_user_id');
            $table->dropForeign('tag_id');
        });
    }
}
