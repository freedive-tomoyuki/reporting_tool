<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
use App\Asp;
use App\Monthlydata;
use App\Repositories\Monthly\MonthlyRepositoryInterface;
use DB;

class MonthlyDataService
{
    private $monthly_repo;
  
    public function __construct(MonthlyRepositoryInterface $monthly_repo)
    {
      $this->monthly_repo = $monthly_repo;
    }

    public function showList($id,$month): array
    {
        //消化率
        $ratio = (date("d")/date("t"));
        //今月
        if( $month == date("Y-m")){
            //昨日時点の月次データ取得のため
            $search_date = date("Y-m-d", strtotime('-1 day'));
            //昨月の最終日データ取得のため
            $search_last_date = date("Y-m-t", strtotime('-1 month'));
        //過去の月
        }else{
            //昨日時点の月次データ取得のため
            $search_date = date('Y-m-d', strtotime('last day of ' . $month));
            
            $before_month = date('Y-m',strtotime(date('Y-m-01', strtotime($month)).'-1 month'));
            //昨月の最終日データ取得のため
            $search_last_date = date('Y-m-t', strtotime('last day of ' . $before_month));
        }

        //当月の実績値
        $products = $this->monthly_repo->getList($id, $search_date , $search_last_date );

        //当月の実績値トータル
        $products_totals = $this->monthly_repo->getTotal($id,$search_date ,$search_last_date);
        
        //今月の検索を行った際に、着地予想を表示
        if( $month == date("Y-m", strtotime('-1 day'))){
            echo date("Y-m", strtotime('-1 day'));
            //当月の着地想定
            $estimates = $this->monthly_repo->getEstimate($id,$search_date ,$ratio);
            //当月の着地想定トータル
            $estimate_totals = $this->monthly_repo->getEstimateTotal($id,$search_date ,$ratio);

        }else{
            $estimates = 'Empty';
            $estimate_totals = 'Empty';
        }
        //グラフ数値
        $chart_data = $this->monthly_repo->getChart($id,$search_date);
            
        return [ $products, $products_totals, $estimates, $estimate_totals, $chart_data];
    }
    public function addData(  $month , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partnership, $approval ,$approval_price ,$approval_rate)
    {

        $daily_data = $this->monthly_repo->addData($month , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partnership, $approval ,$approval_price ,$approval_rate);
        
        // 日次のグラフ用データの一覧を取得する。
        //$site_ranking = $this->dailyRankingSite($id, $searchdate_start, $searchdate_end, $asp_id);

        return $daily_data;
    }
    public function updateData( $id , $all_post_data )
    {
        $products = Product::select('id')->where('product_base_id', $id)->where('killed_flag', '==', 0)->get();

        $month = (!$all_post_data->month)? '' : $all_post_data->month;

        $selected_asp = (!$all_post_data->asp)? '' : $all_post_data->asp;

        // $this->daily_repo->updateData( $start , $end , $selected_asp , $id , $all_post_data, $products);
        return $this->monthly_repo->updateData( $month , $selected_asp , $all_post_data, $products);

    }
    public function showCsv($p , $month): array
    {
        if( $month == date("Y-m")){
            $date = date("Y-m-d", strtotime('-1 day'));
            $last_date = date("Y-m-t", strtotime('-1 month'));
        }else{
            $date = date('Y-m-d', strtotime('last day of ' . $month));
            // $last_month = date('Y-m',strtotime('-1 month'));
            $last_date = date('Y-m-t', strtotime('last day of ' . $month));
        }
    
        $csv_data = $this->monthly_repo->getCsv( $p , $date ,$last_date);

        // $total = $this->daily_repo->getTotal($id, $start, $end, $asp_id );

        return $csv_data ;
    }



}