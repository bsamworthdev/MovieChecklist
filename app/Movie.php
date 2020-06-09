<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Movie extends Model
{
    //
    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    static function parseTimePeriod($time_period)
    {

        switch ($time_period) {
            case 'last_50_years':
                $yearFrom = '1970';
                $yearTo = '2020';
                break;
            case 'last_25_years':
                $yearFrom = '1995';
                $yearTo = '2020';
                break;
            case 'last_10_years':
                $yearFrom = '2010';
                $yearTo = '2020';
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
