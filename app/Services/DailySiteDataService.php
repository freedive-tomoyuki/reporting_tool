<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
use App\Asp;
use App\DailyDiff;
use DB;
use App\Repositories\Daily\DailySiteRepositoryInterface;

class DailySiteDataService
{
            private $daily_site_repo;
            /**
             * Undocumented function
             *
             * @param DailySiteRepositoryInterface $daily_site_repo
             */
            public function __construct(DailySiteRepositoryInterface $daily_site_repo)
            {
            $this->daily_site_repo = $daily_site_repo;
            }
            /**
             * showSiteList function
             *
             * @param [type] $selected_asp
             * @param [type] $id
             * @param [type] $start
             * @param [type] $end
             * @param [type] $selected_site_name
             * @return void
             */
            public function showSiteList( $selected_asp, $id, $start, $end, $selected_site_name)
            {

                $daily_sites_data = $this->daily_site_repo->getList($selected_asp, $id, $start, $end, $selected_site_name);
                
                // 日次のグラフ用データの一覧を取得する。
                //$site_ranking = $this->dailyRankingSite($id, $searchdate_start, $searchdate_end, $asp_id);

                return $daily_sites_data;
            }
            /**
             *   サイト別トップ１０のサイト一覧取得関数
             */
            public function dailyRankingSite($selected_asp=null,$id = 3 ,$searchdate_start = null,$searchdate_end = null) {
                        
                        // 案件ｘ対象期間からCVがTOP10のサイトを抽出
                        // 「StartとEndが同じ日」もしくは、「どちらも入力されていない」の場合・・・①    
                        //echo "date_start".date('m', strtotime($searchdate_start));
                        $date_start = date('Y-m-d', strtotime($searchdate_start));
                        //echo "date_end".date('m', strtotime($searchdate_end));
                        $date_end = date('Y-m-d', strtotime($searchdate_end));

                        $daily_sites_data = $this->daily_site_repo->getRanking($selected_asp, $id, $date_start, $date_end );
                            
                            //var_dump($products_1);
                        return json_encode($daily_sites_data);
            }
            /**
             * 日次データ登録
             *
             * @param [type] $date
             * @param [type] $product_id
             * @param [type] $imp
             * @param [type] $ctr
             * @param [type] $click
             * @param [type] $cvr
             * @param [type] $cv
             * @param [type] $cost
             * @param [type] $price
             * @param [type] $asp
             * @param [type] $media_id
             * @param [type] $site_name
             * @return void
             */
            public function addSiteData( $date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name)
            {

                $daily_sites_data = $this->daily_site_repo->addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name);
                
                // 日次のグラフ用データの一覧を取得する。
                //$site_ranking = $this->dailyRankingSite($id, $searchdate_start, $searchdate_end, $asp_id);

                return $daily_sites_data;
            }

            public function updateSiteData($id , $all_post_data ) {
 
                // $products = Product::select('id')
                //                 ->where('product_base_id', $id)
                //                 ->where('killed_flag', '==', 0)
                //                 ->get();
        
                $selected_month = (!$all_post_data->month)? '' : $all_post_data->month;
                // $selected_asp   = (!$all_post_data->asp)? '' : $all_post_data->asp;
        
                
                $this->daily_site_repo->updateData( $selected_month , $all_post_data);
        
                return false;
            }
}