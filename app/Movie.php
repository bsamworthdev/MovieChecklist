<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Movie extends Model
{
    //
    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    static function parseTimePeriod($time_period)
    {
        $date_now = Carbon::now();

        switch ($time_period) {
            case 'last_50_years':
                $yearFrom = $date_now->subYears("50");
                $yearTo = $date_now->year;
                break;
            case 'last_25_years':
                $yearFrom = $date_now->subYears("25");
                $yearTo = $date_now->year;
                break;
            case 'last_10_years':
                $yearFrom = $date_now->subYears("10");
                $yearTo = $date_now->year;
                break;
            case '2020s':
                $yearFrom = '2020';
                $yearTo = $date_now->year;
                break;
            case '2010s':
                $yearFrom = '2010';
                $yearTo = '2019';
                break;
            case '2000s':
                $yearFrom = '2000';
                $yearTo = '2009';
                break;
            case '90s':
                $yearFrom = '1990';
                $yearTo = '1999';
                break;
            case '80s':
                $yearFrom = '1980';
                $yearTo = '1989';
                break;
            case '70s':
                $yearFrom = '1970';
                $yearTo = '1979';
                break;
            case '60s':
                $yearFrom = '1960';
                $yearTo = '1969';
                break;
            default:
                break;
        }
        $dates = [
            'from' => $yearFrom,
            'to' => $yearTo
        ];
        return $dates;
    }
    
}
