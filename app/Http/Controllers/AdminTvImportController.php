<?php

namespace App\Http\Controllers;

use App\Services\ImdbTop250Parser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdminTvImportController extends Controller
{
    public function thumbnails()
    {
        return response()->json(app(\App\Services\CatalogueThumbnails::class)->generate('tv', 3));
    }

    public function preview(Request $request, ImdbTop250Parser $parser)
    {
        $data = $request->validate(['html' => 'required|string|max:5000000']);
        try {
            $rows = $parser->parse($data['html']);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['html' => str_replace('movies', 'TV shows', $exception->getMessage())]);
        }
        $token = (string) Str::uuid();
        $cache = Cache::store(config('cache.movie_import_store', 'file'));
        if (!$cache->put('tv-import:'.$token, ['owner' => $request->user()->getAuthIdentifier(), 'rows' => $rows], 1800)) {
            throw ValidationException::withMessages(['html' => 'Could not save the preview. Please try again.']);
        }
        $existing = DB::table('tv_shows')->whereIn('imdb_id', array_column($rows, 'imdb_id'))->count();
        return response()->json(['token' => $token, 'total' => count($rows), 'existing' => $existing, 'new' => count($rows) - $existing, 'titles' => array_slice($rows, 0, 10)]);
    }

    public function apply(Request $request)
    {
        $data = $request->validate(['token' => 'required|uuid']);
        $cache = Cache::store(config('cache.movie_import_store', 'file'));
        $lock = $cache->lock('tv-import:apply', 60);
        if (!$lock->get()) {
            throw ValidationException::withMessages(['token' => 'A TV import is running. Please try again shortly.']);
        }
        try {
            $snapshot = $cache->get('tv-import:'.$data['token']);
            if (!$snapshot || $snapshot['owner'] !== $request->user()->getAuthIdentifier()) {
                throw ValidationException::withMessages(['token' => 'This preview expired. Preview the TV list again.']);
            }
            if (isset($snapshot['result'])) return response()->json($snapshot['result']);
            $result = DB::transaction(function () use ($snapshot) {
                DB::table('tv_shows')->update(['top250_rank' => null]);
                $added = 0;
                foreach ($snapshot['rows'] as $row) {
                    $existing = DB::table('tv_shows')->where('imdb_id', $row['imdb_id'])->first();
                    $values = ['name' => $row['name'], 'year' => $row['year'], 'top250_rank' => $row['position'], 'updated_at' => now()];
                    if ($row['rating'] !== null) $values['rating'] = $row['rating'];
                    if (($row['votes'] ?? null) !== null) $values['votes'] = $row['votes'];
                    if (!$existing || blank($existing->image_url)) $values['image_url'] = $row['image_url'];
                    if ($existing) {
                        DB::table('tv_shows')->where('id', $existing->id)->update($values);
                    } else {
                        DB::table('tv_shows')->insert($values + ['imdb_id' => $row['imdb_id'], 'created_at' => now()]);
                        $added++;
                    }
                }
                return ['updated' => 250, 'added' => $added];
            });
            $snapshot['result'] = $result;
            $cache->put('tv-import:'.$data['token'], $snapshot, 1800);
            return response()->json($result);
        } finally {
            $lock->release();
        }
    }
}
