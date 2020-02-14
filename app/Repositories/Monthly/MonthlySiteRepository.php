<?php

namespace App\Repositories\Monthly;

use App\Monthlysite;
use App\Repositories\Monthly\MonthlySiteRepositoryInterface;
use DB;

class MonthlySiteRepository implements MonthlySiteRepositoryInterface
{
    protected $monthly_site;

    /**
    * @param object $monthly_site
    */
    public function __construct(Monthlysite $monthly_site)
    {
        $this->monthly_site = $monthly_site;
    }

    /**
     *　月次サイト別データ取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $selected_date 
     * @var string selected_site_name
     * @return object
     */
    public function getList($selected_asp, $id, $selected_date ,$selected_site_name )
    {
        $monthly_sites_table = date('Ym',strtotime($selected_date)).'_monthlysites';

        $this->monthly_site = DB::table($monthly_sites_table)
                ->select([DB::raw($monthly_sites_table.'.id as mid'),'name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id',DB::raw($monthly_sites_table.'.price'),'cpa','cost','estimate_cv','date','approval','approval_price','approval_rate','products.asp_id'])
                ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
                ->join('asps','products.asp_id','=','asps.id');

        if($id != ''){
            $this->monthly_site->where('products.product_base_id', $id);
        }
        if($selected_site_name != ''){
            $this->monthly_site->where('site_name', 'LIKE' , "%".$selected_site_name."%");
        }
        if($selected_asp != ''){
            $this->monthly_site->where('products.asp_id', $selected_asp);
        }
        if($selected_date != ''){
            $this->monthly_site->where('date', 'LIKE' , "%".$selected_date."%");
        }
        $monthly_site = $this->monthly_site->get();//->toArray();
        return $monthly_site;   
    }
    /**
     * Undocumented function
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
    public function addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$media_id,$site_name ,$approval,$approval_price ,$approval_rate)
    {
        $month = date('Ym',strtotime($date));
        $monthly_site_table = $month.'_monthlysites';

        return DB::table($monthly_site_table)
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
                'price' => $price,
                'cost' => $cost,
                'price' => $price,
                'approval' => $approval,
                'approval_price' => $approval_price,
                'approval_rate' => $approval_rate
            ]);
        
    }
    public function updateData($selected_month , $all_post_data)
    {
        $month = date('Ym',strtotime($selected_month));
        $monthly_site_table = $month.'_monthlysites';

        // DB::table($monthly_site_table)

        $monthly = DB::table($monthly_site_table)
                    ->whereIn("id", $all_post_data->media_array)->get();
 
        foreach ($monthly as $p) {
            $key = hash('md5', $p->id);
            $killed_flag = ($all_post_data->delete[$key] == 'on')? 1 : 0 ;

            $monthly = DB::table($monthly_site_table)
            ->updateOrInsert(
                [
                    'id' => $p->id ,
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
                    'approval' => $all_post_data->approval[$key],
                    'approval_price' => $all_post_data->approval_price[$key],
                    'approval_rate' => $all_post_data->approval_rate[$key],
                    'killed_flag' => $killed_flag ,
                    
                ]);

        }
        return false; 
    }
    

    /**
     * 月別ランキング一覧取得
     *
     * @param integer $selected_asp
     * @param integer $id
     * @param string $selected_date
     * @return void
     */
    public function getRanking($selected_asp, $id, $selected_date )
    {
        // 案件ｘ対象期間からCVがTOP10のサイトを抽出
        $month = date('Ym',strtotime($selected_date));
        $monthly_sites_table = $month.'_monthlysites';
        $monthly_site = DB::table($monthly_sites_table)
            ->select(DB::raw("cv , media_id, site_name"))
            ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
            ->join('asps','products.asp_id','=','asps.id')
            ->where('cv', '!=' , 0 );

            if($id != ''){
                $monthly_site->where('product_base_id', $id);
            }
            if($selected_asp != ''){
                $monthly_site->where('products.asp_id', $selected_asp);
            }                    
            if($selected_date != ''){
                //今月の場合
                if(strpos($selected_date,date("Y-m", strtotime('-1 day'))) === false ){
                    $selected_date= date("Y-m-t",strtotime($selected_date));
                }
                $monthly_site->where('date' , $selected_date );
            }
            //echo $searchdate;
            $monthly_site->groupBy("cv");
            $monthly_site->groupBy("media_id");
            $monthly_site->groupBy("site_name");
            $monthly_site->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');

            $monthly_site->limit(10);
            $monthly_site = $monthly_site->get();

            return $monthly_site;

    }

    public function getCsv($id , $date , $asp)
    {
        $month = date('Ym',strtotime($date));
        $monthlysites_table = $month.'_monthlysites';

        //開始月の検索　クエリビルダ
        $csv_data = DB::table($monthlysites_table)
                    ->select(['date','name', 'media_id','site_name', 'products.product','products.id','imp', 'ctr', 'click', 'cvr','cv','cost','cpa'])
                    ->join('products', DB::raw($monthlysites_table.'.product_id'), '=', 'products.id')
                    ->join('asps', 'products.asp_id', '=', 'asps.id');

        if (!empty($id)) {
            $csv_data->where('product_base_id', $id);
        }
        if(!empty($asp)){
            $csv_data->where('products.asp_id', $asp);
        }
        if (!empty($date)) {
            $csv_data->where('date', '=', $date);
        }

        $csv_data = $csv_data->get()->toArray();
        $csv_data = json_decode(json_encode($csv_data), true);
        return $csv_data;
    }
}
