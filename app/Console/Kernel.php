<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\ChromeheadlessController;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->call('App\Http\Controllers\Admin\DailyCrawlerController@dailytimer')->dailyAt('11:41');

        $schedule->call('App\Http\Controllers\Admin\MonthlyCrawlerController@monthlytimer')->dailyAt('12:01');

        $schedule->call('App\Http\Controllers\Admin\MonthlyCrawlerController@calc_approval_rate_site')->dailyAt('12:27');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}