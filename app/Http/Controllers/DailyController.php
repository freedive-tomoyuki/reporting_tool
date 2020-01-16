<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


use App\Product;
use App\ProductBase;
use App\Asp;
use App\Services\DailyDataService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SearchDailyRequest;


/*
　デイリーデータ用　クラス
*/
class DailyController extends Controller
{
    private $dailyDataService;

    public function __construct(DailyDataService $dailyDataService)
    {
        //$this->middleware('guest');
        $this->middleware('auth');
        $this->dailyDataService = $dailyDataService;

    }

    /**
    　可動しているASPを精査する関数

    */
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
    /**
    *デイリーレポートのデフォルトページを表示。
    *表示データがない場合、エラーページを表示する。
    *
    *@return view
    *
    */
    public function daily_result() {
        $user = Auth::user();
        $data = array();
        //プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        // ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        $asp_id = '';

       //Eloquentを利用して、dailydatasテーブルから案件ID＝１の昨日データ取得する。
       $start   = date("Y-m-1",strtotime('-1 day'));
       $end     = date("Y-m-d",strtotime('-1 day'));

       $product_id = $user->product_base_id;

       [$daily_data ,$daily_ranking , $total , $total_chart ] = $this->dailyDataService->showList($asp_id ,$product_id , $start , $end );


        /**
        * Eloquentを利用して、dailydatasテーブルから案件ID＝１の昨日データ取得する。
        * 
        */

        //VIEWを表示する。
        // if($daily_data->isEmpty() ){
        //     return view('daily_error',compact('product_bases','asps','user'));
        // }else{
            return view('daily',compact('daily_data','product_bases','asps','daily_ranking','user','total','total_chart'));
        // }
    }
    /**
    *デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */
    public function daily_result_search(SearchDailyRequest $request) {
        $user = Auth::user();
        $request->flash();
        $data = array();
        // プロダクト一覧を全て取得
        $product_bases = ProductBase::where('killed_flag', '==' ,0 )->get();
        //ASP一覧を全て取得
        $asps = Asp::where('killed_flag', '==' ,0 )->get();
        $product_id = $user->product_base_id;

        $i = 0;

        //$id = ($request->product != null)? $request->product : 1 ;
        $start =($request->searchdate_start != null)? $request->searchdate_start : date("Y-m-d",strtotime('-1 day'));
        $end =($request->searchdate_end != null)? $request->searchdate_end : date("Y-m-d",strtotime('-1 day'));
        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;

        [$daily_data ,$daily_ranking , $total , $total_chart ] = $this->dailyDataService->showList($asp_id , $product_id, $start , $end  );

        //VIEWを表示する。
        // if($daily_data->isEmpty() ){
        //     return view('daily_error',compact('product_bases','asps','user'));
        // }else{
            return view('daily',compact('daily_data','product_bases','asps','daily_ranking','user','total','total_chart'));
        // }
    }

    /*
        案件期間内のASPの別CV数の計算関数
    */
    // public function daily_ranking_asp($id ,$searchdate_start = null,$searchdate_end = null ,$asp_id=null) {
    //     /*
    //         案件ｘ対象期間から対象案件のCV件数
    //     */
    //         $sql = 'Select DATE_FORMAT(date,"%Y/%m\/%d") as date';
    //         $sql_select_asp = "";
    //         $asp_data = $this->filterAsp($id);

    //         $asp_array = (json_decode($asp_data,true));
    //         //echo gettype($asp_id);
    //         foreach ($asp_array as $asp){
    //                 $sql_select_asp.=",max( case when asp_id=".$asp['asp_id']." then cv end ) as ".str_replace(' ', '' ,$asp["name"]);
    //         }
    //         $sql = $sql.$sql_select_asp;
    //         $sql .= ' From daily_diffs ';
    //         if($id != '' ){
    //             $where = " where ";

    //             $product_list = $this->convertProduct($id);
    //             //var_dump($product_list);

    //             $where .= " product_id in (";
    //             foreach ($product_list as $product) {
    //                 $where .= $product['id'];

