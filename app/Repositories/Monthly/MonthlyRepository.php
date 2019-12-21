<?php

namespace App\Repositories\Monthly;

use App\MonthlyData;
use App\Product;
use App\Repositories\Monthly\MonthlyRepositoryInterface;
use DB;

class MonthlyRepository implements MonthlyRepositoryInterface
{
    protected $monthlyModel;
    protected $productModel;

    /**
    * @param MonthlyData $monthlyModel
    * @param Product $product_model
    */
    public function __construct(MonthlyData $monthlyModel ,Product $productModel)
    {
        $this->monthlyModel = $monthlyModel;
        $this->productModel = $productModel;
        
    }

    /**
     *　月次データ一覧取得 
     * @var string $id 
     * @var string $search_date 
     * @var string $search_last_date
     * @return object
     */
    public function getList($id, $search_date , $search_last_date )
    {
        $monthly_data = $this->monthlyModel->select([
            'name', 
            'imp', 
            'click',
            'cv', 
            'cvr', 
            'ctr', 
            'active', 
            'partnership',
            'monthlydatas.created_at',
            'products.product',
            'products.id',
            'monthlydatas.price',
            'cpa',
            'cost',
            'approval',
            'approval_price',
            'approval_rate',
            'last_cv'
        ])
        ->join('products','monthlydatas.product_id','=','products.id')
        ->join('asps','products.asp_id','=','asps.id')
        ->leftjoin(
        DB::raw("
                (
                    select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$search_last_date."'
                )
                AS last_month"
            ),
            'monthlydatas.product_id','=','last_month.product_id'
        );

        if(!empty($id)){
        $monthly_data->where('products.product_base_id', $id);
        }
        if(!empty($search_date)){
        $monthly_data->where('monthlydatas.date', 'LIKE' , "%".$search_date."%");
        }

        $monthly_data = $monthly_data->get();//->toArray();

        return $monthly_data;
    }
    /**
     *　月次データ各項目の合計取得
     * @param Integer $id
     * @param String $search_date
     * @return object
     */
    public function getTotal($id,$search_date ,$search_last_date)
    {
        $total = $this->monthlyModel->select(DB::raw(
            "date, 
            sum(imp) as total_imp,
            sum(click) as total_click,
            sum(cv) as total_cv,
            sum(active) as total_active,
            sum(partnership) as total_partnership ,
            sum(monthlydatas.price) as total_price ,
            sum(cost) as total_cost,
            (sum(monthlydatas.price)/sum(cv)) as total_cpa ,
            sum(approval) as total_approval, 
            sum(approval_price) as total_approval_price, 
            sum(last_cv) as total_last_cv , 
            (sum(approval)/sum(last_cv)*100) as total_approval_rate"
        ))
        ->join('products','monthlydatas.product_id','=','products.id')
        ->join('asps','products.asp_id','=','asps.id')
        ->leftjoin(DB::raw("(select `cv` as last_cv, `product_id` from `monthlydatas` inner join `products` on `monthlydatas`.`product_id` = `products`.`id` where `product_base_id` = ".$id." and `monthlydatas`.`date` like '".$search_last_date."') AS last_month"), 'monthlydatas.product_id','=','last_month.product_id');

        if(!empty($id)){
            $total->where('products.product_base_id', $id);
        }
        if(!empty($search_date)){
            $total->where('monthlydatas.date', 'LIKE' , "%".$search_date."%");
        }
        $total = $total->get();
        return $total;
    }
    /**
     * 月末の着地予測取得
     *
     * @param Integer $id
     * @param String $search_date
     * @param String $ratio
     * @return void
     */
    public function getEstimate($id,$search_date ,$ratio )
    {
        $estimates = $this->monthlyModel->select(DB::raw("
                    asps.name,
                    (imp/". $ratio .") as estimate_imp,
                    (click/". $ratio .") as estimate_click,
                    (cv/". $ratio .") as estimate_cv,
                    ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                    ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr, 
                    (cost/". $ratio .") as estimate_cost,
                    products.product,
                    products.id"))
            ->join('products','monthlydatas.product_id','=','products.id')
            ->join('asps','products.asp_id','=','asps.id');
            if(!empty($id)){
            $estimates->where('products.product_base_id', $id);
            }
            if(!empty($search_date)){
            $estimates->where('monthlydatas.date', 'LIKE' , "%".$search_date."%");
            }
            $estimates=$estimates->get();
        return $estimates;
    }
    public function getEstimateTotal($id,$search_date ,$ratio )
    {
        $estimate_totals = DB::table(
                    DB::raw("
                        (select (imp/". $ratio .") as estimate_imp,
                        (click/". $ratio .") as estimate_click,
                        (cv/". $ratio .") as estimate_cv,
                        ((cv/". $ratio .")/(click/". $ratio .")*100) as estimate_cvr, 
                        ((click/". $ratio .")/(imp/". $ratio .")*100) as estimate_ctr,
                        (cost/". $ratio .") as estimate_cost,
                        products.product,
                        products.id as product_id ,date from monthlydatas
                        inner join products on monthlydatas.product_id = products.id
                        where products.product_base_id = ".$id."
                        and monthlydatas.date LIKE '%".$search_date."%') as estimate_table")
            )
            ->select(DB::raw("date, product_id,
            sum(estimate_imp) as total_estimate_imp,
            sum(estimate_click) as total_estimate_click,
            sum(estimate_cv) as total_estimate_cv,
            sum(estimate_cost) as total_estimate_cost"))->get();
        $estimate_totals = json_decode(json_encode($estimate_totals), true);
        return $estimate_totals;
    }
    public function getChart($id,$search_date )
    {
        $chart_data = $this->monthlyModel->select(['name', 'imp', 'click','cv'])
                        ->join('products','monthlydatas.product_id','=','products.id')
                        ->join('asps','products.asp_id','=','asps.id');

                    if(!empty($id)){
                        $chart_data->where('products.product_base_id', $id);
                    }
                    if(!empty($search_date)){
                        $chart_data->where('monthlydatas.date', 'LIKE' , "%".$search_date."%");
                    }

        $chart_data = $chart_data->get();
        return $chart_data;
    }
}
