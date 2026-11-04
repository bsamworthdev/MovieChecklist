<?php

namespace Tests\Feature;

use App\Services\StreamingAvailabilityFilter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StreamingAvailabilityFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('genre');
        });
        foreach (config('catalogues.services') as $service) {
            Schema::create($service['table'], function (Blueprint $table) use ($service) {
                $table->integer('movie_id')->unique();
                $table->integer($service['column']);
            });
        }
        foreach (range(1, 6) as $id) {
            DB::table('movies')->insert(['id' => $id, 'genre' => $id === 6 ? 'Comedy' : 'Drama']);
        }
        DB::table('netflix')->insert([['movie_id' => 1, 'on_netflix' => 1], ['movie_id' => 3, 'on_netflix' => 1]]);
        DB::table('amazon')->insert([['movie_id' => 2, 'on_amazon' => 1], ['movie_id' => 3, 'on_amazon' => 1], ['movie_id' => 6, 'on_amazon' => 1]]);
        DB::table('bbc_iplayer')->insert(['movie_id' => 5, 'on_bbc_iplayer' => 1]);
    }

    private function query()
    {
        $query = DB::table('movies');
        foreach (config('catalogues.services') as $service) {
            $query->leftJoin($service['table'], $service['table'].'.movie_id', '=', 'movies.id');
        }
        return $query->orderBy('movies.id');
    }

    public function test_multiple_streaming_services_match_either_without_duplicating_shared_movies(): void
    {
        $ids = StreamingAvailabilityFilter::apply($this->query(), ['netflix' => 1, 'prime' => '1'])->pluck('movies.id')->all();
        $this->assertSame([1, 2, 3, 6], $ids);
    }

    public function test_no_selected_services_keeps_movies_with_no_availability_records(): void
    {
        $this->assertSame(range(1, 6), StreamingAvailabilityFilter::apply($this->query(), [])->pluck('movies.id')->all());
        $unchecked = array_fill_keys(array_keys(config('catalogues.services')), 0);
        $this->assertSame(range(1, 6), StreamingAvailabilityFilter::apply($this->query(), $unchecked)->pluck('movies.id')->all());
    }

    public function test_service_or_group_respects_other_filters_before_and_after_it(): void
    {
        $selected = ['netflix' => 1, 'prime' => 1];
        $before = $this->query()->where('movies.genre', 'Drama');
        $this->assertSame([1, 2, 3], StreamingAvailabilityFilter::apply($before, $selected)->pluck('movies.id')->all());
        $after = StreamingAvailabilityFilter::apply($this->query(), $selected)->where('movies.genre', 'Drama');
        $this->assertSame([1, 2, 3], $after->pluck('movies.id')->all());
    }

    public function test_single_service_and_broadcaster_combinations_are_supported(): void
    {
        $this->assertSame([1, 3], StreamingAvailabilityFilter::apply($this->query(), ['netflix' => 1])->pluck('movies.id')->all());
        $this->assertSame([1, 3, 5], StreamingAvailabilityFilter::apply($this->query(), ['netflix' => 1, 'bbc_iplayer' => 1])->pluck('movies.id')->all());
    }
}
