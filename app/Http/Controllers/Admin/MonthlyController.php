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
use App\Http\Requests\MonthlyRequest;
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
    public function show( Request $request , $id ){
        
        $request->flash();
        $user = Auth::user();
        $array_product_id = array();
        
        $asps = new Asp();
        $asps = $asps->target_asp($id);

        $end_of_month = (!$request->input('search_date'))? date('Y-m-d' ,strtotime('-1 day')) : date('Y-m-d', strtotime('last day of ' . $request->input('search_date')));

        $selected_asp = (!$request->input('search_asp'))? '' : $request->input('search_asp');

        $products = Product::where('product_base_id',$id)->where('killed_flag', '==' ,0 )->get();
        
        foreach($products as $p){
           array_push($array_product_id, $p->id );
        }

        $monthly = MonthlyData::whereIn("product_id",$array_product_id);
        if($end_of_month){
            $monthly->where('date', '=' , $end_of_month);
        }
        if($selected_asp){
            $monthly->where('asp_id', '=' , $selected_asp);
        }
        $monthly = $monthly->get();

        // echo $monthly;
        return view('admin.monthly.edit',compact('monthly','user','asps' ,'products','end_of_month','selected_asp'));
    }

    /**
     * 追加実行
     */
    public function add(MonthlyRequest $request ){

        $product_id = Product::where('product_base_id',$request->product[0])
                            ->where('asp_id',$request->asp[0])
                            ->get()->toArray();
        $month = date('Y-m-t', strtotime($request->date[0]));

        Monthlydata::updateOrCreate(
            ['date' =>  $month , 'product_id' => $product_id[0]['id'] ],
            [
                'asp_id' => $request->asp[0],
                'imp' => $request->imp[0],
                'ctr' => $request->ctr[0],
                'click' => $request->click[0],
                'cvr' => $request->cvr[0],
                'cv' => $request->cv[0],
                'active' => $request->active[0],
                'partnership' => $request->partner[0],
                'cost' => $request->cost[0],
                'price' => $request->price[0],
                'approval' => $request->approval[0],
                'approval_price' => $request->approval_price[0],
                'approval_rate' => $request->approval_rate[0]

            ]
        );
        return redirect('admin/monthly_result');
    }
    /**
     * 編集実行
     */
    public function update(MonthlyRequest $request, $id ){
        //  var_dump($request);

        // if($request->month){
        //     $end_of_month = date('Y-m-d', strtotime('last day of ' . $request->input('search_date')));
        //     $search_date = $request->input('search_date');
        // }else{
        //     $end_of_month = date('Y-m-d' ,strtotime('-1 day')) ;
        //     $search_date = date('Y-m' ,strtotime('-1 day'));
        // }
        $end_of_month = (!$request->month)? '' : $request->month;
        $selected_asp = (!$request->asp)? '' : $request->asp;

        $products = Product::select('id')
                            ->where('product_base_id',$id) 
                            ->where('killed_flag', '==' ,0 )
                            ->get();

        $monthly = MonthlyData::whereIn("product_id",$products);
                            // ->whereIn("date",$target_array)
                            if($end_of_month){
                                $monthly->where('date', '=' , $end_of_month);
                            }
                            if($selected_asp){
                                $monthly->where('asp_id', '=' , $selected_asp);
                            }
                            $monthly = $monthly->get();

        foreach($monthly as $p){
            //var_dump($p) ;
            $update_monthly = MonthlyData::find($p->id) ;
            $request_key = hash('md5',$p->id);
            $update_monthly->imp = $request->imp[$request_key];
            $update_monthly->ctr = $request->ctr[$request_key];
            $update_monthly->click = $request->click[$request_key];
            $update_monthly->cvr = $request->cvr[$request_key];
            $update_monthly->cv = $request->cv[$request_key];
            $update_monthly->active = $request->active[$request_key];
            $update_monthly->partnership = $request->partner[$request_key];
            $update_monthly->cost = $request->cost[$request_key];
            $update_monthly->price = $request->price[$request_key];
            $update_monthly->approval = $request->approval[$request_key];
            $update_monthly->approval_price = $request->approval_price[$request_key];
            $update_monthly->approval_rate = $request->approval_rate[$request_key];

            if($request->delete[$request_key] == 'on' ){
                $update_monthly->killed_flag = 1;
            }

            $update_monthly->save();
            
        }

        return redirect('admin/monthly_result');
        //return view('admin.monthly.edit',compact('monthly','user', 'asps'));
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
