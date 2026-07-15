<?php

namespace App\Providers;

use App\Models\Agence;
use App\Support\PointageRhSettingsMerger;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $locale = (string) config('app.locale', 'fr');
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        CarbonPeriod::setLocale($locale);
        CarbonInterval::setLocale($locale);

        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'french', 'fra');

        Route::model('site', Agence::class);
        PointageRhSettingsMerger::mergeStoredPayloadIntoConfig();
    }
}
