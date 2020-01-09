<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\ProductBase;
use App\Asp;
use App\Services\MonthlyDataService;
use App\Http\Requests\SearchMonthlyRequest;
use DB;

class MonthlyController extends Controller
{
    private $monthlyDataService;
    /**
    *　認証確認
    */
    public function __construct(MonthlyDataService $monthlyDataService)
    {
        //$this->middleware('guest');
        $this->middleware('auth');
        $this->monthlyDataService = $monthlyDataService;
    }

    /**
        月次の基本データ表示（デフォルト）
    */
    public function monthly_result() {
        $user = Auth::user();

        // $products = Monthlydata::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','monthlydatas.created_at','products.product','products.id','price','cpa','cost','approval','approval_price','approval_rate','last_cv'])
        //             ->join('products','monthlydatas.product_id','=','products.id')
        //             ->join('asps','products.asp_id','=','asps.id')
        //             ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$user->id." and `monthlydatas`.`date` like '".date('Y-m-t', strtotime('-1 month'))."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id')
        //             ->where('products.product_base_id', $user->id)
        //             ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
        //             ->get();//->toArray();

        // $productsTotals = Monthlydata::select(DB::raw("date, product_id,sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price,sum(cost) as total_cost ,sum(approval) as total_approval, sum(approval_price) as total_approval_price, sum(last_cv) as total_last_cv"))
        //             ->join('products','monthlydatas.product_id','=','products.id')
        //             ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` as pid from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = 3 and `monthlydatas`.`date` like '".date('Y-m-t', strtotime('-1 month'))."') AS last_month"), 'monthlydatas.product_id','=','last_month.pid')
        //             ->where('product_base_id', $user->id)
        //             ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%")
        //             ->get();


        // /*
        //    - 今月分のみ取得
        // */
        // $productsEstimates = Dailyestimate::select([ 'asps.name','products.id','dailyestimates.asp_id','estimate_imp', 'estimate_click','estimate_cv','dailyestimates.created_at','estimate_cvr','estimate_ctr','estimate_cost','estimate_price','estimate_cpa','date'])
        //             ->join('products','dailyestimates.product_id','=','products.id')
        //             ->join('asps','products.asp_id','=','asps.id')
        //             ->where('products.product_base_id', $user->id)
        //             ->where('dailyestimates.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
        //             ->get();//->toArray();

        // $productsEstimateTotals = Dailyestimate::select(DB::raw("date, product_id,
        //                 sum(estimate_imp) as total_estimate_imp,
        //                 sum(estimate_click) as total_estimate_click,
        //                 sum(estimate_cv) as total_estimate_cv,
        //                 sum(estimate_price) as total_estimate_price,
        //                 sum(estimate_cost) as total_estimate_cost "))
        //             ->join('products','dailyestimates.product_id','=','products.id')
        //             ->where('products.product_base_id', $user->id)
        //             ->where('dailyestimates.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
        //             ->get();
        $id = $user->product_base_id; 
        //->toArray();
        $month   = date("Y-m-d", strtotime('-1 day'));
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        [ $products, $products_totals, $products_estimates, $products_estimate_totals, $chart_data]= $this->monthlyDataService->showList($id ,$month);

        // var_dump($products);

        //グラフ数値
        // $chart_data = Monthlydata::select(['name', 'imp', 'click','cv','date'])
        //         ->join('products','monthlydatas.product_id','=','products.id')
        //         ->join('asps','products.asp_id','=','asps.id')
        //         ->where('products.product_base_id', $user->id)
        //         ->where('monthlydatas.date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
        //         ->get();

        //var_dump($chart_data);
        //echo $chart_sql;

        if( $products->isEmpty() ){
            return view('daily_error',compact('product_bases','asps','user'));
        }else{
            return view('monthly',compact('products','product_bases','asps','products_estimates','products_estimate_totals','products_totals','user','chart_data'));
        }
    }
    /**
        月次の基本データ表示（検索後）
    */
    public function monthly_result_search(SearchMonthlyRequest $request) {
        $request->flash();

        $user = Auth::user();
        $id = $user->product_base_id; 
        $month =($request->month != null)? $request->month : date("Y-m", strtotime('-1 day'));

        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        [ $products, $products_totals, $products_estimates, $products_estimate_totals, $chart_data ] = $this->monthlyDataService->showList($id,$month);

        if( $products->isEmpty() ){
            return view('daily_error',compact('product_bases','asps','user'));
        }else{
            return view('monthly',compact('products','product_bases','asps','products_estimates','products_estimate_totals','products_totals','user','chart_data'));
        }
    }

}
