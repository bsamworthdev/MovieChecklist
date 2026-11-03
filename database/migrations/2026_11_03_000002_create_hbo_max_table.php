<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHboMaxTable extends Migration
{
    public function up()
    {
        Schema::create('hbo_max', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movie_id')->unique();
            $table->tinyInteger('on_hbo_max')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hbo_max');
    }
}
