<?php

namespace App\Services;

use DB;
use App\Product;
use App\Monthlydata;
use App\DailyDiff;
use App\DailySiteDiff;
use App\Schedule;
//use DailySearchService;

class MonthlySearchService
{

    private $product;

    public function startFunc($product)
    {
        
        $aspRow = array();
        $asp_array = array();

        $asp_name = $this->filterAsp($product);
        //var_dump($asp_name);
        $asp_array = json_decode($asp_name,true);

        $asp_array = array_unique($asp_array, SORT_REGULAR);
        var_dump($asp_array);
        foreach($asp_array as $name){
            $functionName = str_replace(' ', '' ,mb_strtolower($name["name"]));
            $className = 'App\Http\Controllers\Admin\Asp\Monthly'. '\\'.str_replace(' ', '' ,$name["name"]).'Controller';
            $run = new $className();

            $run->{$functionName}($product);
        }

    }

    /**
    * 親案件IDとASPIDから案件IDを取得
    */
    public function BasetoProduct($asp_id, $baseproduct){
        $converter = Product::select();
        $converter->where('product_base_id', $baseproduct);
        $converter->where('asp_id', $asp_id );
        $converter->where('killed_flag', 0 );
        $converter = $converter->get()->toArray();
        return $converter[0]["id"];
    } 
    /**
    * 案件IDからASPを取得
    */
    public function filterAsp( $product_id ){
      $target_asp = Product::select('asp_id','name')
                  ->join('asps','products.asp_id','=','asps.id')
                  ->where('product_base_id', $product_id )
                  ->where('products.killed_flag', 0 )
                  ->get();

      return json_encode($target_asp);
    }
    /**
    *　ASPフィー＋FDフィー算出用の関数
    */
    public function calc_approval_price($approval_price ,$asp){
      $calData = array();
      /**
      *  A8の場合の算出
      */
      if( $asp == 1 ){
        $asp_fee = ($approval_price*1.08)+($approval_price*1.08*0.3);//FDグロス
        $total = $asp_fee * 1.08 * 1.2;
      }
      /**
      *  それ以外のASPの場合の算出
      */
      else{
        $total = $approval_price * 1.3;//FDグロス
      }
      //$calData['cost'] = $total;

      return $total;
    }
  /**
  　承認率算出
  */
    public function calc_approval_rate($product = null,$t_date = null){

        $before_one_months = date('Y-m-t', strtotime('last day of '.date('Y-m-01', strtotime('-1 month'))));
        $before_two_months = date('Y-m-t', strtotime('last day of '.date('Y-m-01', strtotime('-2 month'))));

        $dates = [ $t_date , $before_one_months , $before_two_months ];
        //var_dump($dates);
        
        $approval_rate = Monthlydata::select(DB::raw("sum(cv) as total_cv,sum(approval) as total_approval,sum(approval)/sum(cv)*100 as rate,product_id"))
                ->where('product_id',$product)
                ->where(function($approval_rate) use($dates){
                  foreach($dates as $date){
                    $approval_rate->orWhere('date', '=' , $date);
                  }
                });
          //$approval_rate->orwhere('date' , $date );
          
          $approval_rate = $approval_rate->groupby('product_id')->get()->toArray();
          //$approval_rate = json_decode(json_encode($approval_rate[0]), true);
          echo '承認率変換前';
          var_dump($approval_rate);

          $rate = (empty($approval_rate[0]['rate']))? 0 : $approval_rate[0]['rate'];

          //$rate = (empty($approval_rate[0]['rate']))? 0 : $approval_rate[0]['rate'];
          return $rate;

    }
  /**
  　承認率算出(サイト)
  */

