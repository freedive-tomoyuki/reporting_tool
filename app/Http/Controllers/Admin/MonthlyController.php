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
use App\Asp;
use App\Monthlydata;
use App\Monthlysite;
use App\Http\Requests\SearchMonthlyRequest;
use App\Http\Requests\SearchMonthlySiteRequest;
//use App\MonthlyTotal;
use DB;

class MonthlyController extends Controller
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
    public function monthlyResult() {
        $user = Auth::user();

        $products = Monthlydata::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','monthlydatas.created_at','products.product','products.id','price','cpa','cost','approval','approval_price','approval_rate','last_cv'])
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = 3 and `monthlydatas`.`date` like '".date('Y-m-t', strtotime('-1 month'))."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id')
                    ->where('products.product_base_id', 3)
                    ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();//->toArray();

        $productsTotals = Monthlydata::select(DB::raw("date, product_id,sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price,sum(cost) as total_cost ,sum(approval) as total_approval, sum(approval_price) as total_approval_price, sum(last_cv) as total_last_cv"))
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` as pid from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = 3 and `monthlydatas`.`date` like '".date('Y-m-t', strtotime('-1 month'))."') AS last_month"), 'monthlydatas.product_id','=','last_month.pid')
                    ->where('product_base_id', 3)
                    ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%")
                    ->get();

        $ratio = (date("d")/date("t"));

        /*
           - 今月分のみ取得
        */
        $productsEstimates = Monthlydata::select(DB::raw("
            asps.name,
            (imp/". $ratio .") as estimate_imp,
            (click/". $ratio .") as estimate_click,
            (cv/". $ratio .") as estimate_cv,
            ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
            ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr, 
            (cost/". $ratio .") as estimate_cost,
            products.product,
            products.id"))
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('products.product_base_id', 3)
                    ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                    ->get();
                    /*echo '<pre>';
                    var_dump($productsEstimates->toArray());
                    echo '</pre>';*/
                    
        $productsEstimateTotals = DB::table(
                                DB::raw("
                                    (select (imp/". $ratio .") as estimate_imp,
                                    (click/". $ratio .") as estimate_click,
                                    (cv/". $ratio .") as estimate_cv,
                                    ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                                    ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr,
                                    (cost/". $ratio .") as estimate_cost,
                                    products.product,
                                    products.id as product_id ,date from monthlydatas
                                    inner join products on monthlydatas.product_id = products.id
                                    where products.product_base_id = 3 
                                    and monthlydatas.date LIKE '%".date("Y-m-d", strtotime('-1 day'))."%') as estimate_table")
                          )
                        ->select(DB::raw("date, product_id,
                        sum(estimate_imp) as total_estimate_imp,
                        sum(estimate_click) as total_estimate_click,
                        sum(estimate_cv) as total_estimate_cv,
                        sum(estimate_cost) as total_estimate_cost"))
                    ->get();
                    $productsEstimateTotals = json_decode(json_encode($productsEstimateTotals), true);
                    var_dump($productsEstimateTotals);


        $product_bases = ProductBase::all();
        $asps = Asp::all();

        //var_dump($products);

        //グラフ数値
        $chart_data = Monthlydata::select(['name', 'imp', 'click','cv','date'])
                ->join('products','monthlydatas.product_id','=','products.id')
                ->join('asps','products.asp_id','=','asps.id')
                ->where('products.product_base_id', 3)
                ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
                ->get();

        //var_dump($chart_data);
        //echo $chart_sql;

        if( $products->isEmpty() ){
        	return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
        	return view('admin.monthly',compact('products','product_bases','asps','productsEstimates','productsEstimateTotals','productsTotals','user','chart_data'));
        }
    }
    /**
        月次の基本データ表示（検索後）
    */
	public function monthlyResultSearch(SearchMonthlyRequest $request) {

        $user = Auth::user();
		$id = ($request->product != null)? $request->product : 3 ;
        $month =($request->month != null)? $request->month : date("Y-m", strtotime('-1 day'));
        $ratio = (date("d")/date("t"));
        //検索日時
        //今月の場合　Dateが昨日付け
        //先月以前の場合　Dateが末日付け
        if( $month == date("Y-m")){
            $searchdate = date("Y-m-d", strtotime('-1 day'));
            $search_last_date = date("Y-m-t", strtotime('-1 month'));

        }else{
            $searchdate = date('Y-m-d', strtotime('last day of ' . $month));
            $month = date('Y-m',strtotime('-1 month'));
            $search_last_date = date('Y-m-t', strtotime('last day of ' . $month));
        }

        $request->flash();
        /**
            当月の実績値
        */
        $products = Monthlydata::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','monthlydatas.created_at','products.product','products.id','price','cpa','cost','approval','approval_price','approval_rate','last_cv'])
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

        $productsTotals = Monthlydata::select(DB::raw("date, product_id,sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price ,sum(cost) as total_cost,sum(approval) as total_approval, sum(approval_price) as total_approval_price"))
                   ->join('products','monthlydatas.product_id','=','products.id');
                    if(!empty($id)){
                        $productsTotals->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsTotals->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsTotals = $productsTotals->get();

        if( $month == date("Y-m", strtotime('-1 day'))){
            /**
                当月の着地想定
            */

        $productsEstimates = Monthlydata::select(DB::raw("
            asps.name,
            (imp/". $ratio .") as estimate_imp,
            (click/". $ratio .") as estimate_click,
            (cv/". $ratio .") as estimate_cv,
            ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
            ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr, 
            (cost/". $ratio .") as estimate_cost,
            products.product,
            products.id"))
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $productsEstimates->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsEstimates->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsEstimates=$productsEstimates->get();
                    //->toArray();
            /**
                当月の着地想定トータル
            */
        $productsEstimateTotals = DB::table(
                                DB::raw("
                                    (select (imp/". $ratio .") as estimate_imp,
                                    (click/". $ratio .") as estimate_click,
                                    (cv/". $ratio .") as estimate_cv,
                                    ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                                    ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr,
                                    (cost/". $ratio .") as estimate_cost,
                                    products.product,
                                    products.id as product_id ,date from monthlydatas
                                    inner join products on monthlydatas.product_id = products.id
                                    where products.product_base_id = ".$id."
                                    and monthlydatas.date LIKE '%".$searchdate."%') as estimate_table")
                          )
                        ->select(DB::raw("date, product_id,
                        sum(estimate_imp) as total_estimate_imp,
                        sum(estimate_click) as total_estimate_click,
                        sum(estimate_cv) as total_estimate_cv,
                        sum(estimate_cost) as total_estimate_cost"))->get();
                    $productsEstimateTotals = json_decode(json_encode($productsEstimateTotals), true);

                //var_dump($productsEstimateTotals->toArray());

        }else{
            $productsEstimates = 'Empty';
            $productsEstimateTotals = 'Empty';
        }
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
        	return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
        	return view('admin.monthly',compact('products','product_bases','asps','productsEstimates','productsEstimateTotals','productsTotals','user','chart_data'));
        }
    }
    /**
    *サイト別デイリーレポートのデフォルトページを表示。
    *表示データがない場合、エラーページを表示する。
    *
    *@return view
    *
    */

    public function monthlyResultSite() {

        $user = Auth::user();
        
        $month = date('Ym',strtotime('-1 day'));
        $monthly_sites_table = $month.'_monthlysites';

        $products = DB::table($monthly_sites_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id','price','cpa','cost','estimate_cv','date','approval','approval_price','approval_rate'])
                    ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', 3)
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
        $site_ranking = $this->monthlyRankingSite(3,date("Y-m-d", strtotime('-1 day')));

        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
            return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
            return view('admin.monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        }
    }
    /**
    *サイト別デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */

    public function monthlyResultSiteSearch(SearchMonthlySiteRequest $request) {
        $user = Auth::user();
        $id = ($request->product != null)? $request->product : 3 ;
        $month =($request->month != null)? $request->month : date("Y-m-d", strtotime('-1 day'));
        //当月の場合
        if( $month == date("Y-m", strtotime('-1 day'))|| $month == date("Y-m-d", strtotime('-1 day'))) {
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
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id','price','cpa','cost','estimate_cv','date','approval','approval_price','approval_rate'])
                    ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    
                    if(!empty($id)){
                        $products->where('products.product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate)){
                        $products->where('date', 'LIKE' , "%".$searchdate."%");
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
        $site_ranking = $this->monthlyRankingSite($id,$searchdate,$asp_id);
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
            return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
            return view('admin.monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        }


    }
    /**
　    月別ランキング一覧取得
    */
    public function monthlyRankingSite($id ,$searchdate = null,$asp_id=null) {

                /*
                    案件ｘ対象期間からCVがTOP10のサイトを抽出
                */
                $month = date('Ym',strtotime($searchdate));
                $monthly_sites_table = $month.'_monthlysites';

                $products = DB::table($monthly_sites_table)
                //$products = Monthlysite::
                    ->select(DB::raw("cv , media_id, site_name"))
                    
                    ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
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
                        if(strpos($searchdate,date("Y-m", strtotime('-1 day'))) === false ){
                            $searchdate= date("Y-m-t",strtotime($searchdate));
                        }
                        $products->where('date' , $searchdate );
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
