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
//use App\Dailydata;
use App\Product;
//use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\DailyDiff;
use App\DailySiteDiff;
use DB;
use App\Jobs\DailySearchJob;

//header('Content-Type: text/html; charset=utf-8');

class DailyCrawlerController extends Controller
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

          //データにnullの値があるためif文入れる（本来はいらない）
          //if(!empty($data->asp->cv)) 
              //部署名取得
              echo $data->asp->login_url;
              //echo $data->asp->imp." ".$data->asp->click."<br>";
      }
    }
    public function save_daily($data){
        $data_array = json_decode(json_encode(json_decode($data)), True );
/*
        echo gettype($data_array);
        var_dump($data_array);
        
        var_dump($data_array[0]['cv']);
        var_dump($data_array[0]['click']);
        var_dump($data_array[0]['imp']);
*/
        $cv = (intval($data_array[0]['cv']) != "" ) ? intval($data_array[0]['cv']) : 0 ; 
        $click = (intval($data_array[0]['click']) != "" ) ? intval($data_array[0]['click']) : 0 ;
        $imp = (intval($data_array[0]['imp']) != "" ) ? intval($data_array[0]['imp']) : 0 ;

        $crv = ($cv == 0 || $click == 0 )? 0 : ( $cv / $click ) * 100  ;
        $ctv = ($click == 0 || $imp == 0 )? 0 : ( $click / $imp ) * 100  ;
        $ratio = (date("d")/date("t"));
        $estimate_cv = ceil(($cv)/ $ratio);
        echo '|1:'.$cv;
        echo '|2:'.$ratio;
        echo '|3:'.$estimate_cv;
        /*Dailydata::create(
            [
            'imp' => $imp,
            'click' => $click,
            'cv' => $cv,
            'estimate_cv' => $estimate_cv,
            'cvr' => round($crv,2),
            'ctr' => round($ctv,2),
            'active' => $data_array[0]['active'],
            'partnership' => $data_array[0]['partnership'],
            'asp_id' => $data_array[0]['asp'],
            'product_id' => $data_array[0]['product'],
            'price' => $data_array[0]['price'],
            'cost' => $data_array[0]['cost'],
            'cpa' => $data_array[0]['cpa'],
            'date' => $data_array[0]['date']
            ]
        );*/
        Monthlydata::create(
            [
            'imp' => $imp,
            'click' => $click,
            'cv' => $cv,
            'estimate_cv' => $estimate_cv,
            'cvr' => round($crv,2),
            'ctr' => round($ctv,2),
            'active' => $data_array[0]['active'],
            'partnership' => $data_array[0]['partnership'],
            'asp_id' => $data_array[0]['asp'],
            'product_id' => $data_array[0]['product'],
            'price' => $data_array[0]['price'],
            'cost' => $data_array[0]['cost'],
            'cpa' => $data_array[0]['cpa'],
            'date' => $data_array[0]['date']
            ]
        );


    }
    /**
    　CPA算出用の関数
    */
    public function cpa($cv ,$price ,$asp){
      $calData = array();
      /*
        A8の場合の算出
      */
      if( $asp == 1 ){
        //$asp_fee = ($price * 1.2 * 1.08) * 1.08 ;
        //$asp_fee = ($price*1.08)+($price*1.08*0.3);//FDグロス
        $total = (($price * 1.08)+($price * 1.08 * 0.3) * 1.08 * 1.2);
        //$total = $asp_fee * 1.08 * 1.2;
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

    public function save_site($data){
        $month = date('m');
        $date = date('d');
        
        $data_array = json_decode(json_encode(json_decode($data)), True );

        //echo gettype($data_array);

        //var_dump($data_array);

        //for($i=0 ; $i <= count($data_array[0]) ; $i++){
        foreach($data_array as $data ){


            $cv = (intval($data['cv']) != "" ) ? intval($data['cv']) : 0 ; 
            $click = (intval($data['click']) != "" ) ? intval($data['click']) : 0 ;
            $imp = (intval($data['imp']) != "" ) ? intval($data['imp']) : 0 ;

            $cvr = ($cv == 0 || $click ==0 )? 0 : ( $cv / $click ) * 100 ;
            $ctr = ($click == 0|| $imp ==0 )? 0 : ( $click / $imp ) * 100 ;
            $ratio = (date("d")/date("t"));
            $estimate_cv = ceil(($cv)/ $ratio);

            /*Dailysite::create(
                [
                  'media_id' => $data['media_id'],
                  'site_name' => $data['site_name'],
                  'imp' => $imp,
                  'click' => $click,
                  'cv' => $cv,
                  'estimate_cv' => $estimate_cv,
                  'cvr' => round($cvr, 2),
                  'ctr' => round($ctr, 2),
                  'product_id' => $data['product'],
                  'price' => $data['price'],
                  'cost' => $data['cost'],
                  'cpa' => $data['cpa'],
                  'date' => $data['date']
                  
                ]
            );*/
            Monthlysite::create(
                [
                  'media_id' => $data['media_id'],
                  'site_name' => $data['site_name'],
                  'imp' => $imp,
                  'click' => $click,
                  'cv' => $cv,
                  'estimate_cv' => $estimate_cv,
                  'cvr' => round($cvr, 2),
                  'ctr' => round($ctr, 2),
                  'product_id' => $data['product'],
                  'price' => $data['price'],
                  'cost' => $data['cost'],
                  'cpa' => $data['cpa'],
                  'date' => $data['date']
                ]
            );
        }

    }
    /*
    public function cpa($cv ,$price ,$asp){
      $calData = array();
    */
      /*
        A8の場合の算出
      */
    /*  if( $asp == 1 ){
        //$asp_fee = ($price * 1.2 * 1.08) * 1.08 ;
        $asp_fee = ($price*1.08)+($price*1.08*0.3);//FDグロス
        $total = $asp_fee * 1.08 * 1.2;
      }
    */
      /*
        それ以外のASPの場合の算出
      */
    /*  else{
        //$asp_fee = ($price * 1.3 * 1.08) ;
        $asp_fee = $price ;//グロス
        $total = $asp_fee * 1.3;//FDグロス
      }

        $calData['cpa'] = round(($total == 0 || $cv == 0 )? 0 : $total / $cv);
        $calData['cost'] = $total;
        
      return json_encode($calData);
    }*/
    public function BasetoProduct($asp_id, $product_base_id){
        $converter = Product::select();
        $converter->where('product_base_id', $product_base_id);
        $converter->where('asp_id', $asp_id );
        $converter = $converter->get()->toArray();
              //var_dump($a8_product[0]["id"]);
        return $converter[0]["id"];
    } 


    public function filterAsp( $product_id ){
      $target_asp = Product::select('asp_id','name')
                  ->join('asps','products.asp_id','=','asps.id')
                  ->where('product_base_id', $product_id )
                  ->where('products.killed_flag', 0 )
                  ->get();

      return json_encode($target_asp);
    }

    private function refreshQueue():void
    {
        //\Artisan::call('queue:flush');
        //\Artisan::call('queue:work --timeout=0');
        //\DB::table('jobs')->truncate();
    }

    public function run(Request $request){

          $this->refreshQueue();

          DailySearchJob::dispatch($request->product);
/*
          $aspRow = array();
          $asp_array = array();

          $asp_name = $this->filterAsp($request->product);
          //var_dump($asp_name);
          $asp_array = (json_decode($asp_name,true));
          //var_dump($asp_array);
          //echo gettype($asp_id);
          //$i = 0;

          foreach ($asp_array as $name){

            //array_push($aspRow,str_replace(' ', '' ,mb_strtolower($name["name"])));
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            echo $className = 'App\Http\Controllers\Admin\Asp\Daily'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();
            //echo __NAMESPACE__;
            $run->{$functionName}($request->product);

          }*/
          //var_dump($aspRow);

          //foreach($aspRow as $function_name){
            //$this->{$function_name}($request->product);
            //var_dump($function_name);
          //}

          /**
            差分からデイリーの件数を取得
          */
          //$this->diff($request->product);
          //$this->diff_site($request->product);

          //$this->a8($request->product);
          //$this->rentracks($request->product);
          //$controller = new EstimateController;
          

          //$this->a8($request->product);
          //$this->rentracks($request->product);
          //$this->accesstrade($request->product);
          //$this->valuecommerce($request->product);
          //$this->afb($request->product);
          //echo "a";
          //return view('daily_result');
          //return redirect()->to('/daily_result', $status = 302, $headers = [], $secure = null);
    }
    /**
    *  前日分との差分からその日単位の増減数を計算
    *
    */
    public function diff($product_base_id){
        $i =0;

        $Array = array();
        $Array_1 = array();
        $daily_diff = array();
        $daily_diff_1 = array();
        $diff_ = array();

        $date = date("Y-m-d",strtotime("-1 day")); 
        $date_1 = date("Y-m-d",strtotime("-2 day")); 

        $products = Product::select()
              ->where('product_base_id', $product_base_id)
              ->where('killed_flag', 0)
              ->get()
              ->toArray();

        foreach($products as $product){
          //昨日分
          $Array = Monthlydata::where("product_id",$product['id'])->where("date",$date)->get()->toArray();
          if(!empty($Array)){
            array_push($daily_diff , $Array[0] );

          }
          //おととい分
          $Array_1 = Monthlydata::where("product_id",$product['id'])->where("date",$date_1)->get()->toArray();
          if(!empty($Array_1)){
            array_push($daily_diff_1 , $Array_1[0] );
          }
          

        
        }
        echo "<pre>";
        var_dump($daily_diff);
        var_dump($daily_diff_1);
        echo "</pre>";
        $e_asp = array();
        $l_asp = array();
        $x = 0;
           
        /* 前日比でなくなっているASPを考慮 */
        if(date("Y-m-d",strtotime("-2 day")) != date("Y-m-t",strtotime("-2 day"))){
          if(!empty($daily_diff_1)){
              foreach ( $daily_diff as $diff ){
                  foreach ( $daily_diff_1 as $diff_1 ) {
                      if($diff["asp_id"] == $diff_1["asp_id"]){
                        //$asp_id = $diff["asp_id"];
                        $diff_[$i]["imp"] = $diff["imp"] - $diff_1["imp"];
                        $diff_[$i]["click"] = $diff["click"] - $diff_1["click"];
                        $diff_[$i]["cv"] = $diff["cv"] - $diff_1["cv"];
                        $diff_[$i]["ctr"] = 
                        ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                        $diff_[$i]["cvr"] = 
                        ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;

                        $diff_[$i]["active"] = $diff["active"];
                        $diff_[$i]["estimate_cv"] = $diff["estimate_cv"];
                        $diff_[$i]["partnership"] = $diff["partnership"];
                        $diff_[$i]["price"] = $diff["price"] - $diff_1["price"];
                        $diff_[$i]["cpa"] = $diff["cpa"];
                        $diff_[$i]["cost"] = $diff["cost"] - $diff_1["cost"];
                        $diff_[$i]["asp_id"] = $diff["asp_id"];
                        $diff_[$i]["date"] = $diff["date"];
                        $diff_[$i]["product_id"] = $diff["product_id"];
                        //$diff_[$i]["killed_flag"] = 0;
                        $i++;
                        array_push( $e_asp, $diff["asp_id"]);
                        array_diff( $l_asp, $e_asp);
                        //var_dump();

                      }else{
                        array_push( $l_asp , $diff["asp_id"]);
                        
                      } 
                  }
              }
              //var_dump($l_asp);
              foreach ( $daily_diff as $diff ) {
                        if( in_array($diff["asp_id"], $l_asp )){
                          echo $diff["asp_id"];
                            $diff_[$i]["imp"] = $diff["imp"];
                            $diff_[$i]["click"] = $diff["click"];
                            $diff_[$i]["cv"] = $diff["cv"];
                            $diff_[$i]["ctr"] = 
                            ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                            $diff_[$i]["cvr"] = 
                            ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;
                            $diff_[$i]["active"] = $diff["active"];
                            $diff_[$i]["estimate_cv"] = $diff["estimate_cv"];
                            $diff_[$i]["partnership"] = $diff["partnership"];
                            $diff_[$i]["price"] = $diff["price"] ;
                            $diff_[$i]["cpa"] = $diff["cpa"];
                            $diff_[$i]["cost"] = $diff["cost"];
                            $diff_[$i]["asp_id"] = $diff["asp_id"];
                            $diff_[$i]["date"] = $diff["date"];
                            $diff_[$i]["product_id"] = $diff["product_id"];
                            //$diff_[$i]["killed_flag"] = 0;
                            $i++;
                        }
              }
          }else{
              foreach ( $daily_diff as $diff ){
                        //$asp_id = $diff["asp_id"];
                        $diff_[$i]["imp"] = $diff["imp"];
                        $diff_[$i]["click"] = $diff["click"];
                        $diff_[$i]["cv"] = $diff["cv"];
                        $diff_[$i]["ctr"] = 
                        ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                        $diff_[$i]["cvr"] = 
                        ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;

                        $diff_[$i]["active"] = $diff["active"];
                        $diff_[$i]["estimate_cv"] = $diff["estimate_cv"];
                        $diff_[$i]["partnership"] = $diff["partnership"];
                        $diff_[$i]["price"] = $diff["price"] ;
                        $diff_[$i]["cpa"] = $diff["cpa"];
                        $diff_[$i]["cost"] = $diff["cost"];
                        $diff_[$i]["asp_id"] = $diff["asp_id"];
                        $diff_[$i]["date"] = $diff["date"];
                        $diff_[$i]["product_id"] = $diff["product_id"];
                        //$diff_[$i]["killed_flag"] = 0;
                        $i++;
              }
          }
        }else{
            $diff_= $daily_diff;
        }

        echo "<pre>最終データ";
        var_dump($diff_);
        echo "</pre>";
        //$daily_diff = new DailyDiff();
        foreach ($diff_ as $insert_diff) {
            DailyDiff::create(
                [
                'imp' => $insert_diff["imp"],
                'ctr' => $insert_diff["ctr"],
                'click' => $insert_diff["click"],
                'cv' => $insert_diff["cv"],
                'cvr' => $insert_diff["cvr"],
                'active' => $insert_diff["active"],
                'partnership' => $insert_diff["partnership"],
                'price' => $insert_diff["price"],
                'cpa' => $insert_diff["cpa"],
                'cost' => $insert_diff["cost"],
                'estimate_cv' => $insert_diff["estimate_cv"],
                'asp_id' => $insert_diff["asp_id"],
                'date' => $insert_diff["date"],
                'product_id' => $insert_diff["product_id"]
                ]
            );
        }

    }
    /**
    *  前日分との差分からその日のサイト単位の増減数を計算
    *
    */
    public function diff_site($product_base_id = 4){
        $i =0;

        $Array = array();
        $Array_1 = array();
        $daily_diff = array();
        $daily_diff_1 = array();
        $diff_ = array();
        $list = array();
        $date = date("Y-m-d",strtotime("-1 day")); 
        $date_1 = date("Y-m-d",strtotime("-2 day"));

        $month = date('Ym',strtotime("-1 day"));

        $products = Product::select()
              ->where('product_base_id', $product_base_id)
              ->where('killed_flag', 0)
              ->get()
              ->toArray();

        foreach($products as $product){

          $monthlysites_table = $month.'_monthlysites';

          $Array[$product['id']] = DB::table($monthlysites_table)->where("product_id",$product['id'])->where("date",$date)->get()->toArray();
          
          //array_push($daily_diff[] , $Array );

          $Array_1[$product['id']] = DB::table($monthlysites_table)->where("product_id",$product['id'])->where("date",$date_1)->get()->toArray();
          //array_push($daily_diff_1 , $Array_1 );
          
          //$i++;
        
        }
        //$Array = json_decode($Array,true);
        //$Array_1 = json_decode($Array_1,true);
         
        echo "<pre>";
        echo "[Array]";
        var_dump($Array);
        echo "[Array1]";
        var_dump($Array_1);
        echo "</pre>";

        foreach ( $Array as $diff){
            foreach ( $diff as $site_a){
              array_push($daily_diff , $site_a );
            }
        }
        foreach ( $Array_1 as $diff){
            foreach ( $diff as $site_b){
              array_push($daily_diff_1 , $site_b );
            }
        }
        
        echo "<pre>";
        echo "result1";
        var_dump($daily_diff);
        echo "result2";
        var_dump($daily_diff_1);
        echo "</pre>";
        $daily_diff = json_decode(json_encode($daily_diff), true);
        $daily_diff_1 = json_decode(json_encode($daily_diff_1), true);

        foreach ( $daily_diff_1 as $site){
              echo $site["media_id"];
              echo $site["product_id"];
              
              array_push($list , $site["media_id"]."_".$site["product_id"] );
        }

        echo "<pre>";
        var_dump($list);
        echo "</pre>";
        /* 前日比でなくなっているASPを考慮 */
        $i = 0;
        //echo date("Y-m-t",strtotime("-1 month"));
        //月初一日
        if(date("Y-m-d",strtotime("-2 day")) != date("Y-m-t",strtotime("-2 day"))){
            foreach ( $daily_diff as $site){
                foreach ( $daily_diff_1 as $site_1){
              //foreach ( $Array_1 as $diff_1 ) {

                  if($site["media_id"] == $site_1["media_id"] && $site["product_id"] == $site_1["product_id"] ){
                  //$media_id = $diff["media_id"];
                  
                      $diff_[$i]["imp"] = $site["imp"] - $site_1["imp"];
                      $diff_[$i]["click"] = $site["click"] - $site_1["click"];
                      $diff_[$i]["cv"] = $site["cv"] - $site_1["cv"];
                      $diff_[$i]["ctr"] = 
                      ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                      $diff_[$i]["cvr"] = 
                      ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;
                      $diff_[$i]["estimate_cv"] = $site["estimate_cv"];
                      $diff_[$i]["price"] = $site["price"] - $site_1["price"];
                      $diff_[$i]["cpa"] = $site["cpa"];
                      $diff_[$i]["cost"] = $site["cost"] - $site_1["cost"];
                      $diff_[$i]["media_id"] = $site["media_id"];
                      $diff_[$i]["site_name"] = $site["site_name"];
                      $diff_[$i]["date"] = $site["date"];
                      $diff_[$i]["product_id"] = $site["product_id"];
                 
                    //} 
                    $i++;
                    break;
                  }

              }
            }
          foreach ( $daily_diff as $site){
                  if(!in_array($site["media_id"]."_".$site["product_id"], $list)){
                      $diff_[$i]["imp"] = $site["imp"];
                      $diff_[$i]["click"] = $site["click"];
                      $diff_[$i]["cv"] = $site["cv"];
                      $diff_[$i]["ctr"] = 
                      ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                      $diff_[$i]["cvr"] = 
                      ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;
                      $diff_[$i]["estimate_cv"] = $site["estimate_cv"];
                      $diff_[$i]["price"] = $site["price"];
                      $diff_[$i]["cpa"] = $site["cpa"];
                      $diff_[$i]["cost"] = $site["cost"] ;
                      $diff_[$i]["media_id"] = $site["media_id"];
                      $diff_[$i]["site_name"] = $site["site_name"];
                      $diff_[$i]["date"] = $site["date"];
                      $diff_[$i]["product_id"] = $site["product_id"];
                      $i++;
                  }
              
          }
        }else{
            $diff_= $daily_diff ;
        }
        
        echo "<pre>";
        echo "diff_";
        var_dump($diff_);
        echo "</pre>";
        //$daily_diff = new DailyDiff();
        foreach ($diff_ as $insert_diff) {
          echo "<pre>";
          
          $insert_diff = json_decode(json_encode($insert_diff), True );
          var_dump($insert_diff);
          echo "</pre>";
          

            DailySiteDiff::create(
                [
                  'imp' => $insert_diff["imp"],
                  'ctr' => $insert_diff["ctr"],
                  'click' => $insert_diff["click"],
                  'cv' => $insert_diff["cv"],
                  'cvr' => $insert_diff["cvr"],
                  'media_id' => $insert_diff["media_id"],
                  'site_name' => $insert_diff["site_name"],
                  'price' => $insert_diff["price"],
                  'cpa' => $insert_diff["cpa"],
                  'cost' => $insert_diff["cost"],
                  'date' => $insert_diff["date"],
                  'estimate_cv' => $insert_diff["estimate_cv"],
                  'product_id' => $insert_diff["product_id"]
                ]
            );
        }

    }
    public function dailytimer(){

      $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();
      //var_dump($products);

      foreach($products as $product){
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
          /**
            差分からデイリーの件数を取得
          */
            $this->diff($product["product_base_id"]);
            $this->diff_site($product["product_base_id"]);
      }
    }
}