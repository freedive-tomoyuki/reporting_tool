<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;
use App\Site;
use App\Product;
use App\ProductBase;
use App\Asp;
use App\DailyDiff;
//use App\Services\DailyDataService;
use App\Services\DailySiteDataService;
use App\Http\Requests\DailySiteDiffRequest;
use App\Http\Requests\SearchDailyRequest;
use App\Http\Requests\SearchDailySiteRequest;
use Illuminate\Support\Facades\Auth; 
use DB;

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
        $this->middleware('auth:admin');
        //$this->dailyDataService = new DailyDataService();
        $this->dailySiteDataService = $dailySiteDataService;
    }


    /**
     * サイト別デイリーレポートのデフォルトページを表示。
     * 表示データがない場合、エラーページを表示する。
     *
     * @return void
     */
    public function dailyResultSite() {
        $user = Auth::user();
        $selected_asp = '';
        $selected_site_name = '';
        // プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        
        // ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        $start = date("Y-m-01",strtotime('-1 day'));
        
        $end = date("Y-m-d",strtotime('-1 day'));

        $products =  $this->dailySiteDataService->showSiteList( $selected_asp, 3 , $start, $end, $selected_site_name);
        $site_ranking = $this->dailySiteDataService->dailyRankingSite( $selected_asp , 3 , $start, $end );
        //var_dump($products);
        
        //VIEWを表示する。
        if( !isset($products) ){
           return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
           return view('admin.daily_site',compact('products','product_bases','asps','site_ranking','user'));
        }
    }
    /**
    *サイト別デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */

    public function dailyResultSiteSearch(SearchDailySiteRequest $request) {

        $user = Auth::user();

        $request->flash();
        $selected_site_name = '';

        $id = ($request->product != null)? $request->product : 1 ;
        
        $start =($request->searchdate_start != null)? $request->searchdate_start : date("Y-m-d", strtotime('-1 day'));
        $end =($request->searchdate_end != null)? $request->searchdate_end : date("Y-m-d", strtotime('-1 day'));
        $selected_asp = ($request->asp_id != null)? $request->asp_id : "" ;

        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==', 0)->get();
        
        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==', 0)->get();

        $products =  $this->dailySiteDataService->showSiteList( $selected_asp, $id, $start, $end, $selected_site_name);
        $site_ranking = $this->dailySiteDataService->dailyRankingSite( $selected_asp, $id, $start, $end );

        //VIEWを表示する。
        if (!isset($products)) {
            return view('admin.daily_error', compact('product_bases', 'asps', 'user'));
        } else {
            return view('admin.daily_site', compact('products', 'product_bases', 'asps', 'site_ranking', 'user'));
        }
    }
    /**
     * 編集画面
     */
    public function dailySiteModify( Request $request , $id){
        
        $request->flash();
        $user = Auth::user();
        $array_product_id = array();
        
        $asps = new Asp();
        $asps = $asps->target_asp($id);
        
        //検索（日付）
        $month = ($request->input('search_date'))? $request->input('search_date') : date('Y-m',strtotime('-1 day'));
        $start = (!$request->input('search_date'))? date('Y-m-01') : date('Y-m-d', strtotime('first day of ' . $request->input('search_date'))) ;
        $end = (!$request->input('search_date'))? date('Y-m-d' ,strtotime('-1 day')) : date('Y-m-d', strtotime('last day of ' . $request->input('search_date')));

        //検索（サイト名）
        $selected_site_name = (!$request->input('site_name'))? '' : $request->input('site_name');
    
        //検索（ASP）
        $selected_asp = (!$request->input('search_asp'))? '' : $request->input('search_asp');

        $products = Product::where('product_base_id',$id)
                    ->where('killed_flag', '==' ,0 )
                    ->get();
        foreach($products as $p){
            array_push($array_product_id, $p->id );
        }

        $sites = Site::whereIn("product_id",$array_product_id);
        if($selected_asp){
            $sites->where('asp_id', '=' , $selected_asp);
        }
        $sites = $sites->get();

        $daily_sites = $this->dailySiteDataService->showSiteList( $selected_asp, $id, $start, $end, $selected_site_name);
        //var_dump($daily_sites);
        return view('admin.daily.site_edit',compact('daily_sites','user','asps','products','month' ,'selected_asp','sites'));
    }
    /**
     * 追加実行
     */
    public function dailySiteAddition(DailySiteDiffRequest $request ){
        
        $request->flash();

        $product_id = Product::where('product_base_id',$request->product[0])->where('asp_id',$request->asp[0])->get()->toArray();
        
        $date = $request->date[0];
        $imp = $request->imp[0];
        $ctr = $request->ctr[0];
        $click = $request->click[0];
        $cvr = $request->cvr[0];
        $cv = $request->cv[0];
        $cost = $request->cost[0];
        $price = $request->price[0];
        $asp = $request->asp[0];
        $media_id = $request->media_id[0];
        $site_name = $request->media_id[0];

        //var_dump($product_id[0]["id"]);
        $this->dailySiteDataService->addSiteData( $date , $product_id[0]["id"] , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name);
        return redirect('admin/daily_result_site');

    }
    /**
     * 編集実行
     */
    public function dailySiteUpdate(DailySiteDiffRequest $request, $id){
        //  var_dump($request);
        $all_post_data = (isset($request))? $request : '' ;

        $this->dailySiteDataService->updateSiteData( $id , $all_post_data ); 

        return redirect('admin/daily_result');

    }



}
