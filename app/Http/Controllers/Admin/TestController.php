<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Controller;


use App\Http\Controllers\Admin\Asp;
use DB;
use DailySearch;
use App\Product;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\DailyDiff;
use App\DailySiteDiff;
use App\Services\DailySearchService;


class TestController extends Controller
{
    protected $dailySearchService;
   //protected $calculationservice;

    public function __construct( )
    {
       $this->dailySearchService = new DailySearchService;
    }
    public function index()
    {
    }
    //単体実装
    public function run($param){
          $this->dailySearchService->diff_site($param);
    }

}