    public function calc_approval_rate_site($product_id = null, $t_date = null){
        if(date('Ym') != date('Ym',strtotime('-1 day'))){//１日のデータ取得の場合
          $monthlysites_table1 = date('Ym', strtotime('-1 day')).'_monthlysites';
          $monthlysites_table2 = date('Ym', strtotime(date('Y-m-01').'-2 month')).'_monthlysites';
 
        }else{
          $monthlysites_table1 = date('Ym', strtotime('-1 day')).'_monthlysites';
          $monthlysites_table2 = date('Ym', strtotime(date('Y-m-01').'-1 month')).'_monthlysites';
        }


        //$before_one_months = date('Ym', strtotime('last day of '.date('Y-m-01', strtotime('-1 month'))));
        //$before_two_months = date('Y-m-t', strtotime('last day of '.date('Y-m-01', strtotime('-2 month')))); 
        //$monthlysites_table3 = date('Ym', strtotime('-2 month')).'_monthlysites';
        $before_one_months = date('Y-m-t', strtotime('last day of '.date('Y-m-01', strtotime('-1 month'))));
        $before_two_months = date('Y-m-t', strtotime('last day of '.date('Y-m-01', strtotime('-2 month'))));

        //$dates = [ $t_date ,date('Y-m-t', strtotime('-1 month')),date('Y-m-t', strtotime('-2 month')) ];
        $dates = [ $t_date , $before_one_months , $before_two_months];

        $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();

        $product_array = Product::select('id');
          foreach ($products as $value) {
            $product_array->orWhere('product_base_id',$value);
          }
            $product_array = $product_array->get()->toArray();
          
        foreach($product_array as $product_id){

          $table1 = DB::table($monthlysites_table1)->select('cv', 'approval','product_id','media_id','date');
          $table2 = DB::table($monthlysites_table2)->select('cv', 'approval','product_id','media_id','date');

          $approval_table = $table1->union($table2)->toSql();//->union($table3);

          $approval_table = 
                DB::table(DB::raw("(".$approval_table.") as a "))
                  ->select(DB::raw("sum(cv) as total_cv,sum(approval) as total_approval,sum(approval)/sum(cv)*100 as rate,product_id,media_id"));

          $approval_table = $approval_table
                  ->where('product_id',$product_id)
                  //->where('media_id',$media_id);
                  ->where(function($approval_table) use($dates){
                    foreach($dates as $date){
                      $approval_table->orWhere('date', '=' , $date);
                    }
                  });
                  $approval_table->groupby('product_id');
                  $approval_table->groupby('media_id');
                  //$approval_table->get();
                  $approval_table = $approval_table->get()->toArray();

                  $approval_table = json_decode(json_encode($approval_table), true);
                  $count=0;
                  

                  foreach ($approval_table as $value) {

                          DB::table($monthlysites_table1)
                            ->where('product_id', $value['product_id'])
                            ->where('media_id', $value['media_id'])
                            ->where('date', date('Y-m-d',strtotime('-1 day')))
                            //var_dump($data1->get()->toArray());
                            ->update([
                                'approval_rate' => (empty($value['rate']))? 0 : $value['rate'],
                          ]);
                  }
        }
          //return json_encode($approval_table);

    }
/**
*　月次の基本データの保存。日毎の承認件数、承認金額のアップロード

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

        $rate = $this->calc_approval_rate($data_array[0]['product'],$data_array[0]['date']);
        //echo '承認率'.$rate ;

        DB::table('monthlydatas')
          ->where('product_id', $data_array[0]['product'])
          ->where('date', $data_array[0]['date'])
          ->update([
              'approval_rate' => $rate,
          ]);

    }

/**
*　月次のサイト別データの保存。日毎の承認件数、承認金額のアップロード
*/
    public function save_site($data){
        
        $data_array = json_decode(json_encode(json_decode($data)), True );

        $product_id = 0;
        $product_array = array();

        foreach($data_array as $data ){
            
            $month = date('Ym', strtotime($data['date']));
            $monthlysites_table = $month.'_monthlysites';
        
            DB::table($monthlysites_table)
              ->where('product_id', $data['product'])
              ->where('media_id', $data['media_id'])
              ->where('date', $data['date'])
              ->update([
                  'approval_price' => $data['approval_price'],
                  'approval' => $data['approval'],
            ]);

            if( $product_id != $data['product']){
               array_push($product_array, $data['product']);
               $product_id = $data['product'];
            }
        }
        
    }
}
