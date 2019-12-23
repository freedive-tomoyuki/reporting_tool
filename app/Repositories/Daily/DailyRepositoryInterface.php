<?php
namespace App\Repositories\Daily;

interface DailyRepositoryInterface
{
    /**
     * 抽象クラス　getList　サイト別月次データ一覧取得
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $daily 
     * @return object
     */
    public function getList($selected_asp, $id, $start, $end);
    public function getTotal($selected_asp, $id, $start, $end);
    public function getChartDataTotalOfThreeItem($selected_asp, $id, $start, $end);
    public function getRankingEachOfAsp($selected_asp, $id, $start, $end );
    /**
     * 抽象クラス　addData　データ登録
     *
     * @param string $date
     * @param integer $product_id
     * @param integer $imp
     * @param integer $ctr
     * @param integer $click
     * @param integer $cvr
     * @param integer $cv
     * @param integer $cost
     * @param integer $price
     * @param integer $asp
     * @param integer $media_id
     * @param string $site_name
     * @return void
     */
    public function addData(  $date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name );
    /**
     * 抽象クラス　updateData　データ編集
     *
     * @param string $start
     * @param string $end
     * @param string $selected_asp
     * @param integer $id
     * @param object $all_post_data
     * @return void
     */
    public function updateData( $start , $end , $selected_asp , $id , $all_post_data, $products );

    /**
     * 日次データランキング用上位データ
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $daily 
     * @return json
     */
    public function getRanking( $selected_asp, $id, $start, $end );

}