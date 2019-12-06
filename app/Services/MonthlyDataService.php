<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
use App\Asp;
use App\Monthlydata;
use DB;

class MonthlyDataService
{
      public function calChart($product){

        $date = array();
        
        $aspinfo = Product::Select('asp_id','asps.name')->join('asps','products.asp_id','=','asps.id')->where('product_base_id',$product)->get()->toArray();
        //var_dump($aspinfo);
        for ($i = 1 ; $i <= 12 ; $i++ ) {
            array_push($date, date('Y-m-t',strtotime('-'.$i.' month')) ); 
        }
        //var_dump($date);

        $select = 'date ,';
        foreach( $aspinfo as $val){
            $select .= "sum(case when monthlydatas.asp_id='".$val['asp_id']."' then cv else 0 end) as '".$val['name']."'";
            if($val !== end($aspinfo)) {
                $select .= ', ';
            }else{
                $select .= ',SUM(cv) as "合計"';
                
            }
        }

        //var_dump($select);
        $yearly_chart =Monthlydata::select(DB::raw($select));
        $yearly_chart->join('products','monthlydatas.product_id','=','products.id');
        $yearly_chart->where('product_base_id',$product);
        $yearly_chart->whereIn('date',$date);
        $yearly_chart->groupBy('date');
        $sql = $yearly_chart->toSql();
        //var_dump($sql);
        $yearly_chart = $yearly_chart->get()->toArray();
        $i = 0;
        
        return json_encode($yearly_chart);
    }
    public function showList($id,$month): array
    {
        
        $ratio = (date("d")/date("t"));

        if( $month == date("Y-m")){
            $searchdate = date("Y-m-d", strtotime('-1 day'));
            $search_last_date = date("Y-m-t", strtotime('-1 month'));

        }else{
            $searchdate = date('Y-m-d', strtotime('last day of ' . $month));
            $before_month = date('Y-m',strtotime(date('Y-m-01', strtotime($month)).'-1 month'));
            //var_dump($before_month);
            $search_last_date = date('Y-m-t', strtotime('last day of ' . $before_month));
        }

        //当月の実績値
                    $products = Monthlydata::select([
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
                        $products->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $products->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }

                    $products = $products->get();//->toArray();


                    //当月の実績値トータル
                    $products_totals = Monthlydata::select(DB::raw(
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
                        $products_totals->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $products_totals->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $products_totals = $products_totals->get();

        //検索されたとき、且つ他の月が検索されたとき
        if( $month == date("Y-m", strtotime('-1 day'))){
    
        
                    //当月の着地想定
                    $products_estimates = Monthlydata::select(DB::raw("
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
                        $products_estimates->where('products.product_base_id', $id);
                    }
                    if(!empty($searchdate)){
                        $products_estimates->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                    }
                    $products_estimates=$products_estimates->get();
                    //->toArray();

                    //当月の着地想定トータル
                    $products_estimate_totals = DB::table(
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
                                    and monthlydatas.date LIKE '%".$searchdate."%') as estimate_table")
                          )
                        ->select(DB::raw("date, product_id,
                        sum(estimate_imp) as total_estimate_imp,
                        sum(estimate_click) as total_estimate_click,
                        sum(estimate_cv) as total_estimate_cv,
                        sum(estimate_cost) as total_estimate_cost"))->get();
                    $products_estimate_totals = json_decode(json_encode($products_estimate_totals), true);

                //var_dump($productsEstimateTotals->toArray());

        }else{
            $products_estimates = 'Empty';
            $products_estimate_totals = 'Empty';
        }


        //グラフ数値
                $chart_data = Monthlydata::select(['name', 'imp', 'click','cv'])
                ->join('products','monthlydatas.product_id','=','products.id')
                ->join('asps','products.asp_id','=','asps.id');

                if(!empty($id)){
                    $chart_data->where('products.product_base_id', $id);
                }
                if(!empty($searchdate)){
                    $chart_data->where('monthlydatas.date', 'LIKE' , "%".$searchdate."%");
                }

                $chart_data = $chart_data->get();
            
        return [ $products, $products_totals, $products_estimates, $products_estimate_totals, $chart_data];
    }

    public function showSiteList($id,$month,$asp_id): array
    {
        //当月の場合
        if( $month == date("Y-m", strtotime('-1 day'))|| $month == date("Y-m-d", strtotime('-1 day'))) {
            $searchdate = date("Y-m-d", strtotime('-1 day'));
        //当月以外の場合
        }else{
            $searchdate = date('Y-m-d', strtotime('last day of ' . $month));
        }
        $monthly_sites_table = date('Ym',strtotime($searchdate)).'_monthlysites';

        $products = DB::table($monthly_sites_table)
                    ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','products.product','products.id',DB::raw($monthly_sites_table.'.price'),'cpa','cost','estimate_cv','date','approval','approval_price','approval_rate'])
                    ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
                    ->join('asps','products.asp_id','=','asps.id');
                    
                    if(!empty($id)){
                        $products->where('products.product_base_id', $id);
                    }
                    if(!empty($asp_id)){
                        $products->where('products.asp_id', $asp_id);
                    }
                    if(!empty($searchdate)){
                        $products->where('date', 'LIKE' , "%".$searchdate."%");
                    }
                    //->where('product_base_id', $id)
                    //->where('monthlysites.created_at', 'LIKE' , "%".$searchdate."%")
                    $products = $products->get();
                    //->toArray();
                    //->toSql();
                    //var_dump($products);

        
        //echo $product_bases->isEmpty();
        
        //日次のグラフ用データの一覧を取得する。
        $site_ranking = $this->monthlyRankingSite($id,$searchdate,$asp_id);

        return [ $products , $site_ranking ];
    }
   /**
　    月別ランキング一覧取得
    */
    public function monthlyRankingSite($id ,$searchdate = null,$asp_id=null) {

        /*
            案件ｘ対象期間からCVがTOP10のサイトを抽出
        */
        $month = date('Ym',strtotime($searchdate));
        $monthly_sites_table = $month.'_monthlysites';

        $products = DB::table($monthly_sites_table)
        //$products = Monthlysite::
            ->select(DB::raw("cv , media_id, site_name"))
            
            ->join('products',DB::raw($monthly_sites_table.'.product_id'),'=','products.id')
            ->join('asps','products.asp_id','=','asps.id')
            
            ->where('cv', '!=' , 0 );

            if(!empty($id)){
                $products->where('product_base_id', $id);
            }
            if(!empty($asp_id)){
                $products->where('products.asp_id', $asp_id);
            }                    
            if(!empty($searchdate)){
                //今月の場合
                if(strpos($searchdate,date("Y-m", strtotime('-1 day'))) === false ){
                    $searchdate= date("Y-m-t",strtotime($searchdate));
                }
                $products->where('date' , $searchdate );
            }
            //echo $searchdate;
            $products->groupBy("media_id");
            $products->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');

            $products->limit(10);
            //echo $products->toSql();
            $products = $products->get();

            return json_encode($products);

}



}