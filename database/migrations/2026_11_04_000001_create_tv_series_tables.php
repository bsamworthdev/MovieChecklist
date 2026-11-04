<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tv_series', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id', 20)->unique();
            $table->string('name', 512);
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('top250_rank')->nullable()->index();
            $table->decimal('rating', 3, 1)->nullable();
            $table->text('image_url')->nullable();
            $table->string('genre', 512)->nullable();
            $table->string('language', 100)->nullable();
            $table->timestamps();
        });
        Schema::create('tv_series_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tv_series_id')->constrained('tv_series')->cascadeOnDelete();
            $table->string('owner', 128);
            $table->boolean('watched')->default(false);
            $table->boolean('favourite')->default(false);
            $table->boolean('watchlist')->default(false);
            $table->timestamps();
            $table->unique(['owner', 'tv_series_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_series_progress');
        Schema::dropIfExists('tv_series');
    }
};
