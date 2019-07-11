<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Components\CSV;


use App\Product;
use App\ProductBase;
use App\Asp;
use App\DailyDiff;
use App\DailySiteDiff;
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
        $this->middleware('auth:admin');
    }
    public function index()
    {
        $user = Auth::user();
        $products = Product::all();
        $product_bases = ProductBase::Where('killed_flag',0)->get();
        return view('admin.export',compact('product_bases','user'));
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
        return view('admin.export',compact('product_bases','user'));
    }
    public function pdf(){
        
        //$pdf = app('dompdf.wrapper');
        $data = array();

        $products = DailyDiff::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','date','daily_diffs.created_at','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', 3)
                    //->where('daily_diffs.date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%")
                    ->where('daily_diffs.date', '>=' , date("Y-m-1",strtotime('-1 day')))
                    ->where('daily_diffs.date', '<=' , date("Y-m-d",strtotime('-1 day')))
                    ->get();
                    
        $total = DailyDiff::select(DB::raw("date,products.id, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(estimate_cv) as total_estimate_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price "))
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', 3)
                    //->where('daily_diffs.date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%")
                    ->where('daily_diffs.date', '>=' , date("Y-m-1",strtotime('-1 day')))
                    ->where('daily_diffs.date', '<=' , date("Y-m-d",strtotime('-1 day')))
                    ->get();
        $total_chart = DailyDiff::select(DB::raw("date, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv"))
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', 3)
                    //->where('daily_diffs.date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%")
                    ->where('daily_diffs.date', '>=' , date("Y-m-1",strtotime('-1 day')))
                    ->where('daily_diffs.date', '<=' , date("Y-m-d",strtotime('-1 day')));
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
        $daily_ranking = $this->daily_ranking_asp(3,date("Y-m-1",strtotime('-1 day')),date("Y-m-d",strtotime('-1 day')));

        $pdf= PDF::loadView('pdf.pdf', compact('products','product_bases','total','total_chart','daily_ranking'));
        
        return $pdf->download('sample.pdf'); 
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
    public function daily_ranking_asp($id = 3,$searchdate_start = null,$searchdate_end = null ,$asp_id=null) {
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
            if($asp_id != '' ){
                if($where !== ''){
                    $where .= " and ";
                }else{
                    $where = " where ";
                }
                $where .= " asp_id = ". $asp_id ;
            }
            //echo $where;
            if($where !== '') $sql.= $where ;
            $sql .=' Group By  DATE_FORMAT(date,"%Y/%m/%d")';

            $products = DB::select($sql);

            return json_encode($products);

    }
}
