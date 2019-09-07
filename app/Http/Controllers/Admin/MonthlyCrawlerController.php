<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Http\Controllers\Controller;
use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use DB;
use App\Services\MonthlySearchService;


class MonthlyCrawlerController extends Controller
{
    

    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
        $this->monthlySearchService = new MonthlySearchService;
        
    }



    public function run(Request $request){

        $this->monthlySearchService->startFunc($request->product);

    }


    public function monthlytimer(){

      $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();

      foreach($products as $product){
        $this->monthlySearchService->startFunc($product["product_base_id"]);
        /*  $aspRow = array();
          $asp_array = array();
          $asp_name = $this->filterAsp($product["product_base_id"]);
          $asp_array = (json_decode($asp_name,true));
          
          foreach($asp_array as $name){
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            $className = __NAMESPACE__ . '\\' . 'Asp\Monthly'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();
            $run->{$functionName}($product["product_base_id"]);
          }
        */
          //app()->call( 'App\Http\Controllers\Admin\EstimateController@dailyCal', ['product_id'=> $product["product_base_id"]] );
      }
    }
}

