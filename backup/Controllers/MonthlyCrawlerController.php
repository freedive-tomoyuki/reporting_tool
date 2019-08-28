<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use DB;

//header('Content-Type: text/html; charset=utf-8');

class MonthlyCrawlerController extends Controller
{
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth');
    }
/**
　月次の基本データの保存。日毎の承認件数、承認金額のアップロード

*/
    public function save_monthly($data){
        $data_array = json_decode(json_encode(json_decode($data)), True );

        DB::table('monthlydatas')
          ->where('product_id', $data_array[0]['product'])
          ->where('date', $data_array[0]['date'])

          ->update([
              'approval_price' => $data_array[0]['approval_price'],
              'approval' => $data_array[0]['approval'],
          ]);
        
        DB::table('monthlydatas')
              ->where('product_id', $data_array[0]['product'])
              ->where('date', $data_array[0]['last_date'])

              ->update([
                  'approval_price' => $data_array[0]['last_approval_price'],
                  'approval' => $data_array[0]['last_approval'],
        ]);

    }
/**
　月次のサイト別データの保存。日毎の承認件数、承認金額のアップロード

*/

    public function save_site($data){
        
        $data_array = json_decode(json_encode(json_decode($data)), True );
        $month = date('Ym', strtotime('-1 day'));
        $monthlysites_table = $month.'_monthlysites';

        foreach($data_array as $data ){

            /*echo  $data['product'];
            echo  $data['media_id'];
            echo  $data['date'];*/
            
            DB::table($monthlysites_table)
              ->where('product_id', $data['product'])
              ->where('media_id', $data['media_id'])
              ->where('date', $data['date'])

              ->update([
                  'approval_price' => $data['approval_price'],
                  'approval' => $data['approval'],
            ]);

        }

    }

/**
　CPA算出用の関数
*/

    public function cpa($cv ,$price ,$asp){
      $calData = array();

      //A8の場合
      if( $asp == 1 ){
        //$asp_fee = ($price * 1.2 * 1.08) * 1.08 ;
        $asp_fee = ($price*1.08)+($price*1.08*0.3);//FDグロス
        $total = $asp_fee * 1.08 * 1.2;
      }
      /*
        それ以外のASPの場合の算出
      */
      else{
        //$asp_fee = ($price * 1.3 * 1.08) ;
        $asp_fee = $price ;//グロス
        $total = $asp_fee * 1.3;//FDグロス
      }

      $calData['cpa'] = round(($total == 0 || $cv == 0 )? 0 : $total / $cv);
      $calData['cost'] = $total;

      return json_encode($calData);
    }
/**
 親案件IDとASPIDから案件IDを取得
*/
    public function BasetoProduct($asp_id, $baseproduct){
        $converter = Product::select();
        $converter->where('product_base_id', $baseproduct);
        $converter->where('asp_id', $asp_id );
        $converter = $converter->get()->toArray();
              //var_dump($converter[0]["id"]);
        return $converter[0]["id"];
    } 
/**
 案件IDからASPを取得
*/

    public function filterAsp( $product_id ){
      $target_asp = Product::select('asp_id','name')
                  ->join('asps','products.asp_id','=','asps.id')
                  ->where('product_base_id', $product_id )
                  ->where('products.killed_flag', 0 )
                  ->get();

      return json_encode($target_asp);
    }
    public function run(Request $request){

        $aspRow = array();
        $asp_array = array();

        $asp_name = $this->filterAsp($request->product);
        //var_dump($asp_name);
        $asp_array = (json_decode($asp_name,true));

        foreach($asp_array as $name){
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            $className = __NAMESPACE__ . '\\' . 'Asp\Monthly'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();

            $run->{$functionName}($request->product);
        }
        //推定値　
        app()->call( 'App\Http\Controllers\EstimateController@dailyCal', ['product_id'=> $request->product] );
          //$this->a8($request->product);
          //$this->rentracks($request->product);
          //$this->accesstrade($request->product);
          //
          //$this->valuecommerce($request->product);
          //$this->afb($request->product);
          //echo "a";
          //return view('daily_result');
          //return redirect()->to('/daily_result', $status = 302, $headers = [], $secure = null);
    }
    public function monthlytimer(){

      $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();

      foreach($products as $product){
          $aspRow = array();
          $asp_array = array();
          $asp_name = $this->filterAsp($product["product_base_id"]);
          $asp_array = (json_decode($asp_name,true));
          
          foreach($asp_array as $name){
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            $className = __NAMESPACE__ . '\\' . 'Asp\Monthly'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();
            $run->{$functionName}($product["product_base_id"]);
          }

          app()->call( 'App\Http\Controllers\EstimateController@dailyCal', ['product_id'=> $product["product_base_id"]] );
      }
    }
}

