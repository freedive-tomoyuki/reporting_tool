<?php
namespace App\Http\Controllers\Admin;
 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Dailyestimate;
use App\Asp;
use App\Product;
use App\Monthlydata;

 class EstimateController extends Controller
 {
    
    //public function dailyCal( $asp_id,$product_id)

    public function dailyCal($product_id)

    {
        $products = Product::where("product_base_id",$product_id)
                ->where("killed_flag", 0 )
                ->get();

        //$product_id = $id; 
        //$asp_id = 1;
        //var_dump($asps);
        var_dump($products);
        echo $date = date('Y-m-d',strtotime("-1 day"));

        
        echo $day_plus = date("Y-m-d");
        
        //echo $day_plus =  $date.strtotime("+3 day");
        //echo $day_plus = date("Y-m-d H:i:s", $date);
        /*$monthlyImp = 0;
        $monthlyClick = 0;
        $monthlyCv = 0;
        $monthlyEstimateImp = 0;
        $monthlyEstimateClick = 0;
        $monthlyEstimateCv = 0;
        $monthlyEstimateCost = 0;
        $monthlyEstimatePrice = 0;
        $monthlyApproval = 0;
        $monthlyApprovalPrice = 0;
        $monthlyCost = 0;
        $monthlyPrice = 0;
        */

        foreach($products as $product){
            echo 'P>'.$product['id'];

                    $monthly = Monthlydata::select("imp","click","cv","active","partnership","price","cost","approval","approval_price");
                    $monthly ->where("date", ">=" , $date );
                    $monthly ->where("date", "<" , $day_plus );
                    $monthly ->where("asp_id", $product['asp_id']);
                    $monthly ->where("product_id", $product['id'] );
                    $monthly = $monthly->get()->toArray();
                    //$monthly = $monthly->toSql();
                    //$daily = $daily->toSql();
                    //echo $monthly;
                    $ratio = (date("d")/date("t"));
                    
                    //echo "<br>";
                    $estimate_imp = ($monthly[0]["imp"] == 0 )? 0 : $monthly[0]["imp"] / $ratio;
                    $estimate_click = ($monthly[0]["click"] == 0 )? 0 : $monthly[0]["click"] / $ratio;
                    $estimate_cv = ($monthly[0]["cv"] == 0 )? 0 : $monthly[0]["cv"] / $ratio;

                    $estimate_cost = ($monthly[0]["cost"] == 0 )? 0 : $monthly[0]["cost"] / $ratio;
                    $estimate_price = ($monthly[0]["price"] == 0 )? 0 : $monthly[0]["price"] / $ratio;
                    $estimate_cvr = ($estimate_cv == 0 || $estimate_click == 0)? 0 : intval($estimate_cv) / intval($estimate_click)*100;
                    $estimate_ctr = ($estimate_click == 0 || $estimate_imp == 0)? 0 : intval($estimate_click) / intval($estimate_imp)*100;
                    $estimate_cpa = ($estimate_price == 0 || $estimate_cv == 0 )? 0 : $estimate_price / $estimate_cv;

                    $estimateInstance = New Dailyestimate;
                    
                    $estimateInstance->Create(
                        [
                            'asp_id' => $product['asp_id'],
                            'product_id' => $product['id'],
                            'estimate_imp' => $estimate_imp,
                            'estimate_click' => $estimate_click,
                            'estimate_cv' => $estimate_cv,
                            'estimate_cost' => $estimate_cost,
                            'estimate_price' => $estimate_price,
                            'estimate_cvr' => $estimate_cvr,
                            'estimate_ctr' => $estimate_ctr,
                            'estimate_cpa' => $estimate_cpa,
                            'ratio' => $ratio * 100,
                            'date' => date('Y-m-d', strtotime('-1 day')),
                        ]
                    );
                    /*
                    echo "<br>(1)>".$monthlyEstimateImp = $monthlyEstimateImp + $estimate_imp ;
                    echo "<br>estimate_imp>".$estimate_imp;
                    echo "<br>(2)>".$monthlyEstimateClick = $monthlyEstimateClick + $estimate_click ;
                    echo "<br>estimate_click>".$estimate_click;
                    echo "<br>(3)>".$monthlyEstimateCv = $monthlyEstimateCv + $estimate_cv ;
                    echo "<br>estimate_cv>".$estimate_cv;
                    echo "<br>(7)>".$monthlyEstimateCost = (int)$monthlyEstimateCost + (int)$estimate_cost;
                    echo "<br>(8)>".$monthlyEstimatePrice = (int)$monthlyEstimatePrice + (int)$estimate_price ;

                    echo "<br>(4)>".$monthlyImp = (int)$monthlyImp + (int)$monthly[0]["imp"] ;
                    echo "<br>(5)>".$monthlyClick = (int)$monthlyClick + (int)$monthly[0]["click"] ;
                    echo "<br>(6)>".$monthlyCv = (int)$monthlyCv + (int)$monthly[0]["cv"];
                    echo "<br>(4)>".$monthlyCost = (int)$monthlyCost + (int)$monthly[0]["cost"] ;
                    echo "<br>(5)>".$monthlyPrice = (int)$monthlyPrice + (int)$monthly[0]["price"] ;
                    */
                    /**
                        承認件数のトータル算出
                    */
                    /*
                    echo "<br>(12)>".$monthlyApproval =  (int)$monthlyApproval + (int)$monthly[0]["approval"] ;
                    echo "<br>(13)>".$monthlyApprovalPrice =  (int)$monthlyApprovalPrice + (int)$monthly[0]["approval_price"] ;
                    */
        }
/*
                    echo "(9)>".$monthlyEstimateCvr = 
                    ($monthlyEstimateCv == 0 || $monthlyEstimateClick == 0 )? 0 : ((int)$monthlyEstimateCv / (int)$monthlyEstimateClick)*100;
                    echo "(10)>".$monthlyEstimateCtr = 
                    ($monthlyEstimateClick == 0 || $monthlyEstimateImp == 0 )? 0 : ((int)$monthlyEstimateClick / (int)$monthlyEstimateImp)*100;
                    echo "(11)>".$monthlyEstimateCpa = 
                    ($monthlyEstimatePrice == 0 || $monthlyEstimateCv == 0 )? 0 : (int)$monthlyEstimatePrice / (int)$monthlyEstimateCv ;

                    echo "(9)>".$monthlyCvr = ($monthlyCv == 0 || $monthlyClick == 0 )? 0 : ((int)$monthlyCv / (int)$monthlyClick)*100 ;
                    echo "(10)>".$monthlyCtr = ($monthlyClick == 0 || $monthlyImp == 0 )? 0 : ((int)$monthlyClick / (int)$monthlyImp)*100;
                    echo "(11)>".$monthlyCpa = ($monthlyPrice == 0 || $monthlyCv == 0 )? 0 : (int)$monthlyPrice / (int)$monthlyCv ;
                    
*/
                    //$estimateTotalInstance = New DailyEstimateTotal;
                    /**
                    　着地想定の合計
                    */
                    /*$estimateTotalInstance->Create(
                        [
                            'product_base_id' => $product_id,
                            'estimate_total_imp' => $monthlyEstimateImp,
                            'estimate_total_click' => $monthlyEstimateClick,
                            'estimate_total_cv' => $monthlyEstimateCv,
                            'estimate_total_cost' => $monthlyEstimateCost,
                            'estimate_total_price' => $monthlyEstimatePrice,
                            'estimate_total_cvr' => $monthlyEstimateCvr,
                            'estimate_total_ctr' => $monthlyEstimateCtr,
                            'estimate_total_cpa' => $monthlyEstimateCpa,
                            'total_imp' => $monthlyImp,
                            'total_click' => $monthlyClick,
                            'total_cv' => $monthlyCv,
                            'date' => date('Y-m-d', strtotime('-1 day')),
                        ]
                    );*/

                    //$MonthlyTotalInstance = New MonthlyTotal;
                    /**
                        現時点　月次合計　件数
                    */
                    /*$MonthlyTotalInstance->Create(
                        [

                            'product_base_id' => $product_id, 
                            'total_imp' => $monthlyImp, 
                            'total_click' => $monthlyClick,
                            'total_cv' => $monthlyCv,
                            'total_cvr' => $monthlyCvr,
                            'total_ctr' => $monthlyCtr,
                            'total_cost' => $monthlyCost,
                            'total_price' => $monthlyPrice, 
                            'total_cpa' => $monthlyCpa,
                            'total_approval' => $monthlyApproval,
                            'total_approval_price' => $monthlyApprovalPrice,
                            'date' => date('Y-m-d', strtotime('-1 day')),
                        ]
                    );*/

    }

}