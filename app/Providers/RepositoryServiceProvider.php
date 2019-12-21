<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \App\Repositories\Monthly\MonthlySiteRepositoryInterface::class,
            \App\Repositories\Monthly\MonthlySiteRepository::class
        );
        $this->app->bind(
            \App\Repositories\Daily\DailySiteRepositoryInterface::class,
            \App\Repositories\Daily\DailySiteRepository::class
        );
        $this->app->bind(
            \App\Repositories\Daily\DailyRepositoryInterface::class,
            \App\Repositories\Daily\DailyRepository::class
        );
        $this->app->bind(
            \App\Repositories\Monthly\MonthlyRepositoryInterface::class,
            \App\Repositories\Monthly\MonthlyRepository::class
        );
    }

    public function boot()
    {
    }
}