<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $preferences = $request->user()->setting()->firstOrCreate([], [
            'currency' => 'IDR',
            'date_format' => 'd M Y',
            'timezone' => 'Asia/Jakarta',
            'theme' => 'system',
        ]);

        config(['app.timezone' => $preferences->timezone]);
        date_default_timezone_set($preferences->timezone);
        $request->user()->setRelation('setting', $preferences);
        View::share('preferences', $preferences);

        return $next($request);
    }
}
