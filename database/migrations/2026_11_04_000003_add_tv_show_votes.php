<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tv_series', fn (Blueprint $table) => $table->unsignedBigInteger('votes')->nullable());
    }

    public function down(): void
    {
        Schema::table('tv_series', fn (Blueprint $table) => $table->dropColumn('votes'));
    }
};
