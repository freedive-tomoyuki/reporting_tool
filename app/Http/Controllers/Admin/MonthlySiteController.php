<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\MonthlySiteDataService;
use App\ProductBase;
use App\Product;
use App\Asp;
use App\Monthlydata;
use App\Http\Requests\MonthlySiteRequest;
use App\Http\Requests\SearchMonthlySiteRequest;
use App\Site;
//use App\MonthlyTotal;
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
        $this->middleware('auth:admin');
        //$this->monthlyDataService = new MonthlyDataService();
        $this->monthlySiteDataService = $monthlySiteDataService;
    }
    /**
    *　サイト別デイリーレポートのデフォルトページを表示。
    *　表示データがない場合、エラーページを表示する。
    *
    *　@return view
    *
    */

    public function monthlyResultSite() {

        $user = Auth::user();
        $selected_site_name = '';
        $month = date("Y-m-d", strtotime('-1 day'));
        
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asp_id = '';

        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        //var_dump($products);
        
        //日次のグラフ用データの一覧を取得する。
        //$site_ranking = $this->monthlyRankingSite(3,date("Y-m-d", strtotime('-1 day')));
        
        $products = $this->monthlySiteDataService->showSiteList($asp_id ,  3 , $month , $selected_site_name);
        
        $site_ranking = $this->monthlySiteDataService->monthlyRankingSite( 3 , $month , $asp_id);

        //VIEWを表示する。
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
        $selected_site_name = '';

        $id = ($request->product != null)? $request->product : 3 ;

        $month =($request->month != null)? $request->month : date("Y-m-d", strtotime('-1 day'));

        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;

        //echo $searchdate;
                
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();

        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        $request->flash();

        //[ $products , $site_ranking ] = $this->monthlySiteDataService->showSiteList($id,$month,$asp_id);
        
        $products = $this->monthlySiteDataService->showSiteList( $asp_id , $id , $month , $selected_site_name);
        
        $site_ranking = $this->monthlySiteDataService->monthlyRankingSite( $id , $month , $asp_id);

        
        //VIEWを表示する。
        if( $products->isEmpty() ){
            return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
            return view('admin.monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        }


    }
    /**
     * 編集画面
     */
    public function monthlySiteModify( Request $request , $id ){
        
        $request->flash();

        $user = Auth::user();
        $array_product_id = array();
        
        $sites = [];
        
        $asps = new Asp();
        $asps = $asps->target_asp($id);

        //検索（月）
        $selected_month = 
            (!$request->input('search_date') || $request->input('search_date') == date('Y-m'))?
            date('Y-m-d' ,strtotime('-1 day')) : date('Y-m-d', strtotime('last day of ' . $request->input('search_date')));

        //検索（サイト名）
        $selected_site_name = (!$request->input('site_name'))? '' : $request->input('site_name');

        //検索（ASP）
        $selected_asp = (!$request->input('search_asp'))? '' : $request->input('search_asp');

        //案件一覧
        $products = Product::where('product_base_id',$id)->where('killed_flag', '==' ,0 )->get();        
        foreach($products as $p){
           array_push($array_product_id, $p->id );
        }
        // var_dump($products);
        //月次データ一覧
        $monthly_sites = $this->monthlySiteDataService->showSiteList( $selected_asp , $id ,  $selected_month , $selected_site_name);
        //var_dump($monthly_sites);

        //$sites = Site::whereIn("product_id",$array_product_id);

        // if($selected_asp){
        //     $sites->where('asp_id', '=' , $selected_asp);
        // }
        // $sites = $sites->get();
        //echo $products[0]->product_base_id ;
        
        return view('admin.monthly.site_edit',compact('monthly_sites','user','asps' ,'products','selected_month','selected_asp'));
    }

    /**
     * 追加実行
     */
    public function monthlySiteAddition(MonthlySiteRequest $request ){

        $product_id = Product::where('product_base_id',$request->product[0])
                            ->where('asp_id',$request->asp[0])->get()->toArray();
        if($request->date[0] == date('Y-m')){
            $month = date('Y-m-d', strtotime('-1 day'));
        }else{
            $month = date('Y-m-t', strtotime($request->date[0]));
        }

        $date = $month;
        $media_id = $request->media_id[0];
        $site_name = $request->site_name[0];
        $imp = $request->imp[0];
        $ctr = $request->ctr[0];
        $click = $request->click[0];
        $cvr = $request->cvr[0];
        $cv = $request->cv[0];
        $cost = $request->cost[0];
        $price = $request->price[0];
        $asp = $request->asp[0];
        $approval = $request->approval[0];
        $approval_price = $request->approval_price[0];
        $approval_rate = $request->approval_rate[0];

        //var_dump($product_id[0]["id"]);
        $this->monthlySiteDataService->addSiteData( $date , $product_id[0]["id"] , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name,$approval,$approval_price ,$approval_rate);
        return redirect('admin/monthly_result_site');
    }
    /**
     * 編集実行
     */
    public function monthlySiteUpdate(MonthlySiteRequest $request, $id ){

        $all_post_data = (isset($request))? $request : '' ;

        $this->monthlySiteDataService->updateSiteData( $id , $all_post_data ); 

        return redirect('admin/monthly_result_site');
    }


}
