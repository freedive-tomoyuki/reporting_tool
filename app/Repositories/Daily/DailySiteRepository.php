<?php

namespace App\Repositories\Daily;

use App\DailySiteDiff;
use App\Repositories\Daily\DailySiteRepositoryInterface;
use DB;

class DailySiteRepository implements DailySiteRepositoryInterface
{
    protected $daily_site;

    /**
    * @param object $daily_site
    */
    public function __construct(DailySiteDiff $daily_site)
    {
        $this->daily_site = $daily_site;
    }

    /**
     *　月次サイト別データ取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $monthly 
     * @return object
     */
    public function getList($selected_asp, $id, $start, $end, $selected_site_name )
    {
            //開始月と終了月が同月の場合
            if (date('m', strtotime($start)) == date('m', strtotime($end))) {
                $month_1 = date('Ym', strtotime($start));
            //月を跨いでいる場合
            } else {
                $month_1 = date('Ym', strtotime($start));
                $month_2 = date('Ym', strtotime($end));
                        
                $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
                //終了月の検索　クエリビルダ
                $table2 = DB::table($daily_site_diffs_table2)
                            ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id',DB::raw($daily_site_diffs_table2.'.price'),'cpa','cost','estimate_cv'])
                            ->join('products', DB::raw($daily_site_diffs_table2.'.product_id'), '=', 'products.id')
                            ->join('asps', 'products.asp_id', '=', 'asps.id');
                    if (!empty($id)) {
                        $table2 = $table2->where('product_base_id', $id);
                    }
                    if (!empty($selected_asp)) {
                        $table2 = $table2->where('products.asp_id', $selected_asp);
                    }
                    if (!empty($start)) {
                        $table2 = $table2->where('date', '>=', $start);
                    }
                    if (!empty($end)) {
                        $table2 = $table2->where('date', '<=', $end);
                    }
            }
        
                $daily_site_diffs_table = $month_1.'_daily_site_diffs';
        
                //開始月の検索　クエリビルダ
                $daily_site = DB::table($daily_site_diffs_table)
                                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id',DB::raw($daily_site_diffs_table.'.price'),'cpa','cost','estimate_cv','products.asp_id'])
                                    ->join('products', DB::raw($daily_site_diffs_table.'.product_id'), '=', 'products.id')
                                    ->join('asps', 'products.asp_id', '=', 'asps.id');
        
                if ($id != '') {
                    $daily_site = $daily_site->where('product_base_id', $id);
                }
                if ($selected_asp != '') {
                    $daily_site = $daily_site->where('products.asp_id', $selected_asp);
                }
                if($selected_site_name != ''){
                    $daily_site = $daily_site->where('site_name', 'LIKE' , "%".$selected_site_name."%");
                }
                if ($start != '') {
                    $daily_site = $daily_site->where('date', '>=', $start);
                }
                if ($end != '') {
                    $daily_site = $daily_site->where('date', '<=', $end);
                }
                if (!empty($table2)) {
                    $daily_site = $daily_site->union($table2);
                }
            $daily_site = $daily_site->orderBy('date', 'asc');
            $daily_site = $daily_site->limit(2500);
            $daily_site = $daily_site->get();

            return $daily_site;
    }
    public function addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$media_id,$site_name)
    {
        $month = date('Ym',strtotime($date));
        $daily_site_diffs_table = $month.'_daily_site_diffs';

        return DB::table($daily_site_diffs_table)
        ->updateOrInsert(
            [
                'product_id' => $product_id ,
                'media_id' => $media_id ,
                'date' => $date 
            ],
            [
                'site_name' => $site_name,
                'imp' => $imp,
                'ctr' => $ctr,
                'click' => $click,
                'cvr' => $cvr,
                'cv' => $cv,
                'cost' => $cost,
                'price' => $price
            ]);
        
    }

    public function getRanking($selected_asp, $id, $start, $end )
    {
        $i = 0;
        $table2 = '';

        if(date('m',strtotime($start)) == date('m',strtotime($end))) {//同月の場合
            $month_1 = date('Ym',strtotime($start));
            
        }else{//月を跨いでいる場合
            $month_1 = date('Ym',strtotime($start));
            $month_2 = date('Ym',strtotime($end));
            $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
            $table2 = DB::table($daily_site_diffs_table2)
                    ->select(DB::raw("sum(cv) as total_cv, media_id, site_name"))
                    ->join('products',DB::raw($daily_site_diffs_table2.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $table2->where('product_base_id', $id);
                    }
                    if(!empty($selected_asp)){
                        $table2->where('products.asp_id', $selected_asp);
                    }
                    if(!empty($start)){
                        $table2->where('date', '>=' , $start );
                    }
                    if(!empty($end)){
                        $table2->where('date', '<=' , $end );
                    }
        }
        $daily_site_diffs_table = $month_1.'_daily_site_diffs';

        $daily_site = DB::table($daily_site_diffs_table)
            ->select(DB::raw("sum(cv) as total_cv, media_id, site_name"))
            ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
            ->join('asps','products.asp_id','=','asps.id')
            ->where('cv', '!=' , 0 );

            if(!empty($table2)){
                $daily_site->union($table2);
            }
            if(!empty($id)){
                $daily_site->where('product_base_id', $id);
            }
            if(!empty($selected_asp)){
                $daily_site->where('products.asp_id', $selected_asp);
            }
            if(!empty($start)){
                $daily_site->where('date', '>=' , $start );
            }
            if(!empty($end)){
                $daily_site->where('date', '<=' , $end );
            }
            $daily_site->groupBy("media_id");

            if(!empty($table2)){
                $daily_site->orderByRaw('total_cv DESC');
            }else{
                $daily_site->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');
            }

            $daily_site->limit(10);
            $daily_site = $daily_site->get()->toArray();
            return $daily_site;
    }
}
