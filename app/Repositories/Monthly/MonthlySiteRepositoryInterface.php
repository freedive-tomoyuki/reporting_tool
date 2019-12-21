<?php
namespace App\Repositories\Monthly;

interface MonthlySiteRepositoryInterface
{
    /**
     * サイト別月次データ一覧取得
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $monthly 
     * @return object
     */
    public function getList( $selected_asp, $id, $monthly, $selected_site_name);
    /**
     * サイト別月次データランキング用上位データ
     * @var string $selected_asp 
     * @var string $product_id 
     * @var string $monthly 
     * @return json
     */
    public function getRanking( $selected_asp, $id, $monthly);

}