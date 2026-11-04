<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CatalogueContext
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET')) {
            if ($request->routeIs('tv.index')) {
                $request->session()->put('catalogue', 'tv');
            } elseif ($request->routeIs('home', 'nonauthhome')) {
                $request->session()->put('catalogue', 'movie');
            } elseif ($request->routeIs('admin') && in_array($request->query('catalogue'), ['movie', 'tv'], true)) {
                $request->session()->put('catalogue', $request->query('catalogue'));
            }
        }
        return $next($request);
    }
}
