<?php

namespace App\Providers;

use App\Helpers\DateHelper;
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
