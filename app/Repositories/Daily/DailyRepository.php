<?php

namespace App\Repositories\Daily;

use App\DailyDiff;
use App\Product;
use App\Repositories\Daily\DailyRepositoryInterface;
use DB;

class DailyRepository implements DailyRepositoryInterface
{
    protected $dailyModel;
    protected $productModel;

    /**
    * @param DailyDiff $daily_model
    * @param Product $product_model
    */
    public function __construct(DailyDiff $dailyModel ,Product $productModel)
    {
        $this->dailyModel = $dailyModel;
        $this->productModel = $productModel;
        
    }

    /**
     *　日次データ一覧取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $monthly 
     * @return object
     */
    public function getList($selected_asp, $id, $start, $end　)
    {
        $daily_data = $this->dailyModel->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','date','daily_diffs.created_at','products.product','products.id','daily_diffs.price','cpa','cost','estimate_cv'])
        ->join('products','daily_diffs.product_id','=','products.id')
        ->join('asps','products.asp_id','=','asps.id');
        if(!empty($id)){
            $daily_data->where('product_base_id', $id);
        }
        if(!empty($selected_asp)){
            $daily_data->where('products.asp_id', $selected_asp);
        }
        if(!empty($start)){
            $daily_data->where('daily_diffs.date', '>=' , $start);
        }
        if(!empty($end)){
            $daily_data->where('daily_diffs.date', '<=' , $end );
        }
        $daily_data = $daily_data->get();

        return $daily_data;
    }
    /**
     *　日次CSVデータ一覧取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $monthly 
     * @return object
     */
    public function getCsv($selected_asp, $id, $start, $end)
    {
        
        $csv_data = $this->dailyModel->select(['daily_diffs.date','name','products.id','products.product', 'imp', 'ctr', 'click', 'cvr','cv', 'active', 'partnership','daily_diffs.price','cpa'])
                    ->join('products','daily_diffs.product_id','=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    if(!empty($id)){
                        $csv_data->where('product_base_id', $id);
                    }
                    if(!empty($selected_asp)){
                        $csv_data->where('products.asp_id', $selected_asp);
                    }
                    if(!empty($start)){
                        $csv_data->where('daily_diffs.date', '>=' , $start);
                    }
                    if(!empty($end)){
                        $csv_data->where('daily_diffs.date', '<=' , $end);
                    }
                    
                    $csvData =$csv_data->get()->toArray();

        return $csvData;
    }

