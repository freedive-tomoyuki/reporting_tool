<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonthlySearchService;

class ExecuteMonthlyCommand extends Command
{
    protected $monthlySearchService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exec:monthly {product_base_id}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '月次実行　コマンド';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->monthlySearchService = new MonthlySearchService;
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
        $this->monthlySearchService->startFunc($pid);
        $this->info('finish');
    }
}
