<?php
namespace App\Repositories\Daily;

interface DailyRepositoryInterface
{
    /**
     * サイト別月次データ一覧取得
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
    public function addData(  $date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name );

    /**
     * サイト別月次データランキング用上位データ
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $daily 
     * @return json
     */
    public function getRanking( $selected_asp, $id, $start, $end );

}