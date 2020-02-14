<?php
namespace App\Repositories\Monthly;

interface MonthlySiteRepositoryInterface
{
    /**
     * サイト別月次データ一覧取得
     * @var string $selected_asp 
     * @var int $product_id 
     * @var string $monthly 
     * @return object
     */
    public function getList( $selected_asp, $id, $monthly, $selected_site_name);
    /**
     * サイト別月次データランキング用上位データ
     * @var string $selected_asp 
     * @var int $product_id 
     * @var string $monthly 
     * @return json
     */
    public function getRanking( $selected_asp, $id, $selected_date);
    /**
     * サイト別月次データ登録
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
     * @param int $approval
     * @param int $approval_price
     * @param int $approval_rate
     * @return void
     */
    public function addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$media_id,$site_name ,$approval,$approval_price ,$approval_rate);
    /**
     * サイト別月次データ編集
     *
     * @param string $selected_month
     * @param object $all_post_data
     * @return void
     */
    public function updateData($selected_month , $all_post_data);
    
    /**
     * サイト別月次データCSV出力
     *
     * @param [type] $id
     * @param [type] $date
     * @param [type] $asp
     * @return void
     */
    public function getCsv($id , $date , $asp);


}