<?php

namespace App\Providers;

use App\Helpers\DateHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . '|' . (string) $request->input('phone'));
        });

        View::share('bsDateConfig', [
            'years' => DateHelper::getSupportedYears(),
            'months' => DateHelper::getMonthOptions(),
            'monthMap' => DateHelper::getBsMonthMap(),
            'today' => DateHelper::getCurrentBS(),
            'startEnglishDate' => DateHelper::START_ENGLISH_DATE,
            'startNepaliYear' => DateHelper::MIN_YEAR_BS,
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ]);
    }
}
