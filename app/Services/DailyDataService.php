<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
//use App\Asp;
// use App\Dailydata;
//use App\DailyDiff;
use DB;
use App\Repositories\Daily\DailyRepositoryInterface;

class DailyDataService
{
            private $daily_repo;
        
            public function __construct(DailyRepositoryInterface $daily_repo)
            {
            $this->daily_repo = $daily_repo;
            }

            /**
             * 日次一覧画面データ取得
             *
             * @param [type] $id
             * @param [type] $start
             * @param [type] $end
             * @param [type] $asp_id
             * @return array
             */
            public function showList($id, $start, $end, $asp_id ): array
            {
                //日次データ一覧取得
                $daily_data = $this->daily_repo->getList($id, $start, $end, $asp_id );

                $total = $this->daily_repo->getTotal($id, $start, $end, $asp_id );

                $total_chart = $this->daily_repo->getChartDataTotalOfThreeItem($id, $start, $end, $asp_id );
                
                $data = array();
                $i = 0;
                
                foreach ($total_chart as $chart) {
                    $data[$i]['date'] = $chart['date'];
                    $data[$i]['total_imp'] = intval($chart['total_imp']);
                    $data[$i]['total_click'] = intval($chart['total_click']);
                    $data[$i]['total_cv'] = intval($chart['total_cv']);
                    $data[$i] = array_values($data[$i]);
                    $i++;
                }

                $total_chart = json_encode($data);
                //日次のグラフ用データの一覧を取得する。
                $daily_ranking = $this->daily_repo->getRankingEachOfAsp($id, $start, $end, $asp_id );
                //$this->dailyRankingAsp($id,$searchdate_start,$searchdate_end,$asp_id);
                //var_dump($daily_ranking);
                return [$daily_data  ,$daily_ranking , $total , $total_chart ];
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
            public function addData(  $date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partner)
            {

                $daily_data = $this->daily_repo->addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partner);
                
                // 日次のグラフ用データの一覧を取得する。
                //$site_ranking = $this->dailyRankingSite($id, $searchdate_start, $searchdate_end, $asp_id);

                return $daily_data;
            }
            public function updateData( $id , $all_post_data )
            {
                $products = Product::select('id')->where('product_base_id', $id)->where('killed_flag', '==', 0)->get();

                $start = (!$all_post_data->month)? date('Y-m-01') : date('Y-m-d', strtotime('first day of ' . $all_post_data->month)) ;
        
                $end = (!$all_post_data->month)? date('Y-m-d' ,strtotime('-1 day')) : date('Y-m-d', strtotime('last day of ' . $all_post_data->month));

                $selected_asp = (!$all_post_data->asp)? '' : $all_post_data->asp;

                $this->daily_repo->updateData( $start , $end , $selected_asp , $id , $all_post_data, $products);

                return false;

            }
            public function showCsv($selected_asp , $id, $start, $end ): array
            {
                //日次データ一覧取得
                // $daily_data = $this->daily_repo->getList($id, $start, $end, $asp_id );
                $csv_data = $this->daily_repo->getCsv($selected_asp , $id, $start, $end);

                // $total = $this->daily_repo->getTotal($id, $start, $end, $asp_id );
        
                return $csv_data ;
            }

           
}