<?php
namespace App\Repositories\Monthly;

interface MonthlyRepositoryInterface
{
    /**
     * 月次データ一覧取得
     * @param integer $id
     * @param string $search_date
     * @return object
     */
    public function getList($id, $search_date, $search_last_date );
    /**
     * 月次各合計データ一覧取得
     *
     * @param integer $id
     * @param string $search_date
     * @return void
     */
    public function getTotal($id, $search_date, $search_last_date);
    /**
     * 今月の予想着地データ
     *
     * @param integer $id
     * @param string $search_date
     * @param string $ratio
     * @return void
     */
    public function getEstimate($id,$search_date ,$ratio);
    /**
     * 今月の着地合計値データ
     *
     * @param integer $id
     * @param string $search_date
     * @param string $ratio
     * @return void
     */
    public function getEstimateTotal($id,$search_date ,$ratio);
    /**
     *  チャートデータ取得
     *
     * @param Integer $id
     * @param String $search_date
     * @return void
     */
    public function getChart($id,$search_date );


}