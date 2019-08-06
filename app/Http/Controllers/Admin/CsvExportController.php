<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Components\CSV;


use App\Product;
use App\ProductBase;
use App\Asp;
use App\Monthlydata;
use App\DailyDiff;
use App\DailySiteDiff;
use Illuminate\Support\Facades\Auth; 
use DB;
/*
　デイリーデータ用　クラス
*/
class CsvExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
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
                    var_dump($csvData);
                    //var_dump($csvData);
                    //echo gettype($csvHeader);
                    return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$s_data.'_'.$e_data.'_daily_site.csv');
    }
    /**
        マンスリーレポートの一覧のCSV出力用の関数（実績値）
    */
    public function downloadMonthly( $id ,$month )
    {

            if( $month == date("Y-m")){
                $searchdate = date("Y-m-d", strtotime('-1 day'));
                $search_last_date = date("Y-m-t", strtotime('-1 month'));
            }else{
                $searchdate = date('Y-m-d', strtotime('last day of ' . $month));
                $month = date('Y-m',strtotime('-1 month'));
                $search_last_date = date('Y-m-t', strtotime('last day of ' . $month));
            }
            $csvHeader =['ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV', 'Active', 'Partnership','Price','CPA','承認件数','承認金額','承認率','前月CV（前月比）'];

            /**
                当月の実績値
            */
            $csvData = Monthlydata::select([
                'asps.name',
                'products.id',
                'products.product',
                'imp',
                'ctr',
                'click', 
                'cvr',
                'cv', 
                'active', 
                'partnership',
                'price',
                'cpa',
                'approval',
                'approval_price',
                'approval_rate',
                'last_cv'
            ])
                            ->join('products','monthlydatas.product_id','=','products.id')
                            ->join('asps','products.asp_id','=','asps.id')
                            ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$search_last_date."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id');

                    if(!empty($id)){
                        $csvData->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $csvData->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $csvData = $csvData->get()->toArray();
                    $product = ProductBase::Where('id',$id)->get()->toArray();
                /**
                    当月の実績値トータル
                */

            $productsTotals = Monthlydata::select(DB::raw("
                        '合計',
                        products.id,
                        products.product,
                        sum(imp) as total_imp,
                        sum(click) as total_click,
                        sum(cv) as total_cv,
                        sum(active) as total_active,
                        sum(partnership) as total_partnership,
                        sum(price) as total_price ,
                        sum(approval) as total_approval, 
                        sum(approval_price) as total_approval_price,
                        '承認率',
                        sum(last_cv) as total_last_cv
                        "))
                   ->join('products','monthlydatas.product_id','=','products.id')
                   ->join('asps','products.asp_id','=','asps.id')
                   ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$search_last_date."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id');
                    if(!empty($id)){
                        $productsTotals->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsTotals->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsTotals = $productsTotals->get()->toArray();

                    $ctr = ($productsTotals[0]['total_imp'] != 0 || $productsTotals[0]['total_click'] != 0)?$productsTotals[0]['total_click']/$productsTotals[0]['total_imp'] * 100 : 0 ;
                    $cvr = ($productsTotals[0]['total_cv'] != 0 || $productsTotals[0]['total_click'] != 0)?$productsTotals[0]['total_cv']/$productsTotals[0]['total_click'] * 100 : 0 ;
                    $cpa = ($productsTotals[0]['total_approval_price'] != 0 || $productsTotals[0]['total_cv'] != 0)?$productsTotals[0]['total_approval_price']/$productsTotals[0]['total_cv']  : 0 ;
                    
                    array_splice($productsTotals[0], 4, 0, $ctr);
                    array_splice($productsTotals[0], 6, 0, $cvr);
                    array_splice($productsTotals[0], 11, 0, $cpa);
                    
                    return CSV::download(array_merge($csvData ,$productsTotals), $csvHeader, $product[0]['product_name'].'_'.$month.'_daily.csv');
    }
    /**
        マンスリーレポートの一覧のCSV出力用の関数（想定値）
    */
    public function downloadMonthlyEstimate( $id )
    {

        $searchdate = date("Y-m-d", strtotime('-1 day'));
        $month = date('Y-m');
        $csvHeader =['ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV','Price'];
        $ratio = (date("d")/date("t"));

        $productsEstimates = Monthlydata::select(DB::raw("
            asps.name,
            products.id as product_id,
            products.product,
            (imp/". $ratio .") as estimate_imp,
            ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr, 
            (click/". $ratio .") as estimate_click,
            ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
            (cv/". $ratio .") as estimate_cv,
            (price/". $ratio .") as estimate_cost
            "))
                    ->join('products','monthlydatas.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $productsEstimates->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $productsEstimates->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $productsEstimates=$productsEstimates->get()->toArray();
                    //->toArray();
            /**
                当月の着地想定トータル
            */
       $productsEstimateTotals = DB::table(
                                DB::raw("
                                    (select (imp/". $ratio .") as estimate_imp,
                                    (click/". $ratio .") as estimate_click,
                                    (cv/". $ratio .") as estimate_cv,
                                    ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                                    ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr,
                                    (price/". $ratio .") as estimate_cost,
                                    products.product as product_name,
                                    products.id as product_id 
                                     from monthlydatas
                                    inner join products on monthlydatas.product_id = products.id
                                    where products.product_base_id = ".$id."
                                    and monthlydatas.date LIKE '%".$searchdate."%') as estimate_table")
                          )
                        ->select(DB::raw("'合計', product_id, product_name,
                        sum(estimate_imp) as total_estimate_imp,'CTR',
                        sum(estimate_click) as total_estimate_click,'CVR',
                        sum(estimate_cv) as total_estimate_cv,
                        sum(estimate_cost) as total_estimate_cost"))->get()->toArray();

        $product = ProductBase::Where('id',$id)->get()->toArray();

        $productsEstimateTotals = json_decode(json_encode($productsEstimateTotals), true);
        //echo '<pre>';
        //var_dump(array_merge($productsEstimates ,$productsEstimateTotals));
        //echo '</pre>';
        return CSV::download(array_merge($productsEstimates ,$productsEstimateTotals) , $csvHeader, $product[0]['product_name'].'_'.$month.'_daily.csv');
    }
    /**
        マンスリーレポートのサイト別一覧のCSV出力用の関数
    */
    public function downloadSiteMonthly( $id ,$month  )
    {
                
        $searchdate = urldecode($month);
        $month_table = date('Ym',strtotime($searchdate));

        $csvHeader= array();
        $csvHeader = ['日付','ASP', 'MediaID','サイト名', '案件名','案件ID','imp', 'CTR', 'click', 'CVR','CV','FDグロス','CPA','予想CV'];


        $monthlysites_table = $month_table.'_monthlysites';

        //開始月の検索　クエリビルダ
        $csvData = DB::table($monthlysites_table)
                    ->select(['date','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','cost','cpa','estimate_cv'])
                    
                    ->join('products',DB::raw($monthlysites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');

                    if(!empty($id)){
                        $csvData->where('product_base_id', $id);
                        $product = ProductBase::Where('id',$id)->get()->toArray();
                    }
                    if(!empty($searchdate)){
                        $csvData->where('date', '=' , $searchdate );
                        $e_data = str_replace('-', '', $searchdate);
                    }

                    $csvData = $csvData->get()->toArray();
                    $csvData = json_decode(json_encode($csvData), true);
                    //var_dump($csvData);
                    //echo gettype($csvHeader);
                    return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$month_table.'_monthly_site.csv');
    }
}
