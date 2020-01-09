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


class DailyCrawlerController extends Controller
{
    protected $dailySearchService;
   //protected $calculationservice;

    public function __construct( )
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
        
        $this->dailySearchService = new DailySearchService;
    }
    public function index()
    {
        $user = Auth::user();
        $product_bases = ProductBase::all();
        //var_dump($product_bases);
        return view('admin.crawlerdaily',compact('product_bases','user'));
    }

    //単体実装
    public function run(Request $request){

          $this->dailySearchService->startFunc($request->product);

          return redirect()->to('/admin/daily_report', $status = 302, $headers = [], $secure = null);
    }

    //バッチ実装
    public function dailytimer(){

      $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();
      //var_dump($products);

      foreach($products as $product){
          
          $this->dailySearchService->startFunc($product["product_base_id"]);
/*
          $aspRow = array();
          $asp_array = array();
          //echo $product["product_base_id"];
          $asp_name = $this->filterAsp($product["product_base_id"]);
          //var_dump($asp_name);
          $asp_array = (json_decode($asp_name,true));

          foreach ($asp_array as $name){
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            $className = __NAMESPACE__ . '\\' . 'Asp\Daily'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();
            $run->{$functionName}($product["product_base_id"]);
          }
          //差分からデイリーの件数を取得
            $this->diff($product["product_base_id"]);
            $this->diff_site($product["product_base_id"]);
*/
      }
    }
}