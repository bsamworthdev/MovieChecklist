<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\StreamingAvailabilityFilter;
use Illuminate\Validation\Rule;

class TvSeriesController extends Controller
{
    public function index(Request $request)
    {
        return view('tv-series', ['ready' => Schema::hasTable('tv_shows')]);
    }

    public function catalogue(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:200',
            'status' => 'nullable|in:all,unwatched,watched,favourites,watchlist',
            'page' => 'nullable|integer|min:1',
            'genre' => 'nullable|string|max:100',
            'decade' => 'nullable|integer|min:1880|max:2150',
            'english' => 'nullable|boolean',
            'popular' => 'nullable|boolean',
            'unwatched' => 'nullable|boolean',
            'favourites' => 'nullable|boolean',
            'services' => 'nullable|array|max:10',
            'services.*' => ['string', Rule::in(array_keys(config('catalogues.services')))],
            'random' => 'nullable|boolean',
        ]);
        $query = app(\App\Services\TvShowTracking::class)->query($request);
        $watchlistCount = (clone $query)->whereNotNull('watchlist.movie_id')->count();
        foreach (config('catalogues.services') as $service) {
            $table = $service['table'];
            $column = $service['column'];
            $availability = DB::table($table)->where('movie_or_tv', 'tv')->select('movie_id')
                ->selectRaw('MAX('.$column.') AS '.$column)->groupBy('movie_id');
            $query->leftJoinSub($availability, $table, $table.'.movie_id', '=', 'series.id')
                ->selectRaw('COALESCE('.$table.'.'.$column.', 0) AS '.$column);
        }
        StreamingAvailabilityFilter::apply($query, array_fill_keys($filters['services'] ?? [], 1));
        if (filled($filters['genre'] ?? null)) {
            $query->where('series.genre', 'like', '%'.$filters['genre'].'%');
        }
        if (filled($filters['decade'] ?? null)) {
            $query->whereBetween('series.year', [(int) $filters['decade'], (int) $filters['decade'] + 9]);
        }
        if ($request->boolean('english')) $query->whereIn(DB::raw('LOWER(series.language)'), ['english', 'en']);
        if ($request->boolean('popular')) $query->where('series.votes', '>', 250000);
        if ($request->boolean('unwatched')) $query->whereRaw('COALESCE(progress.watched, 0) = 0');
        if ($request->boolean('favourites')) $query->where('progress.favourite', true);
        if (filled($filters['search'] ?? null)) {
            $query->where('series.name', 'like', '%'.$filters['search'].'%');
        }
        switch ($filters['status'] ?? 'all') {
            case 'unwatched': $query->whereRaw('COALESCE(progress.watched, 0) = 0'); break;
            case 'watched': $query->where('progress.watched', true); break;
            case 'favourites': $query->where('progress.favourite', true); break;
            case 'watchlist': $query->whereNotNull('watchlist.movie_id'); break;
        }
        if ($request->boolean('random')) {
            $show = $query->whereRaw('COALESCE(progress.watched, 0) = 0')->inRandomOrder()->first();
            if ($show) $show->image_url_small_extended = app(\App\Services\CatalogueThumbnails::class)->url($show->image_url_small ?? null);
            return response()->json(['show' => $show]);
        }
        $watchedCount = (clone $query)->where('progress.watched', 1)->count();
        $result = $query->orderByRaw('series.top250_rank IS NULL')->orderBy('series.top250_rank')->orderBy('series.name')->paginate(100)->toArray();
        $metadata = DB::table('tv_shows')->get(['genre', 'language', 'year']);
        foreach ($result['data'] as &$show) {
            $show = (array) $show;
            $show['image_url_small_extended'] = app(\App\Services\CatalogueThumbnails::class)->url($show['image_url_small'] ?? null);
        }
        unset($show);
        $result['summary'] = ['watched' => $watchedCount, 'total' => $result['total'], 'watchlist' => $watchlistCount];
        $result['filters'] = [
            'genres' => $metadata->flatMap(fn ($row) => preg_split('/[,|]/', $row->genre ?? ''))->map(fn ($genre) => trim($genre))->filter()->unique()->sort()->values(),
            'decades' => $metadata->pluck('year')->filter()->map(fn ($year) => (int) (floor($year / 10) * 10))->unique()->sortDesc()->values(),
            'has_languages' => $metadata->contains(fn ($row) => filled($row->language)),
        ];
        $result['services'] = collect(config('catalogues.services'))->map(fn ($service, $key) => [
            'key' => $key, 'label' => $service['label'], 'icon' => '/images/'.$service['icon'],
            'column' => $service['column'], 'table' => $service['table'],
        ])->sortBy(fn ($service) => array_search($service['key'], ['netflix', 'prime', 'now', 'disney', 'hbo_max', 'apple_tv', 'paramount_plus', 'bbc_iplayer', 'itvx', 'channel4']))->values();
        return response()->json($result);
    }

    public function watchlist(Request $request)
    {
        return response()->json(app(\App\Services\TvShowTracking::class)->query($request)
            ->whereNotNull('watchlist.movie_id')->orderBy('series.name')->get());
    }

    public function progress(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:tv_shows,id',
            'field' => 'required|in:watched,favourite,watchlist',
            'value' => 'required|boolean',
        ]);
        app(\App\Services\TvShowTracking::class)->save($request, $data);
        return response()->json(['saved' => true]);
    }

    public function streaming(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'editor'], true), 403);
        $data = $request->validate([
            'id' => 'required|integer|exists:tv_shows,id',
            'service' => ['required', Rule::in(array_keys(config('catalogues.services')))],
            'available' => 'required|boolean',
        ]);
        $service = config('catalogues.services.'.$data['service']);
        DB::table($service['table'])->updateOrInsert(['movie_or_tv' => 'tv', 'movie_id' => $data['id']], [
            $service['column'] => (int) $data['available'], 'updated_at' => now(),
        ]);
        return response()->json(['saved' => true]);
    }

}
