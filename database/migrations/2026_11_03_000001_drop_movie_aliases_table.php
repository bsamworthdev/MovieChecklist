<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropMovieAliasesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('movie_aliases');
    }

    public function down()
    {
        // Recreate the schema; aliases removed by up() are not recoverable here.
        Schema::create('movie_aliases', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('imdb_id', 16)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 255);
            $table->timestamps();
            $table->index('imdb_id');
            $table->unique(['imdb_id', 'name']);
        });
    }
}