    /**
     *　日次データ各項目の合計取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $monthly 
     * @return object
     */
    public function getTotal($selected_asp, $id, $start, $end　)
    {
        $total = $this->dailyModel->select(DB::raw("date,products.id, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(estimate_cv) as total_estimate_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(daily_diffs.price) as total_price "))
        ->join('products','daily_diffs.product_id','=','products.id')
        ->join('asps','products.asp_id','=','asps.id');

        if(!empty($id)){
            $total->where('product_base_id', $id);
        }
        if(!empty($selected_asp)){
            $total->where('products.asp_id', $selected_asp);
        }
        if(!empty($start)){
            $total->where('daily_diffs.date', '>=' , $start);
        }
        if(!empty($end　)){
            $total->where('daily_diffs.date', '<=' , $end　 );
        }
        $total = $total->get();
        return $total;
    }
    /**
     *　日次各項目の合計取得
     * @var string $selected_asp 
     * @var string $id 
     * @var string $monthly 
     * @return object
     */
    public function getChartDataTotalOfThreeItem($selected_asp, $id, $start, $end　)
    {
        $total_chart = $this->dailyModel->select(DB::raw("date, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv"))
        ->join('products','daily_diffs.product_id','=','products.id')
        ->join('asps','products.asp_id','=','asps.id');
        if(!empty($id)){
            $total_chart->where('product_base_id', $id);
        }
        if(!empty($selected_asp)){
            $total_chart->where('products.asp_id', $selected_asp);
        }
        if(!empty($start)){
            $total_chart->where('daily_diffs.date', '>=' , $start);
        }
        if(!empty($end)){
            $total_chart->where('daily_diffs.date', '<=' , $end );
        }
        $total_chart = $total_chart->groupby('date')->get()->toArray();
        return $total_chart;
    }
    public function getRankingEachOfAsp($selected_asp, $id, $start, $end)
    {
        //$product = new Product;
        // 案件ｘ対象期間から対象案件のCV件数
        $sql = 'Select DATE_FORMAT(date,"%Y/%m/%d") as date';
        $sql_select_asp = "";
        $asp_data = $this->productModel->filterAsp($id);
        // var_dump($asp_data);
        $asp_array = (json_decode($asp_data,true));
        //echo gettype($asp_id);
        foreach ($asp_array as $asp){
                $sql_select_asp.=",max( case when asp_id=".$asp['asp_id']." then cv end ) as ".str_replace(' ', '' ,$asp["name"]);
        }
        $sql = $sql.$sql_select_asp;
        $sql .= ' From daily_diffs ';
        $where = '';
        if($id != '' ){
            $where = " where ";
            // echo $id;
            $product_list = $this->productModel->convertProduct($id);
            // var_dump($product_list);

            $where .= " product_id in (";
            foreach ($product_list as $product) {
                $where .= $product['id'];

                if($product !== end($product_list)){
                    $where .= ",";
                }
            }
            $where .= " )";
            
        }
        if($start != '' ){
            if($where !== ''){
                $where .= " and ";
            }else{
                $where = " where ";
            }
            $where .= " date >= '". $start ."'";
        }
        if($end != '' ){
            if($where !== ''){
                $where .= " and ";
            }else{
                $where = " where ";
            }
            $where .= " date <= '". $end ."'";
        }
        if($selected_asp != '' ){
            if($where !== ''){
                $where .= " and ";
            }else{
                $where = " where ";
            }
            $where .= " asp_id = ". $selected_asp ;
        }
        //echo $where;
        if($where !== '') $sql.= $where ;
        $sql .=' Group By  DATE_FORMAT(date,"%Y/%m/%d")';
        
        $ranking_asp = DB::select($sql);
        // var_dump($ranking_asp);
        return json_encode($ranking_asp);
    }
    /**
     *  function addData
     *  データ登録用　Repository　
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
     * @param [type] $active
     * @param [type] $partner
     * @return void
     */
    public function addData($date , $product_id , $imp, $ctr, $click, $cvr, $cv ,$cost, $price ,$asp ,$active ,$partner)
    {
        $ctr = ($click / $imp ) * 100;
        $cvr = ($cv / $click ) * 100;
        
        return $this->dailyModel->updateOrCreate(
                ['date' => $date, 'product_id' => $product_id],
                [
                    'imp' => $imp,
                    'ctr' => $ctr,
                    'click' => $click,
                    'cvr' => $cvr,
                    'cv' => $cv,
                    'active' => $active,
                    'partnership' => $partner,
                    'cost' => $cost,
                    'price' => $price,
                    'asp_id' => $asp
                ]
            );
    }
    public function updateData($start , $end , $selected_asp , $id , $all_post_data, $products)
    {
       $update =  $this->dailyModel->whereIn("product_id", $products);
                if ($start) {
                    $update->where('date', '>=', $start);
                }
                if ($end) {
                    $update->where('date', '<=', $end);
                }
                if ($selected_asp) {
                    $update->where('asp_id', '=', $selected_asp);
                }
                
                $update = $update->get();
        //var_dump($all_post_data);      

        foreach ($update as $p) {

                $update_daily = $this->dailyModel->find($p->id) ;
                $key = hash('md5', $p->id);

                $ctr = ($all_post_data->click[$key] / $all_post_data->imp[$key] ) * 100;
                $cvr = ($all_post_data->cv[$key] / $all_post_data->click[$key] ) * 100;

                $update_daily->imp = $all_post_data->imp[$key];
                $update_daily->ctr = $ctr;
                $update_daily->click = $all_post_data->click[$key];
                $update_daily->cvr = $cvr;
                $update_daily->cv = $all_post_data->cv[$key];
                $update_daily->active = $all_post_data->active[$key];
                $update_daily->partnership = $all_post_data->partner[$key];
                $update_daily->cost = $all_post_data->cost[$key];
                $update_daily->price = $all_post_data->price[$key];

                if ($all_post_data->delete[$key] == 'on') {
                    $update_daily->killed_flag = 1;
                }
                
                $update_daily->save();
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
            //var_dump($daily_site);
            return json_encode($daily_site) ;
    }
}
