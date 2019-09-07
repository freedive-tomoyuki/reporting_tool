<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DuskServiceProvider;
use App\Services\DailySearchService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\CalculationService');
        $this->app->bind('App\Services\DailySearchService');
        $this->app->bind('App\Services\MonthlySearchService');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        
    }
}
