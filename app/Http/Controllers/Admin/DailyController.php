<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;

//use App\Dailydata;
//use App\Dailysite;
use App\Product;
use App\ProductBase;
use App\Asp;
use App\DailyDiff;
use App\DailySiteDiff;
use App\Http\Requests\SearchDailyRequest;
use App\Http\Requests\SearchDailySiteRequest;
use Illuminate\Support\Facades\Auth; 
use DB;

/*
　デイリーデータ用　クラス
*/
class DailyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
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
    public function dailyResult() {
        $user = Auth::user();
        /**
        * プロダクト一覧を全て取得
        */
        $product_bases = ProductBase::all();
        /**
        * ASP一覧を全て取得
        */
        $asps = Asp::all();
        $data = array();
        /**
        * Eloquentを利用して、dailydatasテーブルから案件ID＝１の昨日データ取得する。
        * 
        */
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
                    //->get();
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
        //var_dump($data);
        $total_chart = json_encode($data);
        //var_dump($total_chart);

        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        $daily_ranking = $this->dailyRankingAsp(3,date("Y-m-1",strtotime('-1 day')),date("Y-m-d",strtotime('-1 day')));
        //var_dump($daily_ranking);
        //var_dump($products);
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
            return view('admin.daily_error',compact('product_bases','asps','user','total'));
        }else{
            return view('admin.daily',compact('products','product_bases','asps','daily_ranking','user','total','total_chart'));
        }
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
        $data = array();
        /**
        * プロダクト一覧を全て取得
        */
        $product_bases = ProductBase::all();
        /**
        * ASP一覧を全て取得
        */  
        $asps = Asp::all();

        $i = 0;

        $id = ($request->product != null)? $request->product : 1 ;
        $searchdate_start =($request->searchdate_start != null)? $request->searchdate_start : date("Y-m-d",strtotime('-1 day'));
        $searchdate_end =($request->searchdate_end != null)? $request->searchdate_end : date("Y-m-d",strtotime('-1 day'));
        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;

        $products = DailyDiff::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','date','daily_diffs.created_at','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $products->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate_start)){
                        $products->where('daily_diffs.date', '>=' , $searchdate_start);
                    }
                    if(!empty($searchdate_end)){
                        $products->where('daily_diffs.date', '<=' , $searchdate_end );
                    }
        $products = $products->get();
