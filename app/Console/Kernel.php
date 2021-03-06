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
        Commands\ExecuteDailyCommand::class,
        Commands\ExecuteMonthlyCommand::class
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
        $schedule->call('App\Http\Controllers\Admin\DailyCrawlerController@dailytimer')->dailyAt('12:15');

        $schedule->call('App\Http\Controllers\Admin\MonthlyCrawlerController@monthlytimer')->dailyAt('14:40');

        // $schedule->call('App\Http\Controllers\Admin\MonthlyCrawlerController@calc_approval_rate_site')->dailyAt('11:30');
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
