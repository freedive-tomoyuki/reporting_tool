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
    *　認証確認
    */
    public function __construct(MonthlyDataService $monthlyDataService)
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
        $this->monthlyDataService = $monthlyDataService;
    }

    /**
     *   月次の基本データ表示（デフォルト）
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
    public function monthlyModify( Request $request , $id ){
        
        $request->flash();
        $user = Auth::user();
        $array_product_id = array();
        
        $asps = new Asp();
        $asps = $asps->target_asp($id);

        $selected_month = (!$request->input('search_date'))? date('Y-m-d' ,strtotime('-1 day')) : date('Y-m-d', strtotime('last day of ' . $request->input('search_date')));

        $selected_asp = (!$request->input('search_asp'))? '' : $request->input('search_asp');

        $products = Product::where('product_base_id',$id)->where('killed_flag', '==' ,0 )->get();
        
        foreach($products as $p){
           array_push($array_product_id, $p->id );
        }

        $monthly = MonthlyData::whereIn("product_id",$array_product_id);
        if($selected_month){
            $monthly->where('date', '=' , $selected_month);
        }
        if($selected_asp){
            $monthly->where('asp_id', '=' , $selected_asp);
        }
        $monthly = $monthly->get();

        // echo $monthly;
        return view('admin.monthly.edit',compact('monthly','user','asps' ,'products','selected_month','selected_asp'));
    }

    /**
     * 追加実行
     */
    public function monthlyAddition(MonthlyRequest $request ){

        $product_id = Product::where('product_base_id',$request->product[0])
                            ->where('asp_id',$request->asp[0])
                            ->get()->toArray();
        $month = date('Y-m-t', strtotime($request->date[0]));

        $asp_id = $request->asp[0];
        //$date = $request->date[0];
        $imp = $request->imp[0];
        $ctr = $request->ctr[0];
        $click = $request->click[0];
        $cvr = $request->cvr[0];
        $cv = $request->cv[0];
        $active = $request->active[0];
        $partnership = $request->partner[0];
        $cost = $request->cost[0];
        $price = $request->price[0];
        $asp = $request->asp[0];
        $approval = $request->approval[0];
        $approval_price = $request->approval_price[0];
        $approval_rate = $request->approval_rate[0];

        $this->monthlyDataService->addData( 
            $month , $product_id[0]["id"] , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partnership, $approval ,$approval_price, $approval_rate);
            
        
        return redirect('admin/monthly_result');
    }
    
    /**
     * 編集実行
     */
    public function monthlyUpdate(MonthlyRequest $request, $id ){

        $all_post_data = (isset($request))? $request : '' ;


        $this->monthlyDataService->updateData( $id , $all_post_data);

        return redirect('admin/monthly_result');
        //return view('admin.monthly.edit',compact('monthly','user', 'asps'));
    }


}
