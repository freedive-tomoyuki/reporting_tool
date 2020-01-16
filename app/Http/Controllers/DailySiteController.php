<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ProductBase;
use App\Asp;
use App\Services\dailySiteDataService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SearchDailySiteRequest;

/*
　デイリーデータ用　クラス
*/
class DailySiteController extends Controller
{
    //private $dailyDataService;
    private $dailySiteDataService;
    /**
     * Undocumented function
     *
     * @param DailySiteDataService $dailySiteDataService
     */
    public function __construct(DailySiteDataService $dailySiteDataService)
    {
        $this->middleware('auth');
        //$this->dailyDataService = new DailyDataService();
        $this->dailySiteDataService = $dailySiteDataService;
    }

    /**
    *サイト別デイリーレポートのデフォルトページを表示。
    *表示データがない場合、エラーページを表示する。
    *
    *@return view
    *
    */

    public function daily_result_site() {
        $user = Auth::user();

        $selected_asp = '';
        $selected_site_name = '';
        // プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        
        // ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        $start = date("Y-m-01",strtotime('-1 day'));
        $end = date("Y-m-d",strtotime('-1 day'));

        $id = $user->product_base_id;

        $products =  $this->dailySiteDataService->showSiteList( $selected_asp, $id , $start, $end, $selected_site_name);
        $site_ranking = $this->dailySiteDataService->dailyRankingSite( $selected_asp , $id , $start, $end );

        //VIEWを表示する。
        // if( $products->isEmpty() ){
        //    return view('daily_error',compact('product_bases','asps','user'));
        // }else{
           return view('daily_site',compact('products','product_bases','asps','site_ranking','user'));
        // }
    }
    /**
    *サイト別デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */

    public function daily_result_site_search(SearchDailySiteRequest  $request) {
        $user = Auth::user();
        $request->flash();

        $selected_asp = '';
        $selected_site_name = '';
        
        $id = $user->product_base_id;

        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==', 0)->get();
        
        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==', 0)->get();

        // $id = ($request->product != null)? $request->product : 1 ;
        
        $start          =($request->searchdate_start != null)? $request->searchdate_start : date("Y-m-d", strtotime('-1 day'));
        $end            =($request->searchdate_end != null)? $request->searchdate_end : date("Y-m-d", strtotime('-1 day'));
        $selected_asp   = ($request->asp_id != null)? $request->asp_id : "" ;

        $products       =  $this->dailySiteDataService->showSiteList( $selected_asp, $id, $start, $end, $selected_site_name);
        $site_ranking   = $this->dailySiteDataService->dailyRankingSite( $selected_asp, $id, $start, $end );

        //VIEWを表示する。
        // if( $products->isEmpty() ){
        //    return view('daily_error',compact('product_bases','asps','user'));
        // }else{
          return view('daily_site',compact('products','product_bases','asps','site_ranking','user'));
        // }


    }
    
}
