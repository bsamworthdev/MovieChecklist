<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImdbTop250Import
{
    public function preview(array $rows): array
    {
        return $this->match($rows, DB::table('movies')->get());
    }

    private function match(array $rows, $movies): array
    {
        $matches = [];
        $unmatched = [];
        foreach ($rows as $row) {
            $byCode = $movies->filter(fn ($movie) => $movie->imdb_id === $row['imdb_id']);
            $byTitle = $movies->filter(fn ($movie) => (int) $movie->year === $row['year']
                && $this->title($movie->name) === $this->title($row['name']));
            $found = null;
            if ($byCode->count() === 1) {
                $found = $byCode->first();
            } elseif ($byCode->isEmpty() && $byTitle->count() === 1) {
                $candidate = $byTitle->first();
                if (!$candidate->imdb_id || $candidate->imdb_id === $row['imdb_id']) {
                    $found = $candidate;
                }
            }
            $matches[$row['position']] = $found ? (int) $found->id : null;
            if (!$found) {
                $unmatched[] = $row + ['reason' => $byCode->count() > 1 || $byTitle->count() > 1
                    ? 'Multiple existing movies match; resolve the duplicate rows before importing.'
                    : ($byTitle->isNotEmpty() ? 'A matching title has a different IMDb code.' : 'No existing movie matched.')];
            }
        }
        return ['matches' => $matches, 'unmatched' => $unmatched];
    }

    private function title(string $title): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($title)));
    }

    public function apply(array $rows, array $expectedMatches, array $decisions): array
    {
        return DB::transaction(function () use ($rows, $expectedMatches, $decisions) {
            $movies = DB::table('movies')->orderBy('id')->lockForUpdate()->get();
            $preview = $this->match($rows, $movies);
            if ($preview['matches'] !== $expectedMatches) {
                throw ValidationException::withMessages(['import' => 'The movies table changed after the preview. Preview the pasted list again before applying it.']);
            }
            $targets = $preview['matches'];
            $missingPositions = array_column($preview['unmatched'], 'position');
            foreach (array_keys($decisions) as $position) {
                if (!in_array((int) $position, $missingPositions, true)) {
                    throw ValidationException::withMessages(['decisions' => 'An unexpected movie resolution was submitted. Refresh the preview.']);
                }
            }
            foreach ($preview['unmatched'] as $row) {
                $position = $row['position'];
                if (!array_key_exists($position, $decisions)) {
                    throw ValidationException::withMessages(["decisions.$position" => 'Review each unmatched movie. Leave its code blank only to add it as new.']);
                }
                $code = trim($decisions[$position] ?? '');
                if ($code !== '') {
                    $found = $movies->filter(fn ($movie) => $movie->imdb_id === $code);
                    if ($found->count() !== 1) {
                        throw ValidationException::withMessages(["decisions.$position" => 'That code must identify exactly one movie already in your database.']);
                    }
                    $targets[$position] = (int) $found->first()->id;
                }
            }
            $existingTargets = array_filter($targets, fn ($id) => $id !== null);
            if (count($existingTargets) !== count(array_unique($existingTargets))) {
                throw ValidationException::withMessages(['decisions' => 'Two chart entries point to the same existing movie. Correct their codes before importing.']);
            }
            // A manual mapping must not leave a second row holding the current IMDb code.
            foreach ($rows as $row) {
                $others = $movies->filter(fn ($movie) => $movie->imdb_id === $row['imdb_id']
                    && (int) $movie->id !== $targets[$row['position']]);
                if ($others->isNotEmpty()) {
                    throw ValidationException::withMessages(['decisions' => 'Duplicate IMDb codes must be resolved in the database before importing.']);
                }
            }
            $now = now();
            $summary = ['updated' => 0, 'added' => 0, 'artwork_added' => 0, 'unrated_added' => 0];
            DB::table('movies')->update(['top250_rank' => 999]);
            foreach ($rows as $row) {
                $id = $targets[$row['position']];
                if ($id !== null) {
                    $existing = $movies->firstWhere('id', $id);
                    $values = ['top250_rank' => $row['position'], 'imdb_id' => $row['imdb_id'], 'updated_at' => $now];
                    if ($row['rating'] !== null) {
                        $values['rating'] = $row['rating'];
                    }
                    if (!$existing->image_url && $row['image_url']) {
                        $values['image_url'] = $row['image_url'];
                        $summary['artwork_added']++;
                    }
                    DB::table('movies')->where('id', $id)->update($values);
                    $summary['updated']++;
                } else {
                    DB::table('movies')->insert([
                        'imdb_id' => $row['imdb_id'], 'name' => $row['name'], 'year' => $row['year'],
                        'rank' => 1001, 'top250_rank' => $row['position'], 'rating' => $row['rating'] ?? 0,
                        'language' => '', 'image_url' => $row['image_url'], 'image_url_small' => null,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $summary['added']++;
                    $summary['artwork_added'] += $row['image_url'] ? 1 : 0;
                    $summary['unrated_added'] += $row['rating'] === null ? 1 : 0;
                }
            }
            return $summary;
        });
    }
}
