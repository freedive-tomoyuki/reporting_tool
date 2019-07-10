<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
//use App\Dailydata;
use App\Dailyestimate;
//use App\DailyTotal;
use App\DailyEstimateTotal;
use App\ProductBase;
use App\Product;
use App\Asp;
use App\Monthlydata;
use App\Monthlysite;
use App\Http\Requests\SearchYearlyRequest;
use DB;

class YearlyController extends Controller
{
    /**
    　認証確認
    */
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
    }

    /**
        月次の基本データ表示（デフォルト）
    */
    public function yearly_result() {
        $user = Auth::user();

         
        $this_month = date("Ym"); 
        $select = ''; 
//成果発生数
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
        $yearly_cvs->where('product_base_id',3);
        $yearly_cvs->groupBy('product_base_id');
        $yearly_cvs = $yearly_cvs->get()->toArray();
        $yearly_cvs = array_reverse(array_values($yearly_cvs[0]));

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
        $yearly_clicks->where('product_base_id',3);
        $yearly_clicks->groupBy('product_base_id');
        $yearly_clicks = $yearly_clicks->get()->toArray();
        $yearly_clicks = array_reverse(array_values($yearly_clicks[0]));
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
        $yearly_imps->where('product_base_id',3);
        $yearly_imps->groupBy('product_base_id');
        $yearly_imps = $yearly_imps->get()->toArray();
        $yearly_imps = array_reverse(array_values($yearly_imps[0]));
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
        $yearly_approvals->where('product_base_id',3);
        $yearly_approvals->groupBy('product_base_id');
        $yearly_approvals = $yearly_approvals->get()->toArray();
        $yearly_approvals = array_reverse(array_values($yearly_approvals[0]));
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
        $yearly_approval_rates->where('product_base_id',3);
        $yearly_approval_rates->groupBy('product_base_id');
        $yearly_approval_rates = $yearly_approval_rates->get()->toArray();
        $yearly_approval_rates = array_reverse(array_values($yearly_approval_rates[0]));

//CTR
        foreach ($yearly_imps as $key => $value) {

            $yearly_ctrs[$key] = ($yearly_clicks[$key]!=0 || $value!=0)? $yearly_clicks[$key] / $value * 100 : 0 ; 
        
        }
//CVR
        foreach ($yearly_clicks as $key => $value) {

            $yearly_cvrs[$key] = ($yearly_cvs[$key]!=0 || $value!=0)? $yearly_cvs[$key] / $value * 100 : 0 ; 
        
        }
        $product_bases = ProductBase::all();
        $asps = Asp::all();

        //グラフ数値
        $chart_data = Monthlydata::select(['name', 'imp', 'click','cv','date'])
                ->join('products','monthlydatas.product_id','=','products.id')
                ->join('asps','products.asp_id','=','asps.id')
                ->where('products.product_base_id', 3)
                ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                ->get();
        //各ASP毎の年間数値
        $asps = Product::Select('asp_id','name')->join('asps','products.asp_id','=','asps.id')->where('product_base_id', 3)->where('products.killed_flag',0)->get()->toArray();

        foreach ($asps as $asp) {
            echo $asp["asp_id"];
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
                $yearly_cvs_asp[$key]->where('product_base_id',3);
                $yearly_cvs_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_cvs_asp[$key]->groupBy('product_base_id');
                $yearly_cvs_asp[$key] = $yearly_cvs_asp[$key]->get()->toArray();
                $yearly_cvs_asp[$key] = array_reverse(array_values($yearly_cvs_asp[$key][0]));

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
                $yearly_clicks_asp[$key]->where('product_base_id',3);
                $yearly_clicks_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_clicks_asp[$key]->groupBy('product_base_id');
                $yearly_clicks_asp[$key] = $yearly_clicks_asp[$key]->get()->toArray();
                $yearly_clicks_asp[$key] = array_reverse(array_values($yearly_clicks_asp[$key][0]));

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
                $yearly_imps_asp[$key]->where('product_base_id',3);
                $yearly_imps_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_imps_asp[$key]->groupBy('product_base_id');
                $yearly_imps_asp[$key] = $yearly_imps_asp[$key]->get()->toArray();
                $yearly_imps_asp[$key] = array_reverse(array_values($yearly_imps_asp[$key][0]));
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
                $yearly_approvals_asp[$key]->where('product_base_id',3);
                $yearly_approvals_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_approvals_asp[$key]->groupBy('product_base_id');
                $yearly_approvals_asp[$key] = $yearly_approvals_asp[$key]->get()->toArray();
                $yearly_approvals_asp[$key] = array_reverse(array_values($yearly_approvals_asp[$key][0]));
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
                $yearly_approval_rates_asp[$key]->where('product_base_id',3);
                $yearly_approval_rates_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_approval_rates_asp[$key]->groupBy('product_base_id');
                $yearly_approval_rates_asp[$key] = $yearly_approval_rates_asp[$key]->get()->toArray();
                $yearly_approval_rates_asp[$key] = array_reverse(array_values($yearly_approval_rates_asp[$key][0]));

                foreach ($yearly_imps_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_clicks_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_ctrs_asp[$key][$k] = ($val1 == 0 || $val2 ==0 )? 0 : $val1 / $val2 * 100; 
                
                }
        //CVR
                foreach ($yearly_clicks_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_cvs_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_cvrs_asp[$key][$k] = ($val1 == 0 || $val2 == 0 )? 0 : $val1 / $val2 * 100;
                
                }

        }
        
        return view('admin.yearly',compact('user','product_bases','asps','yearly_cvs','yearly_clicks','yearly_imps','yearly_approvals','yearly_cvrs','yearly_ctrs',
                'yearly_cvs_asp','yearly_clicks_asp','yearly_imps_asp','yearly_ctrs_asp','yearly_cvrs_asp'));
    }
    /**
        月次の基本データ表示（検索後）
    */
	public function yearly_result_search(SearchYearlyRequest $request) {
        $request->flash();
        $user = Auth::user();
		$id = ($request->product != null)? $request->product : 3 ;
         
        $this_month = date("Ym"); 
        $select = ''; 
//成果発生数
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
        $yearly_cvs = array_reverse(array_values($yearly_cvs[0]));

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
        $yearly_clicks = array_reverse(array_values($yearly_clicks[0]));

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
        $yearly_imps = array_reverse(array_values($yearly_imps[0]));
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
        $yearly_approvals = array_reverse(array_values($yearly_approvals[0]));
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
        $yearly_approval_rates = array_reverse(array_values($yearly_approval_rates[0]));

//CTR
        foreach ($yearly_imps as $key => $value) {

            $yearly_ctrs[$key] = ($yearly_clicks[$key]!=0 || $value!=0)? $yearly_clicks[$key] / $value * 100 : 0 ; 
        
        }
//CVR
        foreach ($yearly_clicks as $key => $value) {

            $yearly_cvrs[$key] = ($yearly_cvs[$key]!=0 || $value!=0)? $yearly_cvs[$key] / $value * 100 : 0 ; 
        
        }
        $product_bases = ProductBase::all();

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
            echo $asp["asp_id"];
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
                $yearly_cvs_asp[$key] = array_reverse(array_values($yearly_cvs_asp[$key][0]));

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
                $yearly_clicks_asp[$key] = array_reverse(array_values($yearly_clicks_asp[$key][0]));

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
                $yearly_imps_asp[$key] = array_reverse(array_values($yearly_imps_asp[$key][0]));
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
                $yearly_approvals_asp[$key] = array_reverse(array_values($yearly_approvals_asp[$key][0]));
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
                $yearly_approval_rates_asp[$key] = array_reverse(array_values($yearly_approval_rates_asp[$key][0]));

        //CTR
                foreach ($yearly_imps_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_clicks_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_ctrs_asp[$key][$k] = ($val1 == 0 || $val2 ==0 )? 0 : $val1 / $val2 * 100; 
                
                }
        //CVR
                foreach ($yearly_clicks_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_cvs_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_cvrs_asp[$key][$k] = ($val1 == 0 || $val2 == 0 )? 0 : $val1 / $val2 * 100;
                
                }
        }

        return view('admin.yearly',compact('user','asps','product_bases','yearly_cvs','yearly_clicks','yearly_imps','yearly_approvals','yearly_cvrs','yearly_ctrs','yearly_cvs_asp','yearly_clicks_asp','yearly_imps_asp','yearly_ctrs_asp','yearly_cvrs_asp'));

    }

}
