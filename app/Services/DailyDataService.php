<?php
/**
 * 年次データクラス
 */
namespace App\Services;

use App\Product;
use App\Asp;
// use App\Dailydata;
use App\DailyDiff;
use DB;

class DailyDataService
{
            /**
            * 可動しているASPを精査する関数
            */
            public function filterAsp( $product_id ){
                $target_asp = Product::select('asp_id','name')
                            ->join('asps','products.asp_id','=','asps.id')
                            ->where('product_base_id', $product_id )
                            ->where('products.killed_flag', 0 )
                            ->get();
        
                return json_encode($target_asp);
            }
            /**
             * 親案件から案件一覧を取得する。
            * @param number $baseproduct
            * @return array $converter 
            */
            public function convertProduct($baseproduct){
                $converter = Product::select();
                $converter->where('product_base_id', $baseproduct);
                $converter = $converter->get()->toArray();
                return $converter;
            }
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
            public function showList($id, $searchdate_start, $searchdate_end, $asp_id ): array
            {
                
                $products = DailyDiff::select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'active', 'partnership','date','daily_diffs.created_at','products.product','products.id','daily_diffs.price','cpa','cost','estimate_cv'])
                            ->join('products','daily_diffs.product_id','=','products.id')
                            ->join('asps','products.asp_id','=','asps.id');
                            if(!empty($id)){
                                $products->where('product_base_id', $id);
                            }
                            if(!empty($asp_id)){
                                $products->where('products.asp_id', $asp_id);
                            }
                            if(!empty($searchdate_start)){
                                $products->where('daily_diffs.date', '>=' , $searchdate_start);
                            }
                            if(!empty($searchdate_end)){
                                $products->where('daily_diffs.date', '<=' , $searchdate_end );
                            }
                $products = $products->get();
        /*        echo '<pre>';
                var_dump($products->toArray());
                echo '</pre>';*/

                $total = DailyDiff::select(DB::raw("date,products.id, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv,sum(estimate_cv) as total_estimate_cv,sum(active) as total_active,sum(partnership) as total_partnership,sum(daily_diffs.price) as total_price "))
                            ->join('products','daily_diffs.product_id','=','products.id')
                            ->join('asps','products.asp_id','=','asps.id');
                            if(!empty($id)){
                                $total->where('product_base_id', $id);
                            }
                            if(!empty($asp_id)){
                                $total->where('products.asp_id', $asp_id);
                            }
                            if(!empty($searchdate_start)){
                                $total->where('daily_diffs.date', '>=' , $searchdate_start);
                            }
                            if(!empty($searchdate_end)){
                                $total->where('daily_diffs.date', '<=' , $searchdate_end );
                            }
                $total = $total->get();

                $total_chart = DailyDiff::select(DB::raw("date, sum(imp) as total_imp,sum(click) as total_click,sum(cv) as total_cv"))
                            ->join('products','daily_diffs.product_id','=','products.id')
                            ->join('asps','products.asp_id','=','asps.id');
                            if(!empty($id)){
                                $total_chart->where('product_base_id', $id);
                            }
                            if(!empty($asp_id)){
                                $total_chart->where('products.asp_id', $asp_id);
                            }
                            if(!empty($searchdate_start)){
                                $total_chart->where('daily_diffs.date', '>=' , $searchdate_start);
                            }
                            if(!empty($searchdate_end)){
                                $total_chart->where('daily_diffs.date', '<=' , $searchdate_end );
                            }

                $total_chart = $total_chart->groupby('date')->get()->toArray();
                
                $data = array();
                $i = 0;
                
                foreach ($total_chart as $chart) {
                    $data[$i]['date'] = $chart['date'];
                    $data[$i]['total_imp'] = intval($chart['total_imp']);
                    $data[$i]['total_click'] = intval($chart['total_click']);
                    $data[$i]['total_cv'] = intval($chart['total_cv']);
                    $data[$i] = array_values($data[$i]);
                    $i++;
                }

                $total_chart = json_encode($data);
                //日次のグラフ用データの一覧を取得する。
                $daily_ranking = $this->dailyRankingAsp($id,$searchdate_start,$searchdate_end,$asp_id);
                
                return [$products  ,$daily_ranking , $total , $total_chart ];
            }

            public function showSiteList($id, $searchdate_start, $searchdate_end, $asp_id ): array
            {


                //開始月と終了月が同月の場合
                if (date('m', strtotime($searchdate_start)) == date('m', strtotime($searchdate_end))) {
                    $month_1 = date('Ym', strtotime($searchdate_start));
                //月を跨いでいる場合
                } else {
                    $month_1 = date('Ym', strtotime($searchdate_start));
                    $month_2 = date('Ym', strtotime($searchdate_end));
                    
                    $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
                    //終了月の検索　クエリビルダ
                    $table2 = DB::table($daily_site_diffs_table2)
                            ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id',DB::raw($daily_site_diffs_table2.'.price'),'cpa','cost','estimate_cv'])
                            ->join('products', DB::raw($daily_site_diffs_table2.'.product_id'), '=', 'products.id')
                            ->join('asps', 'products.asp_id', '=', 'asps.id');
                    if (!empty($id)) {
                        $table2 = $table2->where('product_base_id', $id);
                    }
                    if (!empty($asp_id)) {
                        $table2 = $table2->where('products.asp_id', $asp_id);
                    }
                    if (!empty($searchdate_start)) {
                        $table2 = $table2->where('date', '>=', $searchdate_start);
                    }
                    if (!empty($searchdate_end)) {
                        $table2 = $table2->where('date', '<=', $searchdate_end);
                    }
                }

                $daily_site_diffs_table = $month_1.'_daily_site_diffs';

                //開始月の検索　クエリビルダ
                $products = DB::table($daily_site_diffs_table)
                            ->select(['name', 'imp', 'click','cv', 'cvr', 'ctr', 'media_id','site_name','date','products.product','products.id',DB::raw($daily_site_diffs_table.'.price'),'cpa','cost','estimate_cv'])
                            ->join('products', DB::raw($daily_site_diffs_table.'.product_id'), '=', 'products.id')
                            ->join('asps', 'products.asp_id', '=', 'asps.id');

                if (!empty($id)) {
                    $products = $products->where('product_base_id', $id);
                }
                if (!empty($asp_id)) {
                    $products = $products->where('products.asp_id', $asp_id);
                }
                if (!empty($searchdate_start)) {
                    $products = $products->where('date', '>=', $searchdate_start);
                }
                if (!empty($searchdate_end)) {
                    $products = $products->where('date', '<=', $searchdate_end);
                }
                if (!empty($table2)) {
                    $products = $products->union($table2);
                }
                $products = $products->orderBy('cv', 'desc');
                $products = $products->limit(2500);
                $products = $products->get();


                /**
                * 日次のグラフ用データの一覧を取得する。
                */
                $site_ranking = $this->dailyRankingSite($id, $searchdate_start, $searchdate_end, $asp_id);

                return [ $products , $site_ranking ];
            }
            /**
                サイト別トップ１０のサイト一覧取得関数
            */
            public function dailyRankingSite($id = 3 ,$searchdate_start = null,$searchdate_end = null,$asp_id=null) {
                        /*
                            案件ｘ対象期間からCVがTOP10のサイトを抽出
                            「StartとEndが同じ日」もしくは、「どちらも入力されていない」の場合・・・①
                        */

                        //echo "date_start".date('m', strtotime($searchdate_start));
                        $date_start = date('Y-m-d', strtotime($searchdate_start));
                        //echo "date_end".date('m', strtotime($searchdate_end));
                        $date_end = date('Y-m-d', strtotime($searchdate_end));

                        $i = 0;
                        $table2 = '';

                        if(date('m',strtotime($date_start)) == date('m',strtotime($date_end))) {//同月の場合
                            $month_1 = date('Ym',strtotime($date_start));
                            
                        }else{//月を跨いでいる場合
                            $month_1 = date('Ym',strtotime($date_start));
                            $month_2 = date('Ym',strtotime($date_end));
                            $daily_site_diffs_table2 = $month_2.'_daily_site_diffs';
                            $table2 = DB::table($daily_site_diffs_table2)
                                    ->select(DB::raw("sum(cv) as total_cv, media_id, site_name"))
                                    ->join('products',DB::raw($daily_site_diffs_table2.'.product_id'),'=','products.id')
                                    ->join('asps','products.asp_id','=','asps.id');
                                    if(!empty($id)){
                                        $table2->where('product_base_id', $id);
                                    }
                                    if(!empty($asp_id)){
                                        $table2->where('products.asp_id', $asp_id);
                                    }
                                    if(!empty($date_start)){
                                        $table2->where('date', '>=' , $date_start );
                                    }
                                    if(!empty($date_end)){
                                        $table2->where('date', '<=' , $date_end );
                                    }
                        }
                        $daily_site_diffs_table = $month_1.'_daily_site_diffs';

                        $products_1 = DB::table($daily_site_diffs_table)
                            ->select(DB::raw("sum(cv) as total_cv, media_id, site_name"))
                            ->join('products',DB::raw($daily_site_diffs_table.'.product_id'),'=','products.id')
                            ->join('asps','products.asp_id','=','asps.id')
                            
                            ->where('cv', '!=' , 0 );
                            if(!empty($table2)){
                                $products_1->union($table2);
                            }
                            if(!empty($id)){
                                $products_1->where('product_base_id', $id);
                            }
                            if(!empty($asp_id)){
                                $products_1->where('products.asp_id', $asp_id);
                            }
                            if(!empty($date_start)){
                                $products_1->where('date', '>=' , $date_start );
                            }
                            if(!empty($date_end)){
                                $products_1->where('date', '<=' , $date_end );
                            }
                            $products_1->groupBy("media_id");
                            if(!empty($table2)){
                                $products_1->orderByRaw('total_cv DESC');
                            }else{
                                $products_1->orderByRaw('CAST(cv AS DECIMAL(10,2)) DESC');
                            }

                            $products_1->limit(10);
                            $products_1 = $products_1->get()->toArray();
                            
                            //var_dump($products_1);
                            return json_encode($products_1);

            }
            /*
                案件期間内のASPの別CV数の計算関数
            */
            public function dailyRankingAsp($id = 3,$searchdate_start = null,$searchdate_end = null ,$asp_id=null) {
                /*
                    案件ｘ対象期間から対象案件のCV件数
                */
                    $sql = 'Select DATE_FORMAT(date,"%Y/%m/%d") as date';
                    $sql_select_asp = "";
                    $asp_data = $this->filterAsp($id);

                    $asp_array = (json_decode($asp_data,true));
                    //echo gettype($asp_id);
                    foreach ($asp_array as $asp){
                            $sql_select_asp.=",max( case when asp_id=".$asp['asp_id']." then cv end ) as ".str_replace(' ', '' ,$asp["name"]);
                    }
                    $sql = $sql.$sql_select_asp;
                    $sql .= ' From daily_diffs ';
                    if($id != '' ){
                        $where = " where ";

                        $product_list = $this->convertProduct($id);
                        //var_dump($product_list);

                        $where .= " product_id in (";
                        foreach ($product_list as $product) {
                            $where .= $product['id'];

                            if($product !== end($product_list)){
                                $where .= ",";
                            }
                        }
                        $where .= " )";
                        
                    }
                    if($searchdate_start != '' ){
                        if($where !== ''){
                            $where .= " and ";
                        }else{
                            $where = " where ";
                        }
                        $where .= " date >= '". $searchdate_start ."'";
                    }
                    if($searchdate_end != '' ){
                        if($where !== ''){
                            $where .= " and ";
                        }else{
                            $where = " where ";
                        }
                        $where .= " date <= '". $searchdate_end ."'";
                    }
                    if($asp_id != '' ){
                        if($where !== ''){
                            $where .= " and ";
                        }else{
                            $where = " where ";
                        }
                        $where .= " asp_id = ". $asp_id ;
                    }
                    //echo $where;
                    if($where !== '') $sql.= $where ;
                    $sql .=' Group By  DATE_FORMAT(date,"%Y/%m/%d")';

                    $products = DB::select($sql);

                    return json_encode($products);

            }


}