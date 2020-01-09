<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Components\CSV;


use App\Product;
use App\ProductBase;
use App\Asp;
use App\DailyDiff;
use App\DailySiteDiff;
use App\Monthlydata;
use Illuminate\Support\Facades\Auth;
use Excel;
use PDF;
use DB;
/*
　デイリーデータ用　クラス
*/
class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');

    }
    public function index()
    {
        $user = Auth::user();
        $products = Product::all();
        $product_bases = ProductBase::Where('killed_flag',0)->get();
        return view('export',compact('product_bases','user'));
    }
    public function filterAsp( $product_id ){
      $target_asp = Product::select('asp_id','name')
                  ->join('asps','products.asp_id','=','asps.id')
                  ->where('product_base_id', $product_id )
                  ->where('products.killed_flag', 0 )
                  ->get();

      return json_encode($target_asp);
    }
    /**
    * 親案件から案件一覧を取得する。
    * @param number $baseproduct
    * @return array $converter 
    */
    public function convertProduct($baseproduct){
        $converter = Product::select();
        $converter->where('product_base_id', $baseproduct);
        $converter = $converter->get()->toArray();
        return $converter;
    }
    public function selected(Request $request)
    {
        $request->flash();
        $user = Auth::user();
        $products = Product::all();
        $product_bases = ProductBase::Where('killed_flag',0)->get();
        return view('export',compact('product_bases','user'));
    }
    /*
        今月データ　
    */
    public function pdf($id,$month = null ){

        $user = Auth::user();

        if($user->product_base_id !== $id){
            return redirect('/export');
        }

        //$pdf = app('dompdf.wrapper');
        $data = array();
        //$date = date('Y-m' ,strtotime(''));
        $ratio = (date("d")/date("t"));
        //*毎月一日の場合は、先月データと先々月データを参照
        
        $product = ProductBase::Where('id',$id)->get()->toArray();
        $today = date("Y年m月d日");

        if($month == 'one_month' ){//先月分
            $startdate = date("Y-m-1",strtotime('-1 month'));
            $enddate = date("Y-m-t",strtotime('-1 month'));
            $header = '先月 ['.date("Y年m月",strtotime("-1 month")) .'] 全体成果レポート' ;
        }else{
            $startdate = date("Y-m-1",strtotime('-1 day'));
            $enddate = date("Y-m-d",strtotime('-1 day'));
            $header = '今月 ['.date("Y年m月") .'] 全体成果レポート' ;
        }

        /**
            当月の実績値
        */
        $monthlyDatas = Monthlydata::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','monthlydatas.created_at','products.product','products.id','price','cpa','cost','approval','approval_price','approval_rate'])
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$enddate."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id');

                    if(!empty($id)){
                        $monthlyDatas->where('products.product_base_id', $id);
                    }
                    if(!empty($enddate)){
                        $monthlyDatas->where('monthlydatas.date', 'LIKE' , "%".$enddate."%");
                    }

                    $monthlyDatas = $monthlyDatas->get();//->toArray();
                    //var_dump($monthlyDatas);
         /**
            当月の実績値トータル
        */

        $monthlyDataTotals = Monthlydata::select(DB::raw("date, product_id,sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price ,sum(cost) as total_cost,sum(approval) as total_approval, sum(approval_price) as total_approval_price"))
                   ->join('products','monthlydatas.product_id','=','products.id');
                    if(!empty($id)){
                        $monthlyDataTotals->where('products.product_base_id', $id);
                    }
                    if(!empty($enddate)){
                        $monthlyDataTotals->where('monthlydatas.date', 'LIKE' , "%".$enddate."%");
                    }
                    $monthlyDataTotals = $monthlyDataTotals->get();

        if( $month != 'one_month' ){
            /**
                当月の着地想定
            */
                $monthlyDataEstimates = Monthlydata::select(DB::raw("
                    asps.name,
                    (imp/". $ratio .") as estimate_imp,
                    (click/". $ratio .") as estimate_click,
                    (cv/". $ratio .") as estimate_cv,
                    ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                    ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr, 
                    (cost/". $ratio .") as estimate_cost,
                    products.product,
                    products.id"))
                            ->join('products','monthlydatas.product_id','=','products.id')
                            ->join('asps','products.asp_id','=','asps.id');
                            if(!empty($id)){
                                $monthlyDataEstimates->where('products.product_base_id', $id);
                            }
                            if(!empty($enddate)){
                                $monthlyDataEstimates->where('monthlydatas.date', 'LIKE' , "%".$enddate."%");
                            }
                            $monthlyDataEstimates=$monthlyDataEstimates->get();
                            //->toArray();
                    /**
                        当月の着地想定トータル
                    */
                $monthlyDataEstimateTotals = DB::table(
                                        DB::raw("
                                            (select (imp/". $ratio .") as estimate_imp,
                                            (click/". $ratio .") as estimate_click,
                                            (cv/". $ratio .") as estimate_cv,
                                            ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                                            ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr,
                                            (cost/". $ratio .") as estimate_cost,
                                            products.product,
                                            products.id as product_id ,date from monthlydatas
                                            inner join products on monthlydatas.product_id = products.id
                                            where products.product_base_id = ".$id."
                                            and monthlydatas.date LIKE '%".$enddate."%') as estimate_table")
                                  )
                                ->select(DB::raw("date, product_id,
                                sum(estimate_imp) as total_estimate_imp,
                                sum(estimate_click) as total_estimate_click,
                                sum(estimate_cv) as total_estimate_cv,
                                sum(estimate_cost) as total_estimate_cost"))->get();
                            $monthlyDataEstimateTotals = json_decode(json_encode($monthlyDataEstimateTotals), true);

        }else{
            $monthlyDataEstimates = 'Empty';
            $monthlyDataEstimateTotals = 'Empty';
        }
        //月間グラフ数値
        $monthlyCharts = Monthlydata::select(['date','name', 'imp', 'click','cv'])
                ->join('products','monthlydatas.product_id','=','products.id')
                ->join('asps','products.asp_id','=','asps.id');

                if(!empty($id)){
                    $monthlyCharts->where('products.product_base_id', $id);
                }
                if(!empty($enddate)){
                    $monthlyCharts->where('monthlydatas.date', '=', $enddate);
                }

        $monthlyCharts = $monthlyCharts->get();


        $products = DailyDiff::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','date','daily_diffs.created_at','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', $id)
                    ->where('daily_diffs.date', '>=' , $startdate)
                    ->where('daily_diffs.date', '<=' , $enddate)
                    ->get();
                    
        $total = DailyDiff::select(DB::raw("date,products.id, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(estimate_cv) as total_estimate_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price "))
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', $id)
                    ->where('daily_diffs.date', '>=' , $startdate)
                    ->where('daily_diffs.date', '<=' , $enddate)
                    ->get();
        $total_chart = DailyDiff::select(DB::raw("date, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv"))
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', $id)
                    ->where('daily_diffs.date', '>=' , $startdate)
                    ->where('daily_diffs.date', '<=' , $enddate);
        $i = 0;
        $total_chart = $total_chart->groupby('date')->get()->toArray();

        //$total_chart = $total_chart->toArray();

        foreach ($total_chart as $chart) {
            $data[$i]['date'] = $chart['date'];
            $data[$i]['total_imp'] = intval($chart['total_imp']);
            $data[$i]['total_click'] = intval($chart['total_click']);
            $data[$i]['total_cv'] = intval($chart['total_cv']);
            $data[$i] = array_values($data[$i]);
            $i++;
        }

        $total_chart = json_encode($data);
        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        //$daily_ranking = $this->daily_ranking_asp($id,date("Y-m-1",strtotime('-1 day')),date("Y-m-d",strtotime('-1 day')));
        $daily_ranking = $this->daily_ranking_asp($id,$startdate,$enddate);

        $header_html = '
        <!DOCTYPE html>
        <html lang="ja">
          <head>
            <meta charset="UTF-8">
            <style type="text/css">
              body { 
                padding: 10px;
                font-size: 20px;
                font-family: "ＭＳ ゴシック",sans-serif;
                text-align: center;
              }
            </style>
          </head>
          <body>
            '.$product[0]['product_name'].' '.$header.' ('.$today.')</body>
        </html>';
        
        $footer_html = '<!DOCTYPE html>
                <html>
                  <head>
                  <style type="text/css">
                    body { text-align: center; font-size: 15px; font-family: "ＭＳ ゴシック",sans-serif; }
                  </style>
                  </head>
                  <body>
                         Page <span id="page"></span> of 
                        <span id="topage"></span>   
                        <script> 
                          var vars={};
                          var x=window.location.search.substring(1).split("&");

                          for (var i in x) {
                            var z=x[i].split("=",2);
                            vars[z[0]] = unescape(z[1]);
                          }
                          document.getElementById("page").innerHTML = vars.page; 
                          document.getElementById("topage").innerHTML = vars.topage;

                        </script> 
                  </body>
                </html>';

        $pdf = PDF::loadView('pdf.pdf', compact('products','product_bases','total','total_chart','daily_ranking','monthlyDatas','monthlyDataTotals','monthlyDataEstimates','monthlyDataEstimateTotals','monthlyCharts'));
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 5000);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        $pdf->setOption('encoding', 'utf-8');

        $pdf->setOption('header-html', $header_html);
        $pdf->setOption('footer-html', $footer_html);
        
        $pdf->setOption('orientation', 'Landscape');
        
        //return view('pdf.pdf', compact('products','product_bases','total','total_chart','daily_ranking'));
        return $pdf->inline();
        //return $pdf->download('sample.pdf'); 
    }
    /**
        年間
    */
    function pdf_yearly($id,$term = null){

        $user = Auth::user();

        if($user->product_base_id != $id){
            return redirect('/export');
        }

        $bladeFile = ( $term == null )? 'pdf.yearly' : 'pdf.three_month';
        $header = ( $term == null )? '年間レポート' : '直近３ヶ月レポート';
        $term = ( $term == null )? 12 : 3 ; 
        $product = ProductBase::Where('id',$id)->get()->toArray();

        $this_month = date("Ym"); 
        $today = date("Y年m月d日"); 
        $select = ''; 

//成果発生数
        for( $i=1 ; $i <= $term ; $i++ ){
            $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
            $month = date("Y年m月", strtotime('-'.$i.' month'));
            $select .= "sum(case when date='".$last_date."' then cv else 0 end) as '".$i."'";
            if($i != $term){
                $select .= ',';
            }
        }
        $yearly_cvs =Monthlydata::select(DB::raw($select));
        $yearly_cvs->join('products','monthlydatas.product_id','=','products.id');
        $yearly_cvs->where('product_base_id',$id);
        $yearly_cvs->groupBy('product_base_id');
        $yearly_cvs = $yearly_cvs->get()->toArray();
        $yearly_cvs = array_reverse(array_values($yearly_cvs[0]));

//クリック数
        $select = '';
        for( $i=1 ; $i <= $term ; $i++ ){
            $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
            $month = date("Y年m月", strtotime('-'.$i.' month'));
            $select .= "sum(case when date='".$last_date."' then click else 0 end) as '".$i."'";
            if($i != $term){
                $select .= ',';
            }
        }
        $yearly_clicks =Monthlydata::select(DB::raw($select));
        $yearly_clicks->join('products','monthlydatas.product_id','=','products.id');
        $yearly_clicks->where('product_base_id',$id);
        $yearly_clicks->groupBy('product_base_id');
        $yearly_clicks = $yearly_clicks->get()->toArray();
        $yearly_clicks = array_reverse(array_values($yearly_clicks[0]));

//Imp数
        $select = '';
        for( $i=1 ; $i <= $term ; $i++ ){
            $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
            $month = date("Y年m月", strtotime('-'.$i.' month'));
            $select .= "sum(case when date='".$last_date."' then imp else 0 end) as '".$i."'";
            if($i != $term){
                $select .= ',';
            }
        }
        $yearly_imps =Monthlydata::select(DB::raw($select));
        $yearly_imps->join('products','monthlydatas.product_id','=','products.id');
        $yearly_imps->where('product_base_id',$id);
        $yearly_imps->groupBy('product_base_id');
        $yearly_imps = $yearly_imps->get()->toArray();
        $yearly_imps = array_reverse(array_values($yearly_imps[0]));
//承認数
        $select = '';
        for( $i=1 ; $i <= $term ; $i++ ){
            $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
            $month = date("Y年m月", strtotime('-'.$i.' month'));
            $select .= "sum(case when date='".$last_date."' then approval else 0 end) as '".$i."'";
            if($i != $term){
                $select .= ',';
            }
        }
        $yearly_approvals =Monthlydata::select(DB::raw($select));
        $yearly_approvals->join('products','monthlydatas.product_id','=','products.id');
        $yearly_approvals->where('product_base_id',$id);
        $yearly_approvals->groupBy('product_base_id');
        $yearly_approvals = $yearly_approvals->get()->toArray();
        $yearly_approvals = array_reverse(array_values($yearly_approvals[0]));
//承認率
        $select = '';
        for( $i=1 ; $i <= $term ; $i++ ){
            $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
            $month = date("Y年m月", strtotime('-'.$i.' month'));
            $select .= "sum(case when date='".$last_date."' then approval_rate else 0 end) as '".$i."'";
            if($i != $term){
                $select .= ',';
            }
        }
        $yearly_approval_rates =Monthlydata::select(DB::raw($select));
        $yearly_approval_rates->join('products','monthlydatas.product_id','=','products.id');
        $yearly_approval_rates->where('product_base_id',$id);
        $yearly_approval_rates->groupBy('product_base_id');
        $yearly_approval_rates = $yearly_approval_rates->get()->toArray();
        $yearly_approval_rates = array_reverse(array_values($yearly_approval_rates[0]));

//CTR
        foreach ($yearly_imps as $key => $value) {

            $yearly_ctrs[$key] = ($yearly_clicks[$key]!=0 || $value!=0)? $yearly_clicks[$key] / $value * 100 : 0 ; 
        
        }
//CVR
        foreach ($yearly_clicks as $key => $value) {

            $yearly_cvrs[$key] = ($yearly_cvs[$key]!=0 || $value!=0)? $yearly_cvs[$key] / $value * 100 : 0 ; 
        
        }
        $product_bases = ProductBase::all();

        //グラフ数値
        $chart_data = Monthlydata::select(['name', 'imp', 'click','cv'])
        ->join('products','monthlydatas.product_id','=','products.id')
        ->join('asps','products.asp_id','=','asps.id');

        if(!empty($id)){
            $chart_data->where('products.product_base_id', $id);
        }
        if(!empty($searchdate)){
            $chart_data->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
        }
        $chart_data = $chart_data->get();
        //var_dump($asps);
        //各ASP毎の年間数値
        $asps = Product::Select('asp_id','name')->join('asps','products.asp_id','=','asps.id')->where('product_base_id', $id)->where('products.killed_flag',0)->get()->toArray();

        foreach ($asps as $asp) {
            //echo $asp["asp_id"];
        //成果発生数
            $key = $asp["asp_id"];
                $select = '';
                for( $i=1 ; $i <= $term ; $i++ ){
                    $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                    $month = date("Y年m月", strtotime('-'.$i.' month'));
                    $select .= "sum(case when date='".$last_date."' then cv else 0 end) as '".$i."'";
                    if($i != $term){
                        $select .= ',';
                    }
                }
                $yearly_cvs_asp[$key] =Monthlydata::select(DB::raw($select));
                $yearly_cvs_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
                $yearly_cvs_asp[$key]->where('product_base_id',$id);
                $yearly_cvs_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_cvs_asp[$key]->groupBy('product_base_id');
                $yearly_cvs_asp[$key] = $yearly_cvs_asp[$key]->get()->toArray();
                $yearly_cvs_asp[$key] = array_reverse(array_values($yearly_cvs_asp[$key][0]));

        //クリック数
                $select = '';
                for( $i=1 ; $i <= $term ; $i++ ){
                    $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                    $month = date("Y年m月", strtotime('-'.$i.' month'));
                    $select .= "sum(case when date='".$last_date."' then click else 0 end) as '".$i."'";
                    if($i != $term){
                        $select .= ',';
                    }
                }
                $yearly_clicks_asp[$key] =Monthlydata::select(DB::raw($select));
                $yearly_clicks_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
                $yearly_clicks_asp[$key]->where('product_base_id',$id);
                $yearly_clicks_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_clicks_asp[$key]->groupBy('product_base_id');
                $yearly_clicks_asp[$key] = $yearly_clicks_asp[$key]->get()->toArray();
                $yearly_clicks_asp[$key] = array_reverse(array_values($yearly_clicks_asp[$key][0]));

        //Imp数
                $select = '';
                for( $i=1 ; $i <= $term ; $i++ ){
                    $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                    $month = date("Y年m月", strtotime('-'.$i.' month'));
                    $select .= "sum(case when date='".$last_date."' then imp else 0 end) as '".$i."'";
                    if($i != $term){
                        $select .= ',';
                    }
                }
                $yearly_imps_asp[$key] =Monthlydata::select(DB::raw($select));
                $yearly_imps_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
                $yearly_imps_asp[$key]->where('product_base_id',$id);
                $yearly_imps_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_imps_asp[$key]->groupBy('product_base_id');
                $yearly_imps_asp[$key] = $yearly_imps_asp[$key]->get()->toArray();
                $yearly_imps_asp[$key] = array_reverse(array_values($yearly_imps_asp[$key][0]));
        //承認数
                $select = '';
                for( $i=1 ; $i <= $term ; $i++ ){
                    $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                    $month = date("Y年m月", strtotime('-'.$i.' month'));
                    $select .= "sum(case when date='".$last_date."' then approval else 0 end) as '".$i."'";
                    if($i != $term){
                        $select .= ',';
                    }
                }
                $yearly_approvals_asp[$key] =Monthlydata::select(DB::raw($select));
                $yearly_approvals_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
                $yearly_approvals_asp[$key]->where('product_base_id',$id);
                $yearly_approvals_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_approvals_asp[$key]->groupBy('product_base_id');
                $yearly_approvals_asp[$key] = $yearly_approvals_asp[$key]->get()->toArray();
                $yearly_approvals_asp[$key] = array_reverse(array_values($yearly_approvals_asp[$key][0]));
        //承認率
                $select = '';
                for( $i=1 ; $i <= $term ; $i++ ){
                    $last_date = date("Y-m-t", strtotime('-'.$i.' month'));
                    $month = date("Y年m月", strtotime('-'.$i.' month'));
                    $select .= "sum(case when date='".$last_date."' then approval_rate else 0 end) as '".$i."'";
                    if($i != $term){
                        $select .= ',';
                    }
                }
                $yearly_approval_rates_asp[$key] =Monthlydata::select(DB::raw($select));
                $yearly_approval_rates_asp[$key]->join('products','monthlydatas.product_id','=','products.id');
                $yearly_approval_rates_asp[$key]->where('product_base_id',$id);
                $yearly_approval_rates_asp[$key]->where('monthlydatas.asp_id',$asp["asp_id"]);
                $yearly_approval_rates_asp[$key]->groupBy('product_base_id');
                $yearly_approval_rates_asp[$key] = $yearly_approval_rates_asp[$key]->get()->toArray();
                $yearly_approval_rates_asp[$key] = array_reverse(array_values($yearly_approval_rates_asp[$key][0]));

        //CTR
                foreach ($yearly_imps_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_clicks_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_ctrs_asp[$key][$k] = ($val1 == 0 || $val2 ==0 )? 0 : $val1 / $val2 * 100; 
                
                }
        //CVR
                foreach ($yearly_clicks_asp[$key] as $k => $val) {
                    //var_dump($val);
                    $val1 = (integer)$yearly_cvs_asp[$key][$k];
                    $val2 = (integer)$val;
                    $yearly_cvrs_asp[$key][$k] = ($val1 == 0 || $val2 == 0 )? 0 : $val1 / $val2 * 100;
                
                }
        }//foreach
        $yearly_chart= $this->calChart($id,$term);
        //return view('pdf.yearly', compact('asps','yearly_cvs','yearly_clicks','yearly_imps','yearly_approvals','yearly_cvrs','yearly_ctrs','yearly_cvs_asp','yearly_clicks_asp','yearly_imps_asp','yearly_ctrs_asp','yearly_cvrs_asp','yearly_chart'));
        
        $pdf = PDF::loadView($bladeFile, compact('asps','yearly_cvs','yearly_clicks','yearly_imps','yearly_approvals','yearly_cvrs','yearly_ctrs',
                'yearly_cvs_asp','yearly_clicks_asp','yearly_imps_asp','yearly_ctrs_asp','yearly_cvrs_asp','yearly_chart'));

        $header_html = '
        <!DOCTYPE html>
        <html lang="ja">
          <head>
            <meta charset="UTF-8">
            <style type="text/css">
              body { 
                padding: 10px;
                font-size: 20px;
                font-family: "ＭＳ ゴシック",sans-serif;
                text-align: center;
              }
            </style>
          </head>
          <body>
            '.$product[0]['product_name'].' '.$header.' ('.$today.')</body>
        </html>';
        
        $footer_html = '<!DOCTYPE html>
                <html>
                  <head>
                  <style type="text/css">
                    body { text-align: center; font-size: 15px; font-family: "ＭＳ ゴシック",sans-serif; }
                  </style>
                  </head>
                  <body>
                         Page <span id="page"></span> of 
                        <span id="topage"></span>   
                        <script> 
                          var vars={};
                          var x=window.location.search.substring(1).split("&");

                          for (var i in x) {
                            var z=x[i].split("=",2);
                            vars[z[0]] = unescape(z[1]);
                          }
                          document.getElementById("page").innerHTML = vars.page; 
                          document.getElementById("topage").innerHTML = vars.topage;

                        </script> 
                  </body>
                </html>';


        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 5000);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        $pdf->setOption('encoding', 'utf-8');

        $pdf->setOption('header-html', $header_html);
        $pdf->setOption('footer-html', $footer_html);

        $pdf->setOption('orientation', 'Landscape');

        //return view('pdf.media',compact('products','site_ranking'));
        return $pdf->inline();
        //return $pdf->download('sample.pdf'); 

    }
    public function calChart($product,$term){

        $date = array();
        
        $aspinfo = Product::Select('asp_id','asps.name')->join('asps','products.asp_id','=','asps.id')->where('product_base_id',$product)->get()->toArray();
        //var_dump($aspinfo);
        for ($i = 1 ; $i <= $term ; $i++ ) {
            array_push($date, date('Y-m-t',strtotime('-'.$i.' month')) ); 
        }
        $select = 'date ,';
        foreach( $aspinfo as $val){
            $select .= "sum(case when monthlydatas.asp_id='".$val['asp_id']."' then cv else 0 end) as '".$val['name']."'";
            if($val !== end($aspinfo)) {
                $select .= ', ';
            }else{
                $select .= ',SUM(cv) as "合計"';
                
            }
        }

        //var_dump($select);
        $yearly_chart =Monthlydata::select(DB::raw($select));
        $yearly_chart->join('products','monthlydatas.product_id','=','products.id');
        $yearly_chart->where('product_base_id',$product);
        $yearly_chart->whereIn('date',$date);
        $yearly_chart->groupBy('date');
        $sql = $yearly_chart->toSql();
        //var_dump($sql);
        $yearly_chart = $yearly_chart->get()->toArray();
        $i = 0;
        
        return json_encode($yearly_chart);

    }
    /**
        メディア
    */
    function pdf_media($id,$month = null ){

        $user = Auth::user();

        if($user->product_base_id !== $id){
            return redirect('/export');
        }
        //$pdf = app('dompdf.wrapper');
        $data = array();
        //$date = date('Y-m' ,strtotime(''));
        $ratio = (date("d")/date("t"));
        //*毎月一日の場合は、先月データと先々月データを参照
        $product = ProductBase::Where('id',$id)->get()->toArray();
        $today = date("Y年m月d日");

        if($month == 'one_month' ){//先月分
            $month = date('Ym',strtotime('-1 month'));
            $searchdate = date('Y-m-t', strtotime('-1 month'));
            $header = '先月 ['.date("Y年m月",strtotime("-1 month")) .'] メディア別成果レポート' ;
        }else{
            $month = date('Ym',strtotime('-1 day'));
            $searchdate = date("Y-m-d", strtotime('-1 day'));
            $header = '今月 ['.date("Y年m月") .'] メディア別成果レポート' ;
        }

        $monthlysites_table = $month.'_monthlysites';

        $products = DB::table($monthlysites_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id','price','cpa','cost','estimate_cv','date','approval','approval_price','approval_rate'])
                    ->join('products',DB::raw($monthlysites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    
                    if(!empty($id)){
                        $products->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $products->where('date', 'LIKE' , "%".$searchdate."%");
                    }
                    $products->groupBy("media_id");
                    $products->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');
                    
                    $products->limit(50);
                    $products = $products->get();

                    //var_dump($products);
        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        $site_ranking = $this->monthly_ranking_site($id,$searchdate);
        //var_dump($site_ranking);
        //return view('pdf.media',compact('products','site_ranking'));
        $header_html = '
        <!DOCTYPE html>
        <html lang="ja">
          <head>
            <meta charset="UTF-8">
            <style type="text/css">
              body { 
                padding: 10px;
                font-size: 20px;
                font-family: "ＭＳ ゴシック",sans-serif;
                text-align: center;
              }
            </style>
          </head>
          <body>
            '.$product[0]['product_name'].' '.$header.' ('.$today.')</body>
        </html>';
        
        $footer_html = '<!DOCTYPE html>
                <html>
                  <head>
                  <style type="text/css">
                    body { text-align: center; font-size: 15px; font-family: "ＭＳ ゴシック",sans-serif; }
                  </style>
                  </head>
                  <body>
                         Page <span id="page"></span> of 
                        <span id="topage"></span>   
                        <script> 
                          var vars={};
                          var x=window.location.search.substring(1).split("&");

                          for (var i in x) {
                            var z=x[i].split("=",2);
                            vars[z[0]] = unescape(z[1]);
                          }
                          document.getElementById("page").innerHTML = vars.page; 
                          document.getElementById("topage").innerHTML = vars.topage;

                        </script> 
                  </body>
                </html>';
        $pdf = PDF::loadView('pdf.media',compact('products','site_ranking'));
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 5000);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOption('orientation', 'Landscape');
        $pdf->setOption('header-html', $header_html);
        $pdf->setOption('footer-html', $footer_html);
        return $pdf->inline();

    }
    /**
　    月別ランキング一覧取得
    */
    public function monthly_ranking_site($id ,$searchdate = null) {
                /*
                    案件ｘ対象期間からCVがTOP10のサイトを抽出
                */
                $month = date('Ym',strtotime($searchdate));
                $monthly_sites_table = $month.'_monthlysites';

                $products = DB::table($monthly_sites_table)
                //$products = Monthlysite::
                    ->select(DB::raw("cv , media_id, site_name"))
                    
                    ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    
                    ->where('cv', '!=' , 0 );

                    if(!empty($id)){
                        $products->where('product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        //今月の場合
                        if(strpos($searchdate,date("Y-m", strtotime('-1 day'))) === false ){
                            $searchdate= date("Y-m-t",strtotime($searchdate));
                        }
                        $products->where('date' , $searchdate );
                    }
                    //echo $searchdate;
                    $products->groupBy("media_id");
                    $products->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');

                    $products->limit(10);
                    //echo $products->toSql();
                    $products = $products->get();
                    return json_encode($products);

    }
    public function excel()
    {
        $id = 3 ;
        $searchdate_start = '2019-07-01';
        $searchdate_end = '2019-07-05';

            $csvHeader =['日付','ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV', 'Active', 'Partnership','Price','CPA','予想CV'];
            $csvData= DailyDiff::
                select(['daily_diffs.date','name','products.id','products.product', 'imp', 'ctr', 'click', 'cvr','cv', 'active', 'partnership','price','cpa','estimate_cv'])
                ->join('products','daily_diffs.product_id','=','products.id')
                ->join('asps','products.asp_id','=','asps.id');
            if(!empty($id)){
                $csvData->where('product_base_id', $id);
                $product = ProductBase::Where('id',$id)->get()->toArray();
            }
            if(!empty($searchdate_start)){
                $csvData->where('daily_diffs.date', '>=' , $searchdate_start);
                $s_data = str_replace('-', '', $searchdate_start);
            }
            if(!empty($searchdate_end)){
                $csvData->where('daily_diffs.date', '<=' , $searchdate_end );
                $e_data = str_replace('-', '', $searchdate_end);
            }
        
        $csvData =$csvData->get();

        Excel::create('plants', function($excel) use($csvData) {
            $excel->sheet('Sheet 1', function($sheet) use($csvData) {
                $sheet->fromArray($csvData);
            });
        })->export('xls');
    }
    /**
        デイリーレポートの一覧のCSV出力用の関数
    */
    public function downloadDaily( $id ,$searchdate_start = null,$searchdate_end  = null)
    {
        //$date = urldecode($date);
                
                $searchdate_start = urldecode($searchdate_start);
                $searchdate_end = urldecode($searchdate_end);

                $csvHeader =['日付','ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV', 'Active', 'Partnership','Price','CPA','予想CV'];
                //$users = \User::where('type', '=', '1')->get(['name', 'birth_day'])->toArray();

                //array_unshift($data, $csvHeaders);
                $csvData= DailyDiff::
                    select(['daily_diffs.date','name','products.id','products.product', 'imp', 'ctr', 'click', 'cvr','cv', 'active', 'partnership','price','cpa','estimate_cv'])
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $csvData->where('product_base_id', $id);
                        $product = ProductBase::Where('id',$id)->get()->toArray();
                    }
                    if(!empty($searchdate_start)){
                        $csvData->where('daily_diffs.date', '>=' , $searchdate_start);
                        $s_data = str_replace('-', '', $searchdate_start);
                    }
                    if(!empty($searchdate_end)){
                        $csvData->where('daily_diffs.date', '<=' , $searchdate_end );
                        $e_data = str_replace('-', '', $searchdate_end);
                    }
                    //var_dump($product[0]['product_name']);
                    //$csv = $csv->toSql();
                    $csvData =$csvData->get()->toArray();
                    //var_dump($csvData);
                    //echo $csvData;
                    return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$s_data.'_'.$e_data.'_daily.csv');
    }
    /**
        デイリーレポートのサイト別一覧のCSV出力用の関数

    */
    public function downloadSiteDaily( $id ,$searchdate_start = null,$searchdate_end  = null )
    {
                
        $searchdate_start = urldecode($searchdate_start);
        $searchdate_end = urldecode($searchdate_end);

        $csvHeader= array();
        $csvHeader = ['日付','ASP', 'MediaID','サイト名', '案件名','案件ID','imp', 'CTR', 'click', 'CVR','CV','FDグロス','CPA','予想CV'];

        //開始月と終了月が同月の場合
        if(date('m',strtotime($searchdate_start)) == date('m',strtotime($searchdate_end))) {
            $month_1 = date('Ym',strtotime($searchdate_start));
        //月を跨いでいる場合
        }else{
            
            $month_1 = date('Ym',strtotime($searchdate_start));
            $month_2 = date('Ym',strtotime($searchdate_end));
            
            $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
            //終了月の検索　クエリビルダ
            $table2 = DB::table($daily_site_diffs_table2)
                    ->select(['date','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','cost','cpa','estimate_cv'])
                    ->join('products',DB::raw($daily_site_diffs_table2.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $table2->where('product_base_id', $id);
                    }
                    if(!empty($searchdate_start)){
                        $table2->where('date', '>=' , $searchdate_start );
                        
                    }
                    if(!empty($searchdate_end)){
                        $table2->where('date', '<=' , $searchdate_end );
                    }
        }

        $daily_site_diffs_table = $month_1.'_daily_site_diffs';

        //開始月の検索　クエリビルダ
        $csvData = DB::table($daily_site_diffs_table)
                    ->select(['date','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','cost','cpa','estimate_cv'])
                    
                    ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');

                    if(!empty($id)){
                        $csvData->where('product_base_id', $id);
                        $product = ProductBase::Where('id',$id)->get()->toArray();
                    }
                    if(!empty($searchdate_start)){
                        $csvData->where('date', '>=' , $searchdate_start );
                        $s_data = str_replace('-', '', $searchdate_start);
                    }
                    if(!empty($searchdate_end)){
                        $csvData->where('date', '<=' , $searchdate_end );
                        $e_data = str_replace('-', '', $searchdate_end);
                    }
                    if(!empty($table2)){
                        $csvData->union($table2);
                    }
                    $csvData = $csvData->get()->toArray();
                    $csvData = json_decode(json_encode($csvData), true);
                    //var_dump($csvData);
                    //echo gettype($csvHeader);
                    return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$s_data.'_'.$e_data.'_daily_site.csv');
    }
    /*
        案件期間内のASPの別CV数の計算関数
    */
    public function daily_ranking_asp($id = 3,$searchdate_start = null,$searchdate_end = null ) {
        /*
            案件ｘ対象期間から対象案件のCV件数
        */
            $sql = 'Select DATE_FORMAT(date,"%Y/%m/%d") as date';
            $sql_select_asp = "";
            $asp_data = $this->filterAsp($id);

            $asp_array = (json_decode($asp_data,true));
            //echo gettype($asp_id);
            foreach ($asp_array as $asp){
                    $sql_select_asp.=",max( case when asp_id=".$asp['asp_id']." then cv end ) as ".str_replace(' ', '' ,$asp["name"]);
            }
            $sql = $sql.$sql_select_asp;
            $sql .= ' From daily_diffs ';
            if($id != '' ){
                $where = " where ";

                $product_list = $this->convertProduct($id);
                //var_dump($product_list);

                $where .= " product_id in (";
                foreach ($product_list as $product) {
                    $where .= $product['id'];

                    if($product !== end($product_list)){
                        $where .= ",";
                    }
                }
                $where .= " )";
                
            }
            if($searchdate_start != '' ){
                if($where !== ''){
                    $where .= " and ";
                }else{
                    $where = " where ";
                }
                $where .= " date >= '". $searchdate_start ."'";
            }
            if($searchdate_end != '' ){
                if($where !== ''){
                    $where .= " and ";
                }else{
                    $where = " where ";
                }
                $where .= " date <= '". $searchdate_end ."'";
            }
            /*
            if($asp_id != '' ){
                if($where !== ''){
                    $where .= " and ";
                }else{
                    $where = " where ";
                }
                $where .= " asp_id = ". $asp_id ;
            }*/
            //echo $where;
            if($where !== '') $sql.= $where ;
            $sql .=' Group By  DATE_FORMAT(date,"%Y/%m/%d")';

            $products = DB::select($sql);

            return json_encode($products);

    }
}
