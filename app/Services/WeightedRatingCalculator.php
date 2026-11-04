<?php

namespace App\Services;

use App\Movie;
use Illuminate\Support\Facades\DB;

class WeightedRatingCalculator
{
    public function recalculate(): int
    {
        return DB::transaction(function () {
            $movies = Movie::query()->select(['id', 'rating', 'votes'])->lockForUpdate()->get();
            if ($movies->isEmpty()) return 0;

            $minimumVotes = 25000;
            $averageRating = $movies->sum('rating') / $movies->count();
            foreach ($movies as $movie) {
                $votes = (int) $movie->votes;
                $movie->weighted_rating = round(
                    ($votes / ($votes + $minimumVotes)) * (float) $movie->rating
                    + ($minimumVotes / ($votes + $minimumVotes)) * $averageRating,
                    4
                );
                $movie->save();
            }

            return $movies->count();
        });
    }
}
