<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
use App\Asp;
use App\Monthlydata;
use DB;
use App\Repositories\Monthly\MonthlySiteRepositoryInterface;

class MonthlySiteDataService
{
    private $monthly_site_repo;
  
    public function __construct(MonthlySiteRepositoryInterface $monthly_site_repo)
    {
      $this->monthly_site_repo = $monthly_site_repo;
    }
    
    /**
     * 月次サイト一覧取得
     *
     * @param integer $selected_asp
     * @param integer $id
     * @param string $selected_month
     * @param string $selected_site_name
     * @return void
     */
    public function showSiteList($selected_asp, $id, $selected_month, $selected_site_name = null)
    {
        //当月の場合
        if( $selected_month == date("Y-m", strtotime('-1 day'))|| $selected_month == date("Y-m-d", strtotime('-1 day'))) {
            $selected_date = date("Y-m-d", strtotime('-1 day'));
        //当月以外の場合
        }else{
            $selected_date = date('Y-m-d', strtotime('last day of ' . $selected_month));
        }
        //var_dump($array_product_id);
        $monthly_sites_data = $this->monthly_site_repo->getList($selected_asp, $id, $selected_date, $selected_site_name);

        //日次のグラフ用データの一覧を取得する。
        //$site_ranking = $this->monthlyRankingSite($id,$selected_date,$selected_asp);

        return $monthly_sites_data;//[ $products , $site_ranking ];
    }
    /**
     * 月別ランキング一覧取得
     *
     * @param integer $selected_asp
     * @param integer $id
     * @param string $selected_month
     * @return void
     */
     public function monthlyRankingSite( $selected_asp=null , $id , $selected_month = null) {
        //当月の場合
        if( $selected_month == date("Y-m", strtotime('-1 day'))|| $selected_month == date("Y-m-d", strtotime('-1 day'))) {
            $selected_date = date("Y-m-d", strtotime('-1 day'));
        //当月以外の場合
        }else{
            $selected_date = date('Y-m-d', strtotime('last day of ' . $selected_month));
        }

        $monthly_sites_data = $this->monthly_site_repo->getRanking($selected_asp, $id, $selected_date);

        return json_encode($monthly_sites_data);

    }

    public function addSiteData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$media_id,$site_name ,$approval,$approval_price ,$approval_rate) {
        //echo $date;
        $this->monthly_site_repo->addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$media_id,$site_name ,$approval,$approval_price ,$approval_rate);

        return false;

    }
    public function updateSiteData($id , $all_post_data ) {
 
        // $products = Product::select('id')
        //                 ->where('product_base_id', $id)
        //                 ->where('killed_flag', '==', 0)
        //                 ->get();

        $selected_month = (!$all_post_data->month)? '' : $all_post_data->month;
        // $selected_asp   = (!$all_post_data->asp)? '' : $all_post_data->asp;

        
        $this->monthly_site_repo->updateData( $selected_month , $all_post_data);

        return false;
    }
    /**
     * Undocumented function
     *
     * @param [type] $selected_asp
     * @param [type] $id
     * @param [type] $start
     * @param [type] $end
     * @return array
     */
    public function showCsv($id , $month, $asp ): array
    {
        //当月の場合
        if( $month == date("Y-m", strtotime('-1 day'))|| $month == date("Y-m-d", strtotime('-1 day'))) {
            $date = date("Y-m-d", strtotime('-1 day'));
        //当月以外の場合
        }else{
            $date = date('Y-m-d', strtotime('last day of ' . $month));
        }
        //日次データ一覧取得
        $csv_data = $this->monthly_site_repo->getCsv( $id , $date , $asp );

        return $csv_data ;
    }

}