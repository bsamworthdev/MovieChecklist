<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdditionalStreamingTables extends Migration
{
    private const SERVICES = ['apple_tv', 'paramount_plus', 'bbc_iplayer', 'itvx', 'channel4'];

    public function up()
    {
        foreach (self::SERVICES as $service) {
            Schema::create($service, function (Blueprint $table) use ($service) {
                $table->id();
                $table->unsignedBigInteger('movie_id')->unique();
                $table->tinyInteger('on_'.$service)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        foreach (array_reverse(self::SERVICES) as $service) {
            Schema::dropIfExists($service);
        }
    }
}
