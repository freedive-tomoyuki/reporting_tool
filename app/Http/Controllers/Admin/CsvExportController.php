<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Components\CSV;


use App\Product;
use App\ProductBase;
use App\Asp;
use App\Monthlydata;
use App\Services\DailyDataService;
use App\Services\DailySiteDataService;
use App\Services\MonthlyDataService;
use App\Services\MonthlySiteDataService;

use App\DailyDiff;
use App\DailySiteDiff;
use Illuminate\Support\Facades\Auth; 
use DB;
/*
　デイリーデータ用　クラス
*/
class CsvExportController extends Controller
{
    private $dailyDataService;
    private $monthlyDataService;
    private $dailySiteDataService;
    private $monthlySiteDataService;

    public function __construct(
        DailyDataService $dailyDataService, 
        MonthlyDataService $monthlyDataService ,
        DailySiteDataService $dailySiteDataService,
        MonthlySiteDataService $monthlySiteDataService
    )
    {
        $this->middleware('auth:admin');
        $this->dailyDataService = $dailyDataService;
        $this->monthlyDataService = $monthlyDataService;
        $this->dailySiteDataService = $dailySiteDataService;
        $this->monthlySiteDataService = $monthlySiteDataService;
    }
    /**
     * デイリーレポートの一覧のCSV出力用の関数
     *
     * @param [type] $id
     * @param [type] $searchdate_start
     * @param [type] $searchdate_end
     * @return void
     */
    public function downloadDaily( Request $request )
    {
        $p = ($request->p) ? $request->p : '' ;                 
        $asp_id = ($request->asp) ? $request->asp : '' ; 
        $start = ($request->s_date) ? urldecode($request->s_date) : '' ; 
        $end =  ($request->e_date) ?  urldecode($request->e_date) : '' ;

        $csvHeader =['日付','ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV', 'Active', 'Partnership','Price','CPA'];
                //$users = \User::where('type', '=', '1')->get(['name', 'birth_day'])->toArray();

        $csvData = $this->dailyDataService->showCsv($asp_id , $p , $start , $end );
        $product = ProductBase::Where('id',$p)->get()->toArray();
        $s_data = str_replace('-', '', $start);
        $e_data = str_replace('-', '', $end);
        // echo "<pre>";
        // var_dump($csvData); 
        // echo "</pre>";

        return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$s_data.'_'.$e_data.'_daily.csv');
    }
    /**
     * デイリーレポートのサイト別一覧のCSV出力用の関数
     *
     * @param [type] $id
     * @param [type] $searchdate_start
     * @param [type] $searchdate_end
     * @return void
     */
    public function downloadSiteDaily(  Request $request )
    {
                
        $id = ($request->p) ? $request->p : '' ;
        $asp_id = ($request->asp) ? $request->asp : '' ; 
        $start = ($request->s_date) ? urldecode($request->s_date) : '' ; 
        $end =  ($request->e_date) ?  urldecode($request->e_date) : '' ;

        $csvHeader= array();
        $csvHeader = ['日付','ASP', 'MediaID','サイト名', '案件名','案件ID','imp', 'CTR', 'click', 'CVR','CV','FDグロス','CPA'];

        $csvData = $this->dailySiteDataService->showCsv($asp_id , $id , $start , $end );

        $product = ProductBase::Where('id',$id)->get()->toArray();
        $s_data = str_replace('-', '', $start);
        $e_data = str_replace('-', '', $end);

        return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$s_data.'_'.$e_data.'_daily_site.csv');
    }
    /**
     * マンスリーレポートの一覧のCSV出力用の関数（実績値）
     *
     * @param [type] $id
     * @param [type] $month
     * @return void
     */
    public function downloadMonthly(Request $request )
    {

            $id = ($request->p) ? $request->p : '' ;                 
            $month = ($request->month) ? $request->month : '' ; 
            
            $product = ProductBase::Where('id',$id)->get()->toArray();

            $csvHeader =['ASP', '案件ID', '案件名', 'Imp', 'CTR', 'Click', 'CVR','CV', 'Active', 'Partnership','Price','CPA','承認件数','承認金額','承認率','前月CV（前月比）'];

            //当月の実績値 
            $csvData = $this->monthlyDataService->showCsv($id , $month);
            // var_dump($csvData);
            return CSV::download( $csvData , $csvHeader, $product[0]['product_name'].'_'.$month.'_daily.csv');
    }

    
    /**
     * マンスリーレポートの一覧のCSV出力用の関数（想定値）
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
        //  当月の着地想定トータル
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
     * マンスリーレポートのサイト別一覧のCSV出力用の関数
     *
     * @param [type] $id
     * @param [type] $month
     * @return void
     */
    public function downloadSiteMonthly(  Request $request )
    {
                
        $id = ($request->p) ? $request->p : '' ;    
        $asp = ($request->asp) ? $request->asp : '' ;              
        $date = ($request->month) ? urldecode($request->month) : '' ; 
        $month = date('Ym',strtotime($date));

        //$searchdate = urldecode($request->month)
        $product = ProductBase::Where('id', $id)->get()->toArray();

        $csvHeader= array();
        $csvHeader = ['日付','ASP', 'MediaID','サイト名', '案件名','案件ID','imp', 'CTR', 'click', 'CVR','CV','FDグロス','CPA'];


        $csvData = $this->monthlySiteDataService->showCsv($id , $date, $asp);
                    //var_dump($csvData);
                    //echo gettype($csvHeader);
        return CSV::download($csvData, $csvHeader, $product[0]['product_name'].'_'.$month.'_monthly_site.csv');
    }
}
