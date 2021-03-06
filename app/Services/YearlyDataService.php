<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
use App\Asp;
use App\Monthlydata;
use DB;

class YearlyDataService
{
      public function calChart($product){
        //select date ,sum(case when monthlydatas.asp_id='3' then cv else 0 end) as 'Value commerce', sum(case when monthlydatas.asp_id='1' then cv else 0 end) as 'A8', sum(case when monthlydatas.asp_id='6' then cv else 0 end) as 'Felmat',SUM(cv) as "合計" ,sum(case when monthlydatas.asp_id='6' then cv else 0 end) as 'Felmat',SUM(cv) as "合計"  from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = 47 and `date` in ('2020-02-29', '2020-01-31') group by `date`
        $date = [];
        $yearly_chart = [];

        $aspinfo = Product::Select('products.id','asp_id','asps.name')->join('asps','products.asp_id','=','asps.id')->where('product_base_id',$product)->get()->toArray();
        //var_dump($aspinfo);
        for ($i = 1 ; $i <= 12 ; $i++ ) {
            array_push($date, date('Y-m-t',strtotime('-'.$i.' month')) ); 
        }
        // var_dump($aspinfo);
        if(!empty($aspinfo)){
            $select = 'date ,';
            foreach( $aspinfo as $val){
                $select .= "sum(case when monthlydatas.asp_id='".$val['asp_id']."' then cv else 0 end) as '".$val['name']."'";
                if($val !== end($aspinfo)) {
                    $select .= ', ';
                }else{
                    $select .= ',SUM(cv) as "合計" ';
                    
                }
            }

            //var_dump($select);
            $yearly_chart =Monthlydata::select(DB::raw($select));
            $yearly_chart->join('products','monthlydatas.product_id','=','products.id');
            $yearly_chart->where('product_base_id',$product);
            $yearly_chart->whereIn('date',$date);
            $yearly_chart->groupBy('date');
            $sql = $yearly_chart->toSql();
            // var_dump($sql);
            $yearly_chart = $yearly_chart->get()->toArray();
            $i = 0;
        }
        return json_encode($yearly_chart);

    /*
        SELECT date ,
    date ,sum(case when monthlydatas.asp_id='3' then cv else 0 end) as 'Value commerce',sum(case when monthlydatas.asp_id='1' then cv else 0 end) as 'A8',sum(case when monthlydatas.asp_id='5' then cv else 0 end) as 'Rentracks',sum(case when monthlydatas.asp_id='7' then cv else 0 end) as 'AffiTown',sum(case when monthlydatas.asp_id='8' then cv else 0 end) as 'TrafficGate',sum(case when monthlydatas.asp_id='9' then cv else 0 end) as 'SCAN',sum(case when monthlydatas.asp_id='4' then cv else 0 end) as 'Afb'
    FROM  monthlydatas join products on monthlydatas.product_id = products.id WHERE product_base_id = 3 and date in ('2019-06-30' ) GROUP by product_base_id

    */

    } 
    public function showAllList($id): array
    {
        $yearly_cvs = [];
        $yearly_clicks = [];
        $yearly_imps = [];
        $yearly_approvals = [];
        $yearly_cvrs = [];
        $yearly_ctrs = [];
        $yearly_cvs_asp = [];
        $yearly_clicks_asp = [];
        $yearly_imps_asp = [];
        $yearly_ctrs_asp = [];
        $yearly_cvrs_asp = [];

        //成果発生数
        $select = ''; 
        for( $i=1 ; $i <= 12 ; $i++ ){
            $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
            $month = date("Y年m月", strtotime('-'.$i.' month'));
            $select .= "sum(case when date='".$last_date."' then cv else 0 end) as '".$i."'";
            if($i != 12){
                $select .= ',';
            }
        }
        $yearly_cvs =Monthlydata::select(DB::raw($select));
        $yearly_cvs->join('products','monthlydatas.product_id','=','products.id');
        $yearly_cvs->where('product_base_id',$id);
        $yearly_cvs->groupBy('product_base_id');
        $yearly_cvs = $yearly_cvs->get()->toArray();
        $yearly_cvs = (!empty($yearly_cvs[0]))? array_reverse(array_values($yearly_cvs[0])) : '';

//クリック数
    $select = '';
    for( $i=1 ; $i <= 12 ; $i++ ){
        $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
        $month = date("Y年m月", strtotime('-'.$i.' month'));
        $select .= "sum(case when date='".$last_date."' then click else 0 end) as '".$i."'";
        if($i != 12){
            $select .= ',';
        }
    }
    $yearly_clicks =Monthlydata::select(DB::raw($select));
    $yearly_clicks->join('products','monthlydatas.product_id','=','products.id');
    $yearly_clicks->where('product_base_id',$id);
    $yearly_clicks->groupBy('product_base_id');
    $yearly_clicks = $yearly_clicks->get()->toArray();
    $yearly_clicks = (!empty($yearly_clicks[0]))? array_reverse(array_values($yearly_clicks[0])) : '';

//Imp数
    $select = '';
    for( $i=1 ; $i <= 12 ; $i++ ){
        $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
        $month = date("Y年m月", strtotime('-'.$i.' month'));
        $select .= "sum(case when date='".$last_date."' then imp else 0 end) as '".$i."'";
        if($i != 12){
            $select .= ',';
        }
    }
    $yearly_imps =Monthlydata::select(DB::raw($select));
    $yearly_imps->join('products','monthlydatas.product_id','=','products.id');
    $yearly_imps->where('product_base_id',$id);
    $yearly_imps->groupBy('product_base_id');
    $yearly_imps = $yearly_imps->get()->toArray();
    $yearly_imps = (!empty($yearly_imps[0]))? array_reverse(array_values($yearly_imps[0])) : '';
//承認数
    $select = '';
    for( $i=1 ; $i <= 12 ; $i++ ){
        $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
        $month = date("Y年m月", strtotime('-'.$i.' month'));
        $select .= "sum(case when date='".$last_date."' then approval else 0 end) as '".$i."'";
        if($i != 12){
            $select .= ',';
        }
    }
    $yearly_approvals =Monthlydata::select(DB::raw($select));
    $yearly_approvals->join('products','monthlydatas.product_id','=','products.id');
    $yearly_approvals->where('product_base_id',$id);
    $yearly_approvals->groupBy('product_base_id');
    $yearly_approvals = $yearly_approvals->get()->toArray();
    $yearly_approvals = (!empty($yearly_approvals[0]))? array_reverse(array_values($yearly_approvals[0])) : '';
//承認率
    $select = '';
    for( $i=1 ; $i <= 12 ; $i++ ){
        $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
        $month = date("Y年m月", strtotime('-'.$i.' month'));
        $select .= "sum(case when date='".$last_date."' then approval_rate else 0 end) as '".$i."'";
        if($i != 12){
            $select .= ',';
        }
    }
    $yearly_approval_rates =Monthlydata::select(DB::raw($select));
    $yearly_approval_rates->join('products','monthlydatas.product_id','=','products.id');
    $yearly_approval_rates->where('product_base_id',$id);
    $yearly_approval_rates->groupBy('product_base_id');
    $yearly_approval_rates = $yearly_approval_rates->get()->toArray();
    $yearly_approval_rates = (!empty($yearly_approval_rates[0]))? array_reverse(array_values($yearly_approval_rates[0])) : '';

//CTR
    if(!empty($yearly_imps)){
        foreach ($yearly_imps as $key => $value) {

            $yearly_ctrs[$key] = ($yearly_clicks[$key]!=0 || $value!=0)? $yearly_clicks[$key] / $value * 100 : 0 ; 
        
        }
    }
//CVR
    if(!empty($yearly_clicks)){
        foreach ($yearly_clicks as $key => $value) {

            $yearly_cvrs[$key] = ($yearly_cvs[$key]!=0 || $value!=0)? $yearly_cvs[$key] / $value * 100 : 0 ; 
        
        }
    }
    //$product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();

    //グラフ数値
    $chart_data = Monthlydata::select(['name', 'imp', 'click','cv'])
    ->join('products','monthlydatas.product_id','=','products.id')
    ->join('asps','products.asp_id','=','asps.id');

    if(!empty($id)){
        $chart_data->where('products.product_base_id', $id);
    }
    if(!empty($searchdate)){
        $chart_data->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
    }
    $chart_data = $chart_data->get();
    //var_dump($asps);
    //各ASP毎の年間数値
    $asps = Product::Select('asp_id','name')->join('asps','products.asp_id','=','asps.id')->where('product_base_id', $id)->where('products.killed_flag',0)->get()->toArray();

    foreach ($asps as $asp) {
        //echo $asp["asp_id"];
    //成果発生数
            $key = $asp["asp_id"];
            $select = '';

            for( $i=1 ; $i <= 12 ; $i++ ){
                $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                $month = date("Y年m月", strtotime('-'.$i.' month'));
                $select .= "sum(case when date='".$last_date."' then cv else 0 end) as '".$i."'";
                if($i != 12){
                    $select .= ',';
                }
            }
            $yearly_cvs_asp[$key] =Monthlydata::select(DB::raw($select));
            $yearly_cvs_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
            $yearly_cvs_asp[$key]->where('product_base_id',$id);
            $yearly_cvs_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
            $yearly_cvs_asp[$key]->groupBy('product_base_id');
            $yearly_cvs_asp[$key] = $yearly_cvs_asp[$key]->get()->toArray();

            $yearly_cvs_asp[$key] = (!empty($yearly_cvs_asp[$key]))? array_reverse(array_values($yearly_cvs_asp[$key][0])) : '';
            
    //クリック数
            $select = '';
            for( $i=1 ; $i <= 12 ; $i++ ){
                $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                $month = date("Y年m月", strtotime('-'.$i.' month'));
                $select .= "sum(case when date='".$last_date."' then click else 0 end) as '".$i."'";
                if($i != 12){
                    $select .= ',';
                }
            }
            $yearly_clicks_asp[$key] =Monthlydata::select(DB::raw($select));
            $yearly_clicks_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
            $yearly_clicks_asp[$key]->where('product_base_id',$id);
            $yearly_clicks_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
            $yearly_clicks_asp[$key]->groupBy('product_base_id');
            $yearly_clicks_asp[$key] = $yearly_clicks_asp[$key]->get()->toArray();
            $yearly_clicks_asp[$key] = (!empty($yearly_clicks_asp[$key]))?  array_reverse(array_values($yearly_clicks_asp[$key][0])) : '';

    //Imp数
            $select = '';
            for( $i=1 ; $i <= 12 ; $i++ ){
                $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                $month = date("Y年m月", strtotime('-'.$i.' month'));
                $select .= "sum(case when date='".$last_date."' then imp else 0 end) as '".$i."'";
                if($i != 12){
                    $select .= ',';
                }
            }
            $yearly_imps_asp[$key] =Monthlydata::select(DB::raw($select));
            $yearly_imps_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
            $yearly_imps_asp[$key]->where('product_base_id',$id);
            $yearly_imps_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
            $yearly_imps_asp[$key]->groupBy('product_base_id');
            $yearly_imps_asp[$key] = $yearly_imps_asp[$key]->get()->toArray();
            $yearly_imps_asp[$key] = (!empty($yearly_imps_asp[$key]))? array_reverse(array_values($yearly_imps_asp[$key][0])) : '';
    //承認数
            $select = '';
            for( $i=1 ; $i <= 12 ; $i++ ){
                $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                $month = date("Y年m月", strtotime('-'.$i.' month'));
                $select .= "sum(case when date='".$last_date."' then approval else 0 end) as '".$i."'";
                if($i != 12){
                    $select .= ',';
                }
            }
            $yearly_approvals_asp[$key] =Monthlydata::select(DB::raw($select));
            $yearly_approvals_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
            $yearly_approvals_asp[$key]->where('product_base_id',$id);
            $yearly_approvals_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
            $yearly_approvals_asp[$key]->groupBy('product_base_id');
            $yearly_approvals_asp[$key] = $yearly_approvals_asp[$key]->get()->toArray();
            $yearly_approvals_asp[$key] = (!empty($yearly_approvals_asp[$key]))? array_reverse(array_values($yearly_approvals_asp[$key][0])) : '';
    //承認率
            $select = '';
            for( $i=1 ; $i <= 12 ; $i++ ){
                $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                $month = date("Y年m月", strtotime('-'.$i.' month'));
                $select .= "sum(case when date='".$last_date."' then approval_rate else 0 end) as '".$i."'";
                if($i != 12){
                    $select .= ',';
                }
            }
            $yearly_approval_rates_asp[$key] =Monthlydata::select(DB::raw($select));
            $yearly_approval_rates_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
            $yearly_approval_rates_asp[$key]->where('product_base_id',$id);
            $yearly_approval_rates_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
            $yearly_approval_rates_asp[$key]->groupBy('product_base_id');
            $yearly_approval_rates_asp[$key] = $yearly_approval_rates_asp[$key]->get()->toArray();
            $yearly_approval_rates_asp[$key] = (!empty($yearly_approval_rates_asp[$key]))? array_reverse(array_values($yearly_approval_rates_asp[$key][0])) : '';

    //CTR
        if(!empty($yearly_imps_asp[$key])){
            foreach ($yearly_imps_asp[$key] as $k => $val) {
                //var_dump($val);
                $val1 = (integer)$yearly_clicks_asp[$key][$k];
                $val2 = (integer)$val;
                $yearly_ctrs_asp[$key][$k] = ($val1 == 0 || $val2 ==0 )? 0 : $val1 / $val2 * 100; 
            }
        }
    //CVR
        if(!empty($yearly_clicks_asp[$key])){
            foreach ($yearly_clicks_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_cvs_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_cvrs_asp[$key][$k] = ($val1 == 0 || $val2 == 0 )? 0 : $val1 / $val2 * 100;
                
                }
        }   
    }
        \Log::debug($yearly_cvs);
        \Log::debug($yearly_clicks);
        \Log::debug($yearly_imps);
        \Log::debug($yearly_approvals);
        \Log::debug($yearly_cvrs);
        \Log::debug($yearly_ctrs);
        \Log::debug($yearly_cvs_asp);
        \Log::debug($yearly_clicks_asp);
        \Log::debug($yearly_imps_asp);
        \Log::debug($yearly_ctrs_asp);
        \Log::debug($yearly_cvrs_asp);
        
        return [$yearly_cvs,$yearly_clicks,$yearly_imps,$yearly_approvals,$yearly_cvrs,$yearly_ctrs,$yearly_cvs_asp,$yearly_clicks_asp,$yearly_imps_asp,$yearly_ctrs_asp,$yearly_cvrs_asp];
    }
}