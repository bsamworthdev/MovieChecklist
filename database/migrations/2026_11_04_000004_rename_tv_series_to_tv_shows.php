<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('tv_series', 'tv_shows');
    }

    public function down(): void
    {
        Schema::rename('tv_shows', 'tv_series');
    }
};
