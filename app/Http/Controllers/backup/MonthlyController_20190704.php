<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Dailydata;
use App\Dailyestimate;
use App\DailyTotal;
use App\DailyEstimateTotal;
use App\ProductBase;
use App\Asp;
use App\Monthlydata;
use App\Monthlysite;
use App\MonthlyTotal;
use DB;

class MonthlyController extends Controller
{
    /**
    　認証確認
    */
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:user');
    }
    /**
        月次の基本データ表示（デフォルト）
    */
    public function monthly_result() {
        $user = Auth::user();

        $products = Monthlydata::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','monthlydatas.created_at','products.product','products.id','price','cpa','cost','approval','approval_price','last_cv'])
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = 3 and `monthlydatas`.`date` like '".date('Y-m-t', strtotime('-1 month'))."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id')
                    ->where('products.product_base_id', $user->id)
                    ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();//->toArray();

        $productsTotals = Monthlydata::select(DB::raw("date, monthlydatas.product_id,sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(cost) as total_cost,sum(price) as total_price ,sum(approval) as total_approval, sum(approval_price) as total_approval_price,sum(last_cv) as total_last_cv"))
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = 3 and `monthlydatas`.`date` like '".date('Y-m-t', strtotime('-1 month'))."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id')
                    ->where('product_base_id', $user->id)
                    ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%");
                    //echo $productsTotals->toSql();
                    $productsTotals = $productsTotals->get();

        /*            
        $productsTotals = MonthlyTotal::select([ 'total_imp','total_click','total_cv','total_cost','total_price','total_cpa','total_approval','total_approval_price'])
                    ->join('product_bases','monthly_totals.product_base_id','=','product_bases.id')
                    ->where('product_bases.id', 3)
                    ->where('monthly_totals.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();//->toArray();
        */
        $productsEstimates = Dailyestimate::select([ 'asps.name','products.id','dailyestimates.asp_id','estimate_imp', 'estimate_click','estimate_cv','dailyestimates.created_at','estimate_cvr','estimate_ctr','estimate_cost','estimate_price','estimate_cpa','date'])
                    ->join('products','dailyestimates.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('products.product_base_id', $user->id)
                    ->where('dailyestimates.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();//->toArray();

        $productsEstimateTotals = DailyEstimateTotal::select([ 'estimate_total_imp','estimate_total_click','estimate_total_cv','estimate_total_cvr', 'estimate_total_ctr','estimate_total_cost','estimate_total_price','estimate_total_cpa'])
                    ->join('product_bases','daily_estimate_totals.product_base_id','=','product_bases.id')
                    ->where('product_bases.id', $user->id)
                    ->where('daily_estimate_totals.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();//->toArray();

        $product_bases = ProductBase::all();
        $asps = Asp::all();

        //var_dump($products);

        //グラフ数値
        $chart_data = Monthlydata::select(['name', 'imp', 'click','cv','date'])
                ->join('products','monthlydatas.product_id','=','products.id')
                ->join('asps','products.asp_id','=','asps.id')
                ->where('products.product_base_id', $user->id)
                ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                ->get();

        //var_dump($chart_data);
        //echo $chart_sql;

        if( $products->isEmpty() ){
        	return view('daily_error',compact('product_bases','asps','user'));
        }else{
        	return view('monthly',compact('products','product_bases','asps','productsEstimates','productsEstimateTotals','productsTotals','user','chart_data'));
        }
    }
    /**
        月次の基本データ表示（検索後）
    */

	public function monthly_result_search(Request $request) {

        $user = Auth::user();
		$id = ($request->product != null)? $request->product : $user->id ;
        $month =($request->month != null)? $request->month : date("Y-m");

        //検索日時
        //今月の場合　Dateが昨日付け
        //先月以前の場合　Dateが末日付け
        if( $month == date("Y-m")){
            $searchdate = date("Y-m-d", strtotime('-1 day'));
            $search_last_date = date("Y-m-t", strtotime('-1 month'));
            //$month = date('Y-m',strtotime('-1 month'));
            //$search_last_date = date('Y-m-t', strtotime('last day of ' . $month));
        }else{
            $searchdate = date('Y-m-d', strtotime('last day of ' . $month));
            $month = date('Y-m',strtotime('-1 month'));
            $search_last_date = date('Y-m-t', strtotime('last day of ' . $month));
        }

        $request->flash();
        /**
            当月の実績値
        */
        $products = Monthlydata::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','monthlydatas.created_at','products.product','products.id','price','cpa','cost','approval','approval_price','last_cv'])
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$search_last_date."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id');

                    //->where('product_base_id', 1)
                    //->where('dailydatas.created_at', 'LIKE' , "%".date("Y-m")."%")

                    if(!empty($id)){
                        $products->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $products->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }

                    $products = $products->get();//->toArray();

                    //echo '<pre>';
                    //var_dump($products->toArray());
                    //echo '</pre>';

         /**
            当月の実績値トータル
        */
/*
        $productsTotals = MonthlyTotal::select([ 'total_imp','total_click','total_cv','total_cvr', 'total_ctr','total_cost','total_price','total_cpa'])
                    ->join('product_bases','monthly_totals.product_base_id','=','product_bases.id');
                    
                    if(!empty($id)){
                        $productsTotals->where('product_bases.id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsTotals->where('monthly_totals.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsTotals = $productsTotals->get();
*/
        $productsTotals = Monthlydata::select(DB::raw("date, product_id,sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price ,sum(cost) as total_cost,sum(approval) as total_approval, sum(approval_price) as total_approval_price"))
                   ->join('products','monthlydatas.product_id','=','products.id')
                   ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$search_last_date."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id');

                    if(!empty($id)){
                        $productsTotals->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsTotals->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsTotals = $productsTotals->get();


/*                    
                    echo '<pre>';
                    var_dump($productsTotals->toArray());
                    echo '</pre>';

 */                   //$products = $products->get();
         /**
            当月の着地想定
        */
        $productsEstimates = Dailyestimate::select([ 'asps.name','products.id','dailyestimates.asp_id','estimate_imp', 'estimate_click','estimate_cv','dailyestimates.created_at','estimate_cvr','estimate_ctr','estimate_cost','estimate_price','estimate_cpa','date'])
                    ->join('products','dailyestimates.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    //->where('product_base_id', 1)
                    //->where('dailyestimates.created_at', 'LIKE' , "%".date("Y-m")."%")
                    if(!empty($id)){
                        $productsEstimates->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsEstimates->where('dailyestimates.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsEstimates = $productsEstimates->get();//->toArray();

/*                    echo '<pre>';
                    var_dump($productsEstimates->toArray());
                    echo '</pre>';*/

                    //var_dump($productsEstimates);
         /**
            当月の着地想定トータル
        */
        $productsEstimateTotals = DailyEstimateTotal::select([ 'estimate_total_imp','estimate_total_click','estimate_total_cv','estimate_total_cvr', 'estimate_total_ctr','estimate_total_cost','estimate_total_price','estimate_total_cpa'])
                    ->join('product_bases','daily_estimate_totals.product_base_id','=','product_bases.id');
                    //->where('product_base_id', 1)
                    //->where('daily_estimate_totals.created_at', 'LIKE' , "%".date("Y-m")."%")
                    if(!empty($id)){
                        $productsEstimateTotals->where('product_bases.id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsEstimateTotals->where('daily_estimate_totals.date', 'LIKE' , "%".$searchdate."%");
                    }
                    //echo $searchdate;
                    $productsEstimateTotals = $productsEstimateTotals->get();//->toArray();
                    
/*                    
                    echo '<pre>';
                    var_dump($productsEstimateTotals->toArray());
                    echo '</pre>';
                    
*/

                    $product_bases = ProductBase::all();
                    $asps = Asp::all();
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

        if( $products->isEmpty() ){
        	return view('daily_error',compact('product_bases','asps','user'));
        }else{
        	return view('monthly',compact('products','product_bases','asps','productsEstimates','productsEstimateTotals','productsTotals','user','chart_data'));
        }
    }
    /**
    *サイト別デイリーレポートのデフォルトページを表示。
    *表示データがない場合、エラーページを表示する。
    *
    *@return view
    *
    */

    public function monthly_result_site() {

        $user = Auth::user();
        $month = date('Ym',strtotime('-1 day'));
        $monthly_sites_table = $month.'_monthlysites';

        $products = DB::table($monthly_sites_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', $user->id)
                    ->where('date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();
        /**
        * プロダクト一覧を全て取得
        */
        $product_bases = ProductBase::all();
        /**
        * ASP一覧を全て取得
        */  
        $asps = Asp::all();
        //var_dump($products);
        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        $site_ranking = $this->monthly_ranking_site($user->id,date("Y-m-d", strtotime('-1 day')));
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
            return view('daily_error',compact('product_bases','asps','user'));
        }else{
            return view('monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        }
    }
    /**
    *サイト別デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */

    public function monthly_result_site_search(Request $request) {
        $user = Auth::user();
        $id = ($request->product != null)? $request->product : $user->id ;
        $month =($request->month != null)? $request->month : date("Y-m-d", strtotime('-1 day'));
        //当月の場合
        if( $month == date("Y-m")|| $month == date("Y-m-d", strtotime('-1 day'))) {
            $searchdate = date("Y-m-d", strtotime('-1 day'));
        //当月以外の場合
        }else{
            $searchdate = date('Y-m-d', strtotime('last day of ' . $month));
        }
        //echo $searchdate;
        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;
        
        $request->flash();

        $month = date('Ym',strtotime($searchdate));
        $monthly_sites_table = $month.'_monthlysites';

        $products = DB::table($monthly_sites_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','monthlysites.created_at','products.product','products.id','price','cpa','cost','estimate_cv','approval','approval_price'])
                    ->join('products','monthlysites.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    
                    if(!empty($id)){
                        $products->where('products.product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate)){
                        $products->where('monthlysites.date', 'LIKE' , "%".$searchdate."%");
                    }
                    //->where('product_base_id', $id)
                    //->where('monthlysites.created_at', 'LIKE' , "%".$searchdate."%")
                    $products = $products->get();
                    //->toArray();
                    //->toSql();
                    //var_dump($products);
        /**
        * プロダクト一覧を全て取得
        */
        $product_bases = ProductBase::all();
        /**
        * ASP一覧を全て取得
        */  
        $asps = Asp::all();
        //var_dump($products);

        
        //echo $products->isEmpty();

        //echo $product_bases->isEmpty();
        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        $site_ranking = $this->monthly_ranking_site($id,$searchdate,$asp_id);
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
            return view('daily_error',compact('product_bases','asps','user'));
        }else{
            return view('monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        }


    }
    /**
　    月別ランキング一覧取得
    */
    public function monthly_ranking_site($id ,$searchdate = null,$asp_id=null) {

                /*
                    案件ｘ対象期間からCVがTOP10のサイトを抽出
                */
                $products = Monthlysite::select(DB::raw("cv , media_id, site_name"))
                    
                    ->join('products','monthlysites.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    
                    ->where('cv', '!=' , 0 );

                    if(!empty($id)){
                        $products->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products->where('products.asp_id', $asp_id);
                    }                    
                    if(!empty($searchdate)){
                        //今月の場合
                        if(strpos($searchdate,date("Y-m")) === false ){
                            $searchdate= date("Y-m-t",strtotime($searchdate));
                        }
                        $products->where('monthlysites.date' , $searchdate );
                    }
                    //echo $searchdate;
                    $products->groupBy("media_id");
                    $products->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');

                    $products->limit(10);
                    //echo $products->toSql();
                    $products = $products->get();

                    return json_encode($products);

    }

}
