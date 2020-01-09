<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\YearlyDataService;
use App\Http\Controllers\Controller;
use App\Dailyestimate;
use App\DailyEstimateTotal;
use App\ProductBase;
use App\Product;
use App\Asp;
use App\Monthlydata;
use App\Monthlysite;
use App\Http\Requests\SearchYearlyRequest;
use DB;

class YearlyController extends Controller
{
    private $yearDataService;
    /**
    　認証確認
    */
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
        $this->yearDataService = new YearlyDataService();
    }

    /**
        月次の基本データ表示（デフォルト）
    */
    public function yearlyResult() {
        $user = Auth::user();

        //$asps = Asp::where('killed_flag', '==', 0)->get();
        $asps = Product::Select('asp_id', 'name')->join('asps', 'products.asp_id', '=', 'asps.id')->where('product_base_id', 3)->where('products.killed_flag', 0)->get()->toArray();
        $this_month = date("Ym"); 

        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        
        [$yearly_cvs,$yearly_clicks,$yearly_imps,$yearly_approvals,$yearly_cvrs,$yearly_ctrs,$yearly_cvs_asp,$yearly_clicks_asp,$yearly_imps_asp,$yearly_ctrs_asp,$yearly_cvrs_asp] = $this->yearDataService->showAllList(3);

        $yearly_chart= $this->yearDataService->calChart(3);
        //var_dump($yearly_chart);
        return view('admin.yearly',compact('user','product_bases','asps','yearly_chart','yearly_cvs','yearly_clicks','yearly_imps','yearly_approvals','yearly_cvrs','yearly_ctrs','yearly_cvs_asp','yearly_clicks_asp','yearly_imps_asp','yearly_ctrs_asp','yearly_cvrs_asp'));
    }

    /**
        月次の基本データ表示（検索後）
    */
	public function yearlyResultSearch(SearchYearlyRequest $request) {
        $request->flash();
        
        $user = Auth::user();
        
        $id = ($request->product != null)? $request->product : 3 ;
        
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        
        //$this_month = date("Ym"); 
        [$yearly_cvs,$yearly_clicks,$yearly_imps,$yearly_approvals,$yearly_cvrs,$yearly_ctrs,$yearly_cvs_asp,$yearly_clicks_asp,$yearly_imps_asp,$yearly_ctrs_asp,$yearly_cvrs_asp] = $this->yearDataService->showAllList($id);
        
        $asps = Product::Select('asp_id', 'name')->join('asps', 'products.asp_id', '=', 'asps.id')->where('product_base_id', $id)->where('products.killed_flag', 0)->get()->toArray();
        
        $yearly_chart = $this->yearDataService->calChart($id);

        return view('admin.yearly',compact('user','asps','product_bases','yearly_cvs','yearly_clicks','yearly_imps','yearly_approvals','yearly_cvrs','yearly_ctrs','yearly_cvs_asp','yearly_clicks_asp','yearly_imps_asp','yearly_ctrs_asp','yearly_cvrs_asp','yearly_chart'));

    }

}
