<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
//use App\Http\Requests\CheckerRequest;

use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Controller;


use App\Product;
//use App\Dailysite;
use App\Asp;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\DailyDiff;
use App\DailySiteDiff;
use DB;
//use App\Services\CalculationService;

//header('Content-Type: text/html; charset=utf-8');

class CheckerController extends Controller
{
    //protected $calculationservice;

    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
    }
    public function index()
    {
        $user = Auth::user();
        $product_bases = ProductBase::all();
        //var_dump($product_bases);
        return view('admin.crawlerdaily',compact('product_bases','user'));
    }
    public function show_test()
    {
      $datas = \App\Product::all()->where('id','6');
      //var_dump($datas->asp) ;
      foreach($datas as $data)
      {
              echo $data->asp->login_url;
              //echo $data->asp->imp." ".$data->asp->click."<br>";
      }
    }

    public function check(Request $request){

          $aspRow = array();
          $asp_array = array();

          $asp_name = Asp::select('name')->where('id', '=' ,$request->asp_id)->get()->toArray();
          //$asp_array = (json_decode($asp_name,true));
          $result = 1;
          //var_dump($asp_name[0]["name"]);
          $functionName = str_replace(' ', '' ,mb_strtolower($asp_name[0]["name"]));
          $className = 'App\Http\Controllers\Admin\Asp\Check'. '\\'.str_replace(' ', '' ,$asp_name[0]["name"]).'Controller';
          $run = new $className();
          //var_dump($run);
            //echo __NAMESPACE__;
          $result = $run->{$functionName}($request->login ,$request->password,$request->product,$request->sponsor );
          //var_dump($result);
          return $result;

          //return redirect()->to('/daily_result', $status = 302, $headers = [], $secure = null);
    }

}