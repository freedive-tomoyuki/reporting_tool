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
                ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id',DB::raw($monthly_sites_table.'.price'),'cpa','cost','estimate_cv','date','approval','approval_price','approval_rate','products.asp_id'])
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
            $monthly_site->groupBy("media_id");
            $monthly_site->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');

            $monthly_site->limit(10);
            $monthly_site = $monthly_site->get();

            return $monthly_site;

    }
}
