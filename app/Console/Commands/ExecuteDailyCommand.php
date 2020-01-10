<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DailySearchService;

class ExecuteDailyCommand extends Command
{
    protected $dailySearchService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exec:daily {product_base_id}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '日次実行　コマンド';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dailySearchService = new DailySearchService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('start');
        $pid = $this->argument("product_base_id");
        $this->dailySearchService->startFunc($pid);
        $this->info('finish');
    }
}
