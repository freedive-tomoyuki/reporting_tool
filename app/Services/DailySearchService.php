<?php

namespace App\Services;

use DB;
use App\Product;
use App\DailyDiff;
use App\DailySiteDiff;
use App\Monthlydata;
use App\Monthlysite;

class DailySearchService
{

    private $product;

    public function startFunc($product)
    {
      
        $aspRow = array();
        $asp_array = array();

        $asp_name = $this->filterAsp($product);
        $asp_array = (json_decode($asp_name,true));
        
        foreach ($asp_array as $name){
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            $className = 'App\Http\Controllers\Admin\Asp\Daily'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();
            $run->{$functionName}($product);
        }

        //差分からデイリーの件数を取得
        $this->diff($product);
        $this->diff_site($product);

    }

    public function save_daily($data){
        $data_array = json_decode(json_encode(json_decode($data)), True );

        $cv     = (intval($data_array[0]['cv']) != "" ) ? intval($data_array[0]['cv']) : 0 ; 
        $click  = (intval($data_array[0]['click']) != "" ) ? intval($data_array[0]['click']) : 0 ;
        $imp    = (intval($data_array[0]['imp']) != "" ) ? intval($data_array[0]['imp']) : 0 ;

        $crv    = ($cv == 0 || $click == 0 )? 0 : ( $cv / $click ) * 100  ;
        $ctv    = ($click == 0 || $imp == 0 )? 0 : ( $click / $imp ) * 100  ;
        $ratio  = (date("d")/date("t"));
        $estimate_cv = ceil(($cv)/ $ratio);

        // Monthlydata::create(
        //     [
        //     'imp' => $imp,
        //     'click' => $click,
        //     'cv' => $cv,
        //     'estimate_cv' => $estimate_cv,
        //     'cvr' => round($crv,2),
        //     'ctr' => round($ctv,2),
        //     'active' => $data_array[0]['active'],
        //     'partnership' => $data_array[0]['partnership'],
        //     'asp_id' => $data_array[0]['asp'],
        //     'product_id' => $data_array[0]['product'],
        //     'price' => $data_array[0]['price'],
        //     'cost' => $data_array[0]['cost'],
        //     'cpa' => $data_array[0]['cpa'],
        //     'date' => $data_array[0]['date']
        //     ]
        // );
        Monthlydata::updateOrCreate(
            [
              'date' => $data_array[0]['date'] ,
              'product_id' => $data_array[0]['product']
            ],
            [
              'asp_id' => $data_array[0]['asp'],
              'imp' => $imp,
              'click' => $click,
              'cv' => $cv,
              'estimate_cv' => $estimate_cv,
              'cvr' => round($crv,2),
              'ctr' => round($ctv,2),
              'active' => $data_array[0]['active'],
              'partnership' => $data_array[0]['partnership'],
              'price' => $data_array[0]['price'],
              'cost' => $data_array[0]['cost'],
              'cpa' => $data_array[0]['cpa']
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

        foreach($data_array as $data ){


            $cv = (intval($data['cv']) != "" ) ? intval($data['cv']) : 0 ; 
            $click = (intval($data['click']) != "" ) ? intval($data['click']) : 0 ;
            $imp = (intval($data['imp']) != "" ) ? intval($data['imp']) : 0 ;

            $cvr = ($cv == 0 || $click ==0 )? 0 : ( $cv / $click ) * 100 ;
            $ctr = ($click == 0|| $imp ==0 )? 0 : ( $click / $imp ) * 100 ;
            $ratio = (date("d")/date("t"));
            $estimate_cv = ceil(($cv)/ $ratio);

            // Monthlysite::create(
            //     [
            //       'media_id' => $data['media_id'],
            //       'site_name' => $data['site_name'],
            //       'imp' => $imp,
            //       'click' => $click,
            //       'cv' => $cv,
            //       'estimate_cv' => $estimate_cv,
            //       'cvr' => round($cvr, 2),
            //       'ctr' => round($ctr, 2),
            //       'product_id' => $data['product'],
            //       'price' => $data['price'],
            //       'cost' => $data['cost'],
            //       'cpa' => $data['cpa'],
            //       'date' => $data['date']
            //     ]
            // );
            $monthlysites_table = date('Ym',strtotime('-1 day')).'_monthlysites';


            DB::table($monthlysites_table)->updateOrInsert(
                [
                  'media_id' => $data['media_id'],
                  'product_id' => $data['product'],
                  'date' => $data['date']
                ],
                [
                  'site_name' => $data['site_name'],
                  'imp' => $imp,
                  'click' => $click,
                  'cv' => $cv,
                  'estimate_cv' => $estimate_cv,
                  'cvr' => round($cvr, 2),
                  'ctr' => round($ctr, 2),
                  'price' => $data['price'],
                  'cost' => $data['cost'],
                  'cpa' => $data['cpa'], 
                  'created_at' =>  \Carbon\Carbon::now(),
                  'updated_at' => \Carbon\Carbon::now()
                ]
            );
        }

    }

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

    /**
    *  前日分との差分からその日単位の増減数を計算
    *
    */
    public function diff($product_base_id){
        $i =0;

        $Array          = array();
        $Array_1        = array();
        $yesterday_data     = array();
        $before_yesterday_data   = array();
        $diff_data          = array();

        $yesterday           = date("Y-m-d",strtotime("-1 day")); 
        $before_yesterday         = date("Y-m-d",strtotime("-2 day")); 

        $match_asp_id = array();//マッチしたASP配列
        $yesterday_asp_id = array();
        $diff_asp_id = array();//差分のあるASP配列

        $products = Product::select()
              ->where('product_base_id', $product_base_id)
              ->where('killed_flag', 0)
              ->get()
              ->toArray();

        foreach($products as $product){
          //昨日分
          $Array = Monthlydata::where("product_id",$product['id'])->where("date",$yesterday)->get()->toArray();
          if(!empty($Array)){
            array_push($yesterday_data , $Array[0] );//昨日のデータ
          }
          //おととい分
          $Array_1 = Monthlydata::where("product_id",$product['id'])->where("date",$before_yesterday)->get()->toArray();
          if(!empty($Array_1)){
            array_push($before_yesterday_data , $Array_1[0] );
          }
        }

        // 前日比でなくなっているASPを考慮 
        if(date("Y-m-d",strtotime("-2 day")) != date("Y-m-t",strtotime("-2 day"))){//２日前が月末以外のとき

          if(!empty($before_yesterday_data)){//おとといのデータが全案件取れている
              foreach ( $yesterday_data as $diff ){
                
                array_push( $yesterday_asp_id , $diff["asp_id"]);//実行したASP

                  foreach ( $before_yesterday_data as $diff_1 ) {
                      if($diff["asp_id"] == $diff_1["asp_id"]){

                        $diff_data[$i]["imp"] = $diff["imp"] - $diff_1["imp"];
                        $diff_data[$i]["click"] = $diff["click"] - $diff_1["click"];
                        $diff_data[$i]["cv"] = $diff["cv"] - $diff_1["cv"];
                        $diff_data[$i]["ctr"] = 
                        ($diff_data[$i]["imp"] > 0 && $diff_data[$i]["click"] > 0 ) ? intval($diff_data[$i]["imp"])/intval($diff_data[$i]["click"]): 0 ;
                        $diff_data[$i]["cvr"] = 
                        ($diff_data[$i]["click"] > 0 && $diff_data[$i]["cv"] > 0 )? intval($diff_data[$i]["click"])/intval($diff_data[$i]["cv"]): 0 ;

                        $diff_data[$i]["active"] = $diff["active"];
                        $diff_data[$i]["estimate_cv"] = $diff["estimate_cv"];
                        $diff_data[$i]["partnership"] = $diff["partnership"];
                        $diff_data[$i]["price"] = $diff["price"] - $diff_1["price"];
                        $diff_data[$i]["cpa"] = $diff["cpa"];
                        $diff_data[$i]["cost"] = $diff["cost"] - $diff_1["cost"];
                        $diff_data[$i]["asp_id"] = $diff["asp_id"];
                        $diff_data[$i]["date"] = $diff["date"];
                        $diff_data[$i]["product_id"] = $diff["product_id"];
                        //$diff_[$i]["killed_flag"] = 0;
                        $i++;
                        array_push( $match_asp_id, $diff["asp_id"]);

                      }

                  }

              }

              $diff_asp_id = array_diff( $yesterday_asp_id, $match_asp_id);

              //一昨日はない／昨日はあるという案件（途中で始まった案件）
              foreach ( $yesterday_data as $diff ) {
                        if( in_array($diff["asp_id"], $diff_asp_id )){
                         // echo $diff["asp_id"];
                            $diff_data[$i]["imp"] = $diff["imp"];
                            $diff_data[$i]["click"] = $diff["click"];
                            $diff_data[$i]["cv"] = $diff["cv"];
                            $diff_data[$i]["ctr"] = 
                            ($diff_data[$i]["imp"] > 0 && $diff_data[$i]["click"] > 0 ) ? intval($diff_data[$i]["imp"])/intval($diff_data[$i]["click"]): 0 ;
                            $diff_data[$i]["cvr"] = 
                            ($diff_data[$i]["click"] > 0 && $diff_data[$i]["cv"] > 0 )? intval($diff_data[$i]["click"])/intval($diff_data[$i]["cv"]): 0 ;
                            $diff_data[$i]["active"] = $diff["active"];
                            $diff_data[$i]["estimate_cv"] = $diff["estimate_cv"];
                            $diff_data[$i]["partnership"] = $diff["partnership"];
                            $diff_data[$i]["price"] = $diff["price"] ;
                            $diff_data[$i]["cpa"] = $diff["cpa"];
                            $diff_data[$i]["cost"] = $diff["cost"];
                            $diff_data[$i]["asp_id"] = $diff["asp_id"];
                            $diff_data[$i]["date"] = $diff["date"];
                            $diff_data[$i]["product_id"] = $diff["product_id"];
                            //$diff_[$i]["killed_flag"] = 0;
                            $i++;
                        }
              }
          }else{

            //一昨日のデータが全案件取れていないとき
              foreach ( $yesterday_data as $diff ){
                        //$asp_id = $diff["asp_id"];

                        $diff_data[$i]["imp"] = $diff["imp"];
                        $diff_data[$i]["click"] = $diff["click"];
                        $diff_data[$i]["cv"] = $diff["cv"];
                        $diff_data[$i]["ctr"] = 
                        ($diff_data[$i]["imp"] > 0 && $diff_data[$i]["click"] > 0 ) ? intval($diff_data[$i]["imp"])/intval($diff_data[$i]["click"]): 0 ;
                        $diff_data[$i]["cvr"] = 
                        ($diff_data[$i]["click"] > 0 && $diff_data[$i]["cv"] > 0 )? intval($diff_data[$i]["click"])/intval($diff_data[$i]["cv"]): 0 ;

                        $diff_data[$i]["active"] = $diff["active"];
                        $diff_data[$i]["estimate_cv"] = $diff["estimate_cv"];
                        $diff_data[$i]["partnership"] = $diff["partnership"];
                        $diff_data[$i]["price"] = $diff["price"] ;
                        $diff_data[$i]["cpa"] = $diff["cpa"];
                        $diff_data[$i]["cost"] = $diff["cost"];
                        $diff_data[$i]["asp_id"] = $diff["asp_id"];
                        $diff_data[$i]["date"] = $diff["date"];
                        $diff_data[$i]["product_id"] = $diff["product_id"];
                        //$diff_[$i]["killed_flag"] = 0;
                        $i++;
              }
          }
        }else{//1日のデータは差分を取らない。
            $diff_data= $yesterday_data;
        }

        foreach ($diff_data as $insert_diff) {
            // DailyDiff::create(
            //     [
            //     'imp' => $insert_diff["imp"],
            //     'ctr' => $insert_diff["ctr"],
            //     'click' => $insert_diff["click"],
            //     'cv' => $insert_diff["cv"],
            //     'cvr' => $insert_diff["cvr"],
            //     'active' => $insert_diff["active"],
            //     'partnership' => $insert_diff["partnership"],
            //     'price' => $insert_diff["price"],
            //     'cpa' => $insert_diff["cpa"],
            //     'cost' => $insert_diff["cost"],
            //     'estimate_cv' => $insert_diff["estimate_cv"],
            //     'asp_id' => $insert_diff["asp_id"],
            //     'date' => $insert_diff["date"],
            //     'product_id' => $insert_diff["product_id"]
            //     ]
            // );
            DailyDiff::updateOrCreate(
              [
                'date' => $insert_diff["date"],
                'product_id' => $insert_diff["product_id"]
              ],
              [
                'asp_id' => $insert_diff["asp_id"],
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
                'estimate_cv' => $insert_diff["estimate_cv"]
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

        $Array          = array();
        $Array_1        = array();
        $yesterday_data     = array();
        $before_yesterday_data   = array();
        $diff_data          = array();
        $before_yesterday_site_id           = array();

        $yesterday           = date("Y-m-d",strtotime("-2 day")); 
        $before_yesterday         = date("Y-m-d",strtotime("-3 day"));
        $month          = date('Ym',strtotime("-1 day"));

        $match_asp_id = array();//マッチしたASP配列
        $yesterday_asp_id = array();
        $diff_asp_id = array();//差分のあるASP配列

        $products = Product::select()
              ->where('product_base_id', $product_base_id)
              ->where('killed_flag', 0)
              ->get()
              ->toArray();

        foreach($products as $product){

          $monthlysites_table = $month.'_monthlysites';

          $Array[$product['id']] = DB::table($monthlysites_table)->where("product_id",$product['id'])->where("date",$yesterday)->get()->toArray();
          
          //array_push($daily_diff[] , $Array );

          $Array_1[$product['id']] = DB::table($monthlysites_table)->where("product_id",$product['id'])->where("date",$before_yesterday)->get()->toArray();
        
        }

        foreach ( $Array as $diff){
            foreach ( $diff as $site_a){
              array_push($yesterday_data , $site_a );
            }
        }
        foreach ( $Array_1 as $diff){
            foreach ( $diff as $site_b){
              array_push($before_yesterday_data , $site_b );
            }
        }

        $yesterday_data = json_decode(json_encode($yesterday_data), true);
        $before_yesterday_data = json_decode(json_encode($before_yesterday_data), true);

        foreach ( $before_yesterday_data as $site){
              //echo $site["media_id"];
              //echo $site["product_id"];
              
              array_push($before_yesterday_site_id , $site["media_id"]."_".$site["product_id"] );
        }

        //echo "<pre>";
        //var_dump($list);
        //echo "</pre>";
        // 前日比でなくなっているASPを考慮 
        $i = 0;
        //echo date("Y-m-t",strtotime("-1 month"));
        //月初一日のデータ以外
        if(date("Y-m-d",strtotime("-2 day")) != date("Y-m-t",strtotime("-2 day"))){
          
          var_dump($yesterday_data);
          var_dump($before_yesterday_data);

            foreach ( $yesterday_data as $site){

                foreach ( $before_yesterday_data as $site_1){

                  if($site["media_id"] == $site_1["media_id"] && $site["product_id"] == $site_1["product_id"] ){
                      //$media_id = $diff["media_id"];
                      //echo "同じ:".$site["media_id"]."_".$site["product_id"]."<br>";
                      //echo $i."同じ:".$site["media_id"]."_".$site["product_id"]."_".$site["site_name"]."<br>";
                      $diff_data[$i]["imp"] = $site["imp"] - $site_1["imp"];
                      $diff_data[$i]["click"] = $site["click"] - $site_1["click"];
                      $diff_data[$i]["cv"] = $site["cv"] - $site_1["cv"];
                      $diff_data[$i]["ctr"] = 
                      ($diff_data[$i]["imp"] > 0 && $diff_data[$i]["click"] > 0 ) ? intval($diff_data[$i]["imp"])/intval($diff_data[$i]["click"]): 0 ;
                      $diff_data[$i]["cvr"] = 
                      ($diff_data[$i]["click"] > 0 && $diff_data[$i]["cv"] > 0 )? intval($diff_data[$i]["click"])/intval($diff_data[$i]["cv"]): 0 ;
                      $diff_data[$i]["estimate_cv"] = $site["estimate_cv"];
                      $diff_data[$i]["price"] = $site["price"] - $site_1["price"];
                      $diff_data[$i]["cpa"] = $site["cpa"];
                      $diff_data[$i]["cost"] = $site["cost"] - $site_1["cost"];
                      $diff_data[$i]["media_id"] = $site["media_id"];
                      $diff_data[$i]["site_name"] = $site["site_name"];
                      $diff_data[$i]["date"] = $site["date"];
                      $diff_data[$i]["product_id"] = $site["product_id"];
                 
                    $i++;
                    break;
                  }

              }
            }
            foreach ( $yesterday_data as $site){

                    if(!in_array($site["media_id"]."_".$site["product_id"], $before_yesterday_site_id)){
                        //echo $i."Diff:".$site["media_id"]."_".$site["product_id"]."_".$site["site_name"]."<br>";
                        $diff_data[$i]["imp"] = $site["imp"];
                        $diff_data[$i]["click"] = $site["click"];
                        $diff_data[$i]["cv"] = $site["cv"];
                        $diff_data[$i]["ctr"] = 
                        ($diff_data[$i]["imp"] > 0 && $diff_data[$i]["click"] > 0 ) ? intval($diff_data[$i]["imp"])/intval($diff_data[$i]["click"]): 0 ;
                        $diff_data[$i]["cvr"] = 
                        ($diff_data[$i]["click"] > 0 && $diff_data[$i]["cv"] > 0 )? intval($diff_data[$i]["click"])/intval($diff_data[$i]["cv"]): 0 ;
                        $diff_data[$i]["estimate_cv"] = $site["estimate_cv"];
                        $diff_data[$i]["price"] = $site["price"];
                        $diff_data[$i]["cpa"] = $site["cpa"];
                        $diff_data[$i]["cost"] = $site["cost"] ;
                        $diff_data[$i]["media_id"] = $site["media_id"];
                        $diff_data[$i]["site_name"] = $site["site_name"];
                        $diff_data[$i]["date"] = $site["date"];
                        $diff_data[$i]["product_id"] = $site["product_id"];
                        $i++;
                    }
            }
        }else{
          //１日のデータ
            $diff_data= $yesterday_data ;
        }

        foreach ($diff_data as $insert_diff) {
          
          $insert_diff = json_decode(json_encode($insert_diff), True );

            // DailySiteDiff::create(
            //     [
            //       'imp' => $insert_diff["imp"],
            //       'ctr' => $insert_diff["ctr"],
            //       'click' => $insert_diff["click"],
            //       'cv' => $insert_diff["cv"],
            //       'cvr' => $insert_diff["cvr"],
            //       'media_id' => $insert_diff["media_id"],
            //       'site_name' => $insert_diff["site_name"],
            //       'price' => $insert_diff["price"],
            //       'cpa' => $insert_diff["cpa"],
            //       'cost' => $insert_diff["cost"],
            //       'date' => $insert_diff["date"],
            //       'estimate_cv' => $insert_diff["estimate_cv"],
            //       'product_id' => $insert_diff["product_id"]
            //     ]
            // );
            //DailySiteDiff::updateOrCreate(

            $dailysites_table = date('Ym',strtotime('-1 day')).'_daily_site_diffs';
            
            DB::table($dailysites_table)->updateOrInsert(
              [
                  'media_id' => $insert_diff["media_id"],
                  'date' => $insert_diff["date"],
                  'product_id' => $insert_diff["product_id"]
              ],
              [
                  'imp' => $insert_diff["imp"],
                  'ctr' => $insert_diff["ctr"],
                  'click' => $insert_diff["click"],
                  'cv' => $insert_diff["cv"],
                  'cvr' => $insert_diff["cvr"],
                  'site_name' => $insert_diff["site_name"],
                  'price' => $insert_diff["price"],
                  'cpa' => $insert_diff["cpa"],
                  'cost' => $insert_diff["cost"],
                  'estimate_cv' => $insert_diff["estimate_cv"], 
                  'created_at' =>  \Carbon\Carbon::now(),
                  'updated_at' => \Carbon\Carbon::now()
              ]
            );
        }

    }
}
