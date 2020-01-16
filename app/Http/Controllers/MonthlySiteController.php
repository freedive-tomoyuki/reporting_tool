<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\ProductBase;
use App\Asp;
use App\Services\MonthlySiteDataService;
use App\Http\Requests\SearchMonthlySiteRequest;
use DB;

class MonthlySiteController extends Controller
{
    //private $monthlyDataService;
    private $monthlySiteDataService;
    /**
    *　認証確認
    */
    public function __construct(MonthlySiteDataService $monthlySiteDataService)
    {
        //$this->middleware('guest');
        $this->middleware('auth');
        $this->monthlySiteDataService = $monthlySiteDataService;
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
        $selected_site_name = '';
        $month = date("Y-m-d", strtotime('-1 day'));
        
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asp_id = '';
        $id = $user->product_base_id; 
        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        $products = $this->monthlySiteDataService->showSiteList( $asp_id ,  $id , $month , $selected_site_name);

        $site_ranking = $this->monthlySiteDataService->monthlyRankingSite( $id , $month , $asp_id);
    
        //VIEWを表示する。
        // if( $products->isEmpty() ){
        //     return view('daily_error',compact('product_bases','asps','user'));
        // }else{
            return view('monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        // }
    }
    /**
    *サイト別デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */

    public function monthly_result_site_search(SearchMonthlySiteRequest $request) {
        $request->flash();
        $user = Auth::user();
        $selected_site_name = '';

        $id = $user->product_base_id; 
        $month = ($request->month != null)? $request->month : date("Y-m-d", strtotime('-1 day'));
        // echo $month;
        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;
                    
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        
        $products = $this->monthlySiteDataService->showSiteList( $asp_id ,  $id , $month , $selected_site_name);
        // var_dump($products);

        $site_ranking = $this->monthlySiteDataService->monthlyRankingSite( $id , $month , $asp_id);
        // VIEWを表示する。
        // if( $products->isEmpty() ){
        //     return view('daily_error',compact('product_bases','asps','user'));
        // }else{
            return view('monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        // }


    }
    
}
