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
    /**
     * Undocumented function
     *
     * @param string $date
     * @param int $product_id
     * @param int $imp
     * @param int $ctr
     * @param int $click
     * @param int $cvr
     * @param int $cv
     * @param int $cost
     * @param int $price
     * @param int $asp
     * @param string $media_id
     * @param string $site_name
     * @return void
     */
    public function addData( $date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp,$media_id,$site_name );
    /**
     * Undocumented function
     *
     * @param string $selected_month
     * @param object $all_post_data
     * @return void
     */
    public function updateData($selected_month , $all_post_data);
    /**
     * サイト別月次データランキング用上位データ
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $daily 
     * @return json
     */
    public function getRanking( $selected_asp, $id, $start, $end );
    
    /**
     * 
     *  
    */ 
    public function getCsv($asp, $id, $start, $end);



}