    //                 if($product !== end($product_list)){
    //                     $where .= ",";
    //                 }
    //             }
    //             $where .= " )";
                
    //         }
    //         if($searchdate_start != '' ){
    //             if($where !== ''){
    //                 $where .= " and ";
    //             }else{
    //                 $where = " where ";
    //             }
    //             $where .= " date >= '". $searchdate_start ."'";
    //         }
    //         if($searchdate_end != '' ){
    //             if($where !== ''){
    //                 $where .= " and ";
    //             }else{
    //                 $where = " where ";
    //             }
    //             $where .= " date <= '". $searchdate_end ."'";
    //         }
    //         if($asp_id != '' ){
    //             if($where !== ''){
    //                 $where .= " and ";
    //             }else{
    //                 $where = " where ";
    //             }
    //             $where .= " asp_id = ". $asp_id ;
    //         }
    //         //echo $where;
    //         if($where !== '') $sql.= $where ;
    //         $sql .=' Group By  DATE_FORMAT(date,"%Y/%m/%d")';

    //         $products = DB::select($sql);

    //         return json_encode($products);

    // }
    /**
        デイリーレポートの一覧のCSV出力用の関数

    */
/*    public function downloadCSV( $id ,$searchdate_start = null,$searchdate_end  = null)
    {
        //$date = urldecode($date);

        return  new StreamedResponse(
            function () use ($id,$searchdate_start,$searchdate_end){
                //$csv = array();
                
                $date = date("Y-m-d",strtotime('-1 day'));
                $stream = fopen('php://output', 'w');

                fputcsv($stream, ['日付','ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV', 'Active', 'Partnership','Price','CPA','予想CV']);

                //array_unshift($data, $csvHeaders);
                $csv= DailyDiff::
                    select(['daily_diffs.created_at','name','products.id','products.product', 'imp', 'ctr', 'click', 'cvr','cv', 'active', 'partnership','price','cpa','estimate_cv'])
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $csv->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $csv->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate_start)){
                        $csv->where('daily_diffs.date', '>=' , $searchdate_start);
                    }
                    if(!empty($searchdate_end)){
                        $csv->where('daily_diffs.date', '<=' , $searchdate_end );
                    }


                    //$csv = $csv->toSql();
                    $csv =$csv->get()->toArray();
                    //var_dump($csv);

                foreach ($csv as $line) {
                    fputcsv($stream, $line);
                }
                fclose($stream);

            },
            200,
            [

                //'Content-Type' => 'text/csv',
                'Content-Type: application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.$id.'_dailydata.csv"',
                'Content-Transfer-Encoding: binary'
            ]
        );
    }*/
    /**
        デイリーレポートのサイト別一覧のCSV出力用の関数

    */
/*    public function downloadSiteCSV( $id ,$searchdate_start = null,$searchdate_end  = null )
    {
        $date = urldecode(date("Y-m-d"));
        return  new StreamedResponse(
            function () use ($date,$id,$searchdate_start,$searchdate_end){
                //$csv = array();
                
                $stream = fopen('php://output', 'w');

                fputcsv($stream, ['日付','ASP', 'MediaID','サイト名', '案件名','案件ID','imp', 'CTR', 'click', 'CVR','cv','Price','CPA','予想CV']);

                //array_unshift($data, $csvHeaders);
                $csv= DailySiteDiff::
                    select(['daily_site_diffs.created_at','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','price','cpa','estimate_cv'])
                    ->join('products','daily_site_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $csv->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $csv->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate_start)){
                        $csv->where('daily_site_diffs.date', '>=' , $searchdate_start );
                    }
                    if(!empty($searchdate_end)){
                        $csv->where('daily_site_diffs.date', '<=' , $searchdate_end );
                    }
                    
                    $csv = $csv->get()->toArray();
                    //var_dump($csv);

                foreach ($csv as $line) {
                    fputcsv($stream, $line);
                }
                fclose($stream);

            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$date.'_dailysites.csv"',
            ]
        );
    }*/
}
