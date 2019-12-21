<?php
namespace App\Repositories\Daily;

interface DailySiteRepositoryInterface
{
    /**
     * サイト別月次データ一覧取得
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $daily 
     * @return object
     */
    public function getList($selected_asp, $id, $start, $end, $selected_site_name);

    public function addData( $date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name );

    /**
     * サイト別月次データランキング用上位データ
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $daily 
     * @return json
     */
    public function getRanking( $selected_asp, $id, $start, $end );

}