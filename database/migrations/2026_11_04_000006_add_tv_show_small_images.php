<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tv_shows', fn (Blueprint $table) => $table->string('image_url_small', 2048)->nullable());
    }

    public function down(): void
    {
        Schema::table('tv_shows', fn (Blueprint $table) => $table->dropColumn('image_url_small'));
    }
};
