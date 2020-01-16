<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;

use App\Product;
use App\ProductBase;
use App\Asp;
use App\DailyDiff;
use App\Services\DailyDataService;
use App\Http\Requests\DailyDiffRequest;
use App\Http\Requests\SearchDailyRequest;
use App\Http\Requests\SearchDailySiteRequest;
use Illuminate\Support\Facades\Auth; 
use DB;

/*
　デイリーデータ用　クラス
*/
class DailyController extends Controller
{
    private $dailyDataService;

    public function __construct(DailyDataService $dailyDataService)
    {
        $this->middleware('auth:admin');
        $this->dailyDataService = $dailyDataService;
    }

    /**
    *デイリーレポートのデフォルトページを表示。
    *表示データがない場合、エラーページを表示する。
    *
    *@return view
    *
    */
    public function dailyResult() {
        $user = Auth::user();
        
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        
        // ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        
        // $data = array();
        $asp_id = '';
        
        //Eloquentを利用して、dailydatasテーブルから案件ID＝１の昨日データ取得する。
        $start   = date("Y-m-1",strtotime('-1 day'));
        $end     = date("Y-m-d",strtotime('-1 day'));

        [$daily_data ,$daily_ranking , $total , $total_chart ] = $this->dailyDataService->showList($asp_id , 3 , $start , $end );

        
        //VIEWを表示する。
        //if($daily_data->isEmpty()){
        //    return view('admin.daily_error',compact('product_bases','asps','user','total'));
        //}else{
            return view('admin.daily',compact('daily_data','product_bases','asps','daily_ranking','user','total','total_chart'));
        //}
    }
    /**
    *デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */
    public function dailyResultSearch(SearchDailyRequest $request) {
        $user = Auth::user();
        $request->flash();

        // プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        
        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();

        // $i = 0;
        $id = ($request->product != null)? $request->product : 1 ;

        $start =($request->searchdate_start != null)? $request->searchdate_start : date("Y-m-d",strtotime('-1 day'));
        
        $end =($request->searchdate_end != null)? $request->searchdate_end : date("Y-m-d",strtotime('-1 day'));
        
        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;
        
        [$daily_data ,$daily_ranking , $total , $total_chart ] = $this->dailyDataService->showList($asp_id , $id, $start , $end  );

        //VIEWを表示する。
        // if($daily_data->isEmpty() ){
        //     return view('admin.daily_error',compact('product_bases','asps','user'));
        // }else{
            return view('admin.daily',compact('daily_data','product_bases','asps','daily_ranking','user','total','total_chart'));
        // }
    }

    /**
     * 編集画面
     */
    public function dailyModify( Request $request , $id){
        
        $request->flash();
        $user = Auth::user();
        $array_product_id = array();
        
        $asps = new Asp();
        $asps = $asps->target_asp($id);
        
        $month = ($request->input('search_date'))? $request->input('search_date') : '';
        $start = (!$request->input('search_date'))? date('Y-m-01') : date('Y-m-d', strtotime('first day of ' . $request->input('search_date'))) ;
        $end = (!$request->input('search_date'))? date('Y-m-d' ,strtotime('-1 day')) : date('Y-m-d', strtotime('last day of ' . $request->input('search_date')));
        
        $selected_asp = (!$request->input('search_asp'))? '' : $request->input('search_asp');

        $products = Product::where('product_base_id',$id)
                    ->where('killed_flag', '==' ,0 )
                    ->get();
        
        foreach($products as $p){
            array_push($array_product_id, $p->id );
        }
        
        $daily = new DailyDiff;
                
        $daily->whereIn("product_id",$products);
        if($start){
            $daily = $daily->where('date', '>=' , $start);
        }
        if($end){
            $daily = $daily->where('date', '<=' , $end) ;
        }
        if($selected_asp){
            $daily = $daily->where('asp_id', '=' , $selected_asp);
        }
        $daily = $daily->where('killed_flag', '=' ,0)->orderBy('date', 'asc')->get();
        
        return view('admin.daily.edit',compact('daily','user','asps','products','month','selected_asp'));
    }
    /**
     * 追加実行
     */
    public function dailyAddition(DailyDiffRequest $request )
    {
    
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
        $active = $request->active[0];
        $partner = $request->partner[0];


        //var_dump($product_id[0]["id"]);
        $this->dailyDataService->addData( $date , $product_id[0]["id"] , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partner);

        return redirect('admin/daily_result');

    }
    /**
     * 編集実行
     */
    public function dailyUpdate(DailyDiffRequest $request, $id){
        //  var_dump($request);

        $all_post_data = (isset($request))? $request : '' ;

        $this->dailyDataService->updateData( $id , $all_post_data ); 
        
        // $products = Product::select('id')->where('product_base_id',$id)->where('killed_flag', '==' ,0 )->get();
        
        // $daily = DailyData::whereIn("product_id", $products);
        //                     if($start){
        //                         $daily->where('date', '>=' , $start);
        //                     }
        //                     if($end){
        //                         $daily->where('date', '<=' , $end);
        //                     }
        //                     if($selected_asp){
        //                         $daily->where('asp_id', '=' , $selected_asp);
        //                     }
        
        //                     $daily = $daily->get();
        
        // foreach($daily as $p){
        //     $update_daily = DailyData::find($p->id) ;
        //     $request_key = hash('md5',$p->id);

        //     $update_daily->imp = $request->imp[$request_key];
        //     $update_daily->ctr = $request->ctr[$request_key];
        //     $update_daily->click = $request->click[$request_key];
        //     $update_daily->cvr = $request->cvr[$request_key];
        //     $update_daily->cv = $request->cv[$request_key];
        //     $update_daily->active = $request->active[$request_key];
        //     $update_daily->partnership = $request->partner[$request_key];
        //     $update_daily->cost = $request->cost[$request_key];
        //     $update_daily->price = $request->price[$request_key];
        //     if($request->delete[$request_key] == 'on' ){
        //         $update_daily->killed_flag = 1;
        //     }
        //     $update_daily->save();
            
        // }
        return redirect('admin/daily_result');
        //return view('admin.monthly.edit',compact('monthly','user'));
    }

}