/*        echo '<pre>';
        var_dump($products->toArray());
        echo '</pre>';*/

        $total = DailyDiff::select(DB::raw("date,products.id, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(estimate_cv) as total_estimate_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(price) as total_price "))
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $total->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $total->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate_start)){
                        $total->where('daily_diffs.date', '>=' , $searchdate_start);
                    }
                    if(!empty($searchdate_end)){
                        $total->where('daily_diffs.date', '<=' , $searchdate_end );
                    }
        $total = $total->get();

        $total_chart = DailyDiff::select(DB::raw("date, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv"))
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $total_chart->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $total_chart->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate_start)){
                        $total_chart->where('daily_diffs.date', '>=' , $searchdate_start);
                    }
                    if(!empty($searchdate_end)){
                        $total_chart->where('daily_diffs.date', '<=' , $searchdate_end );
                    }

        $total_chart = $total_chart->groupby('date')->get()->toArray();


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
        $daily_ranking = $this->dailyRankingAsp($id,$searchdate_start,$searchdate_end,$asp_id);
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
            return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
            return view('admin.daily',compact('products','product_bases','asps','daily_ranking','user','total','total_chart'));
        }


    }
    /**
    *サイト別デイリーレポートのデフォルトページを表示。
    *表示データがない場合、エラーページを表示する。
    *
    *@return view
    *
    */

    public function dailyResultSite() {
        $user = Auth::user();
        $month = date('Ym',strtotime('-1 day'));
        $daily_site_diffs_table = $month.'_daily_site_diffs';

        /**
        * プロダクト一覧を全て取得
        */
        $product_bases = ProductBase::all();
        /**
        * ASP一覧を全て取得
        */  
        $asps = Asp::all();

        $products = DB::table($daily_site_diffs_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    ->where('product_base_id', 3)
                    //->where('date', 'LIKE' , "%".date("Y-m-d",strtotime('-1 day'))."%")
                    ->where('date', '>=' , date("Y-m-01",strtotime('-1 day'))) 
                    ->where('date', '<=' , date("Y-m-d",strtotime('-1 day')))
                    ->orderBy('cv','desc')
                    ->limit(2500)
                    ->get();

        //var_dump($products);
        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        $site_ranking = $this->dailyRankingSite(3,date("Y-m-01",strtotime('-1 day')),date("Y-m-d",strtotime('-1 day')));
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
           return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
           return view('admin.daily_site',compact('products','product_bases','asps','site_ranking','user'));
        }
    }
    /**
    *サイト別デイリーレポートの検索結果ページを表示。
    *表示データがない場合、エラーページを表示する。
    *@param request $request 検索データ（ASPID、日時（はじめ、おわり）案件ID）
    *@return view
    *
    */

    public function dailyResultSiteSearch(SearchDailySiteRequest $request) {
        $user = Auth::user();
        $request->flash();
        $table2 = ''; //初期値

        $id = ($request->product != null)? $request->product : 1 ;
        $searchdate_start =($request->searchdate_start != null)? $request->searchdate_start : date("Y-m-d",strtotime('-1 day'));
        $searchdate_end =($request->searchdate_end != null)? $request->searchdate_end : date("Y-m-d",strtotime('-1 day'));
        $asp_id = ($request->asp_id != null)? $request->asp_id : "" ;
        
        /**
        * プロダクト一覧を全て取得
        */
        $product_bases = ProductBase::all();
        /**
        * ASP一覧を全て取得
        */  
        $asps = Asp::all();

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
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products',DB::raw($daily_site_diffs_table2.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $table2->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $table2->where('products.asp_id', $asp_id);
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
        $products = DB::table($daily_site_diffs_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id','price','cpa','cost','estimate_cv'])
                    ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');

                    if(!empty($id)){
                        $products->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate_start)){
                        $products->where('date', '>=' , $searchdate_start );
                    }
                    if(!empty($searchdate_end)){
                        $products->where('date', '<=' , $searchdate_end );
                    }
                    if(!empty($table2)){
                        $products->union($table2);
                    }
                    $products = $products->orderBy('cv','desc');
                    $products = $products->limit(2500);
                    $products = $products->get();


        /**
        * 日次のグラフ用データの一覧を取得する。
        */
        $site_ranking = $this->dailyRankingSite($id,$searchdate_start,$searchdate_end,$asp_id);
        /**
        * VIEWを表示する。
        */
        if( $products->isEmpty() ){
           return view('admin.daily_error',compact('product_bases','asps','user'));
        }else{
          return view('admin.daily_site',compact('products','product_bases','asps','site_ranking','user'));
        }


    }
    /**
        サイト別トップ１０のサイト一覧取得関数
    */
    public function dailyRankingSite($id = 3 ,$searchdate_start = null,$searchdate_end = null,$asp_id=null) {
                /*
                    案件ｘ対象期間からCVがTOP10のサイトを抽出
                    「StartとEndが同じ日」もしくは、「どちらも入力されていない」の場合・・・①
                */

                //echo "date_start".date('m', strtotime($searchdate_start));
                $date_start = date('Y-m-d', strtotime($searchdate_start));
                //echo "date_end".date('m', strtotime($searchdate_end));
                $date_end = date('Y-m-d', strtotime($searchdate_end));

                $i = 0;
                $table2 = '';

                if(date('m',strtotime($date_start)) == date('m',strtotime($date_end))) {//同月の場合
                    $month_1 = date('Ym',strtotime($date_start));
                    
                }else{//月を跨いでいる場合
                    $month_1 = date('Ym',strtotime($date_start));
                    $month_2 = date('Ym',strtotime($date_end));
                    $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
                    $table2 = DB::table($daily_site_diffs_table2)
                            ->select(DB::raw("sum(cv) as total_cv, media_id, site_name"))
                            ->join('products',DB::raw($daily_site_diffs_table2.'.product_id'),'=','products.id')
                            ->join('asps','products.asp_id','=','asps.id');
                            if(!empty($id)){
                                $table2->where('product_base_id', $id);
                            }
                            if(!empty($asp_id)){
                                $table2->where('products.asp_id', $asp_id);
                            }
                            if(!empty($date_start)){
                                $table2->where('date', '>=' , $date_start );
                            }
                            if(!empty($date_end)){
                                $table2->where('date', '<=' , $date_end );
                            }
                }
                $daily_site_diffs_table = $month_1.'_daily_site_diffs';

                $products_1 = DB::table($daily_site_diffs_table)
                    ->select(DB::raw("sum(cv) as total_cv, media_id, site_name"))
                    ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id')
                    
                    ->where('cv', '!=' , 0 );
                    if(!empty($table2)){
                        $products_1->union($table2);
                    }
                    if(!empty($id)){
                        $products_1->where('product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products_1->where('products.asp_id', $asp_id);
                    }
                    if(!empty($date_start)){
                        $products_1->where('date', '>=' , $date_start );
                    }
                    if(!empty($date_end)){
                        $products_1->where('date', '<=' , $date_end );
                    }
                    $products_1->groupBy("media_id");
                    if(!empty($table2)){
                        $products_1->orderByRaw('total_cv DESC');
                    }else{
                        $products_1->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');
                    }

                    $products_1->limit(10);
                    $products_1 = $products_1->get()->toArray();
                    
                    //var_dump($products_1);
                    return json_encode($products_1);

    }
    /*
        案件期間内のASPの別CV数の計算関数
    */
    public function dailyRankingAsp($id = 3,$searchdate_start = null,$searchdate_end = null ,$asp_id=null) {
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
        $date = urldecode(date("Y-m-d",strtotime('-1 day')));
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
