<?php 
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CSVServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('csv', function()
        {
            return new CSV;
        });

    }

}