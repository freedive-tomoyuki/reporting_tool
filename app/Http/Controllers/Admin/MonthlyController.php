<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\MonthlyDataService;
use App\ProductBase;
use App\Product;
use App\Asp;
use App\Monthlydata;
use App\Http\Requests\SearchMonthlyRequest;
use App\Http\Requests\SearchMonthlySiteRequest;
//use App\MonthlyTotal;
use DB;

class MonthlyController extends Controller
{
    private $monthlyDataService;
    /**
    　認証確認
    */
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
        $this->monthlyDataService = new MonthlyDataService();
    }

    /**
        月次の基本データ表示（デフォルト）
    */
    public function monthlyResult() {
        $user = Auth::user();
        
        $month   = date("Y-m-d", strtotime('-1 day'));

        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        
        [ $products, $products_totals, $products_estimates, $products_estimate_totals, $chart_data]= $this->monthlyDataService->showList(3,$month);

        if( $products->isEmpty() ){
        	return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
        	return view('admin.monthly',compact('products','product_bases','asps','products_estimates','products_estimate_totals','products_totals','user','chart_data'));
        }
    }
    /**
    *    月次の基本データ表示（検索後）
    */
	public function monthlyResultSearch(SearchMonthlyRequest $request) {

        $user = Auth::user();
        
        $id = ($request->product != null)? $request->product : 3 ;
        $month =($request->month != null)? $request->month : date("Y-m", strtotime('-1 day'));
        
        $request->flash();

        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        [ $products, $products_totals, $products_estimates, $products_estimate_totals, $chart_data]= $this->monthlyDataService->showList($id,$month);

        if( $products->isEmpty() ){
        	return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
            return view('admin.monthly',compact('products','product_bases','asps','products_estimates','products_estimate_totals','products_totals','user','chart_data'));
        }
    }
    /**
     * 編集画面
     */
    public function edit( $id ){
        $i = 30;
        $target ='';
        $target_array = array();

        while(date("Y-m-t",strtotime('-1 month') ) != $target){
            $target = date("Y-m-t",strtotime('-'.$i.' month'));
            array_push($target_array, $target);
            $i--;
        }
        $user = Auth::user();
        $products = Product::select('id')->where('product_base_id',$id)->where('killed_flag', '==' ,0 )->get();

        $monthly = MonthlyData::whereIn("product_id",$products)->whereIn("date",$target_array)->get();

        return view('admin.monthly.edit',compact('monthly','user'));
    }
    /**
     * 編集実行
     */
    public function update(Request $request, $id ){
        //  var_dump($request);
        $i = 30;
        $target ='';
        $target_array = array();
        $user = Auth::user();

        while(date("Y-m-t",strtotime('-1 month') ) != $target){
            $target = date("Y-m-t",strtotime('-'.$i.' month'));
            array_push($target_array, $target);
            $i--;
        }
        $products = Product::select('id')->where('product_base_id',$id)->where('killed_flag', '==' ,0 )->get();

        
        $monthly = MonthlyData::whereIn("product_id",$products)->whereIn("date",$target_array)->get();
        
        foreach($monthly as $p){
            $u_monthly = MonthlyData::find($p->id) ;
            $u_monthly->imp = $request->{"imp".$p->id};
            $u_monthly->ctr = $request->{"ctr".$p->id};
            $u_monthly->click = $request->{"click".$p->id};
            $u_monthly->cvr = $request->{"cvr".$p->id};
            $u_monthly->cv = $request->{"cv".$p->id};
            $u_monthly->active = $request->{"active".$p->id};
            $u_monthly->partnership = $request->{"partner".$p->id};
            $u_monthly->cost = $request->{"cost".$p->id};
            $u_monthly->price = $request->{"price".$p->id};
            $u_monthly->approval = $request->{"approval".$p->id};
            $u_monthly->approval_price = $request->{"approval_price".$p->id};
            $u_monthly->approval_rate = $request->{"approval_rate".$p->id};
            if($request->{"delete".$p->id} == 'on' ){
                $u_monthly->killed_flag = 1;
            }
            $u_monthly->save();
            
        }
        
        return view('admin.monthly.edit',compact('monthly','user'));
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
        
        $month = date("Y-m-d", strtotime('-1 day'));
        $monthly_sites_table = date('Ym',strtotime('-1 day')).'_monthlysites';
        
        // $products = DB::table($monthly_sites_table)
        //             ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id',DB::raw($monthly_sites_table.'.price'),'cpa','cost','estimate_cv','date','approval','approval_price','approval_rate'])
        //             ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
        //             ->join('asps','products.asp_id','=','asps.id')
        //             ->where('product_base_id', 3)
        //             ->where('date', 'LIKE' , "%".date("Y-m-d", strtotime('-1 day'))."%")
        //             ->get();
        
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        $asp_id = '';

        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        //var_dump($products);
        
        //日次のグラフ用データの一覧を取得する。
        //$site_ranking = $this->monthlyRankingSite(3,date("Y-m-d", strtotime('-1 day')));
        
        [ $products , $site_ranking ] = $this->monthlyDataService->showSiteList( 3 , $month , $asp_id);


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
        $id = ($request->product != null)? $request->product : 3 ;

        $month =($request->month != null)? $request->month : date("Y-m-d", strtotime('-1 day'));

        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;

        //echo $searchdate;
                
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();

        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        $request->flash();

        [ $products , $site_ranking ] = $this->monthlyDataService->showSiteList($id,$month,$asp_id);

        
        //VIEWを表示する。
        if( $products->isEmpty() ){
            return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
            return view('admin.monthly_site',compact('products','product_bases','asps','site_ranking','user'));
        }


    }

}
