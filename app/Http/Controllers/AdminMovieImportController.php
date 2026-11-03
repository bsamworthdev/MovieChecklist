<?php

namespace App\Http\Controllers;

use App\Services\ImdbTop250Import;
use App\Services\ImdbTop250Parser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdminMovieImportController extends Controller
{
    public function preview(Request $request, ImdbTop250Parser $parser, ImdbTop250Import $importer)
    {
        $data = $request->validate(['html' => 'required|string|max:5000000'], [
            'html.required' => 'Paste the complete HTML from IMDb’s Top 250 page before previewing movies.',
        ]);
        try {
            $rows = $parser->parse($data['html']);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['html' => $exception->getMessage().' Paste the complete Top 250 page HTML below to try again.']);
        }
        $preview = $importer->preview($rows);
        $token = (string) Str::uuid();
        // Keep the large chart out of database sessions (whose payload may be TEXT).
        $cache = Cache::store(config('cache.movie_import_store', 'file'));
        $cacheKey = 'movie-import:'.$token;
        $snapshot = [
            'token' => $token, 'expires_at' => time() + 1800,
            'user_id' => $request->user()->getAuthIdentifier(),
            'rows' => $rows, 'matches' => $preview['matches'],
        ];
        if (!$cache->put($cacheKey, $snapshot, 1800)) {
            throw ValidationException::withMessages(['import' => 'The preview could not be saved. Check the import cache storage and try again.']);
        }
        $previous = $request->session()->get('movie_import');
        if (isset($previous['cache_key'])) {
            $cache->forget($previous['cache_key']);
        }
        $request->session()->put('movie_import', ['token' => $token, 'cache_key' => $cacheKey]);
        return response()->json([
            'token' => $token, 'total' => count($rows),
            'matched' => count($rows) - count($preview['unmatched']),
            'unmatched' => $preview['unmatched'],
        ]);
    }

    public function apply(Request $request, ImdbTop250Import $importer)
    {
        $data = $request->validate([
            'token' => 'required|uuid', 'decisions' => 'present|array|max:250',
            'decisions.*' => ['nullable', 'string', 'regex:/^tt\d{5,12}$/'],
        ]);
        $reference = $request->session()->get('movie_import');
        $cache = Cache::store(config('cache.movie_import_store', 'file'));
        $snapshot = isset($reference['cache_key']) ? $cache->get($reference['cache_key']) : null;
        if (!$snapshot || !hash_equals($snapshot['token'], $data['token']) || $snapshot['expires_at'] <= time()
            || $snapshot['user_id'] !== $request->user()->getAuthIdentifier()) {
            throw ValidationException::withMessages(['token' => 'This preview has expired or was replaced. Preview the pasted list again.']);
        }
        if (isset($snapshot['result'])) {
            return response()->json($snapshot['result']);
        }
        $result = $importer->apply($snapshot['rows'], $snapshot['matches'], $data['decisions']);
        $snapshot['result'] = $result;
        $cache->put($reference['cache_key'], $snapshot, max(1, $snapshot['expires_at'] - time()));
        return response()->json($result);
    }
}
