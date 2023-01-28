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
                $yearTo = $date_now->year;
                $yearFrom = $date_now->subYears("50")->year;     
                break;
            case 'last_25_years':
                $yearTo = $date_now->year;
                $yearFrom = $date_now->subYears("25")->year;
                break;
            case 'last_10_years':
                $yearTo = $date_now->year;
                $yearFrom = $date_now->subYears("10")->year;
                break;
            case '2020s':
                $yearTo = $date_now->year;
                $yearFrom = '2020';
                break;
            case '2010s':
                $yearTo = '2019';
                $yearFrom = '2010';
                break;
            case '2000s':
                $yearTo = '2009';
                $yearFrom = '2000';
                break;
            case '90s':
                $yearTo = '1999';
                $yearFrom = '1990';
                break;
            case '80s':
                $yearTo = '1989';
                $yearFrom = '1980';
                break;
            case '70s':
                $yearTo = '1979';
                $yearFrom = '1970';
                break;
            case '60s':
                $yearTo = '1969';
                $yearFrom = '1960';
                break;
            default:
                break;
        }
        $dates = [
            'to' => $yearTo,
            'from' => $yearFrom
        ];
        return $dates;
    }
    
}
