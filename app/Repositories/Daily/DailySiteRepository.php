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
                                    ->select([DB::raw($daily_site_diffs_table.'.id as mid'),'name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id',DB::raw($daily_site_diffs_table.'.price'),'cpa','cost','estimate_cv','products.asp_id'])
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
    /**
     *　日次CSVサイトデータ一覧取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $monthly 
     * @return object
     */
    public function getCsv($asp, $id, $start, $end)
    {
         
        //開始月と終了月が同月の場合
        if(date('m',strtotime($start)) == date('m',strtotime($end))) {
            $month_1 = date('Ym',strtotime($start));
        //月を跨いでいる場合
        }else{
            
            $month_1 = date('Ym',strtotime($start));
            $month_2 = date('Ym',strtotime($end));
            
            $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
            //終了月の検索　クエリビルダ
            $table2 = DB::table($daily_site_diffs_table2)
                    ->select(['date','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','cost','cpa'])
                    ->join('products',DB::raw($daily_site_diffs_table2.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $table2->where('product_base_id', $id);
                    }
                    
                    if(!empty($start)){
                        $table2->where('date', '>=' , $start );
                    }
                    if(!empty($end)){
                        $table2->where('date', '<=' , $end );
                    }
        }
        

        $daily_site_diffs_table = $month_1.'_daily_site_diffs';

        //開始月の検索　クエリビルダ
        $csv_data = DB::table($daily_site_diffs_table)
                    ->select(['date','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','cost','cpa'])
                    
                    ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');

                    if(!empty($id)){
                        $csv_data->where('product_base_id', $id);
                        
                    }
                    if(!empty($start)){
                        $csv_data->where('date', '>=' , $start );
                    }
                    if(!empty($asp)){
                        $csv_data->where('products.asp_id', $asp);
                    }
                    if(!empty($end)){
                        $csv_data->where('date', '<=' , $end );
                    }
                    if(!empty($table2)){
                        $csv_data->union($table2);
                    }
                    $csv_data = $csv_data->get()->toArray();
                    $csv_data = json_decode(json_encode($csv_data), true);

        return $csv_data;
    }

    public function updateData($selected_month , $all_post_data)
    {
        $month = date('Ym',strtotime($selected_month));
        $daily_site_diffs_table = $month.'_daily_site_diffs';

        // DB::table($monthly_site_table)

        $daily = DB::table($daily_site_diffs_table)
                    ->whereIn("id", $all_post_data->media_array)->get();
 
        foreach ($daily as $p) {
            $key = hash('md5', $p->id);
            $killed_flag = ($all_post_data->delete[$key] == 'on')? 1 : 0 ;

            $daily = DB::table($daily_site_diffs_table)
            ->updateOrInsert(
                [
                    'id' => $p->id ,
                    'date' => $all_post_data->date[$key] ,
                ],
                [
                    'media_id' => $all_post_data->media_id[$key] ,
                    'site_name' => $all_post_data->site_name[$key],
                    'imp' => $all_post_data->imp[$key],
                    'ctr' => $all_post_data->ctr[$key],
                    'click' => $all_post_data->click[$key],
                    'cvr' => $all_post_data->cvr[$key],
                    'cv' => $all_post_data->cv[$key],
                    'cost' => $all_post_data->cost[$key],
                    'price' => $all_post_data->price[$key],
                    'cost' => $all_post_data->cost[$key],
                    'price' => $all_post_data->price[$key],
                    'killed_flag' => $killed_flag ,
                    
                ]);

        }
        return false; 
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
