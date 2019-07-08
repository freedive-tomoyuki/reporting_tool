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
                    //var_dump($csvData);
                    //echo gettype($csvHeader);
                    return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$s_data.'_'.$e_data.'_daily_site.csv');
    }
}
