<?php

namespace App\Http\Controllers\Admin\Asp\Daily;

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\DailyCrawlerController;

use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\DailyDiff;
use App\DailySiteDiff;
use App\Mail\Alert;
use Mail;

class S8Controller extends DailyCrawlerController
{
    
        public function s8($product_base_id)
        {
            $product_id = $this->dailySearchService->BasetoProduct( 12, $product_base_id );
        
            $promotion = \App\Product::where( 'id', $product_id )->get();
            try{
                //$product_infos = \App\Product::all()->where('id',$product_id);
                    //$product_infos = \App\Product::all()->where( 'id', $product_id );
                                    //$promotion = $this->dailySearchService->BasetoProduct( 12, $product_base_id );
                    

                    //クロール実行が1日のとき
                    $start = date( 'Y-m-01 00:00:00', strtotime( '-1 day' ) );
                    $end = date( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );
                    //var_dump($promotion[0]['asp_product_id']);

                    $curl = curl_init();
                    $data = array(
                        'start'=>$start,
                        'end'=>$end ,
                        'promotion'=>$promotion[0]['asp_product_id'],
                    );
                    //var_dump($data);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($curl, CURLOPT_URL, 'https://s8affi.net/api/index.php');
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                    //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 証明書の検証を行わない
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // curl_execの結果を文字列で返す
                    
                    $response = curl_exec($curl);
                    $results = json_decode($response, true);
                    //var_dump($response);
                    echo '<pre>';
                    var_dump($results);
                    echo '</pre>';
                    curl_close($curl);
                    //{ [0]=> array(13) { ["advertiser"]=> string(13) "5ba1c7b19466f" ["advertiser_name"]=> string(9) "横田様" ["promotion"]=> string(13) "5ba1def3079a3" ["promotion_name"]=> string(31) "BRILLIANCE＋【来店予約】" ["count_cv"]=> string(1) "2" ["costs"]=> string(4) "8500" ["prices"]=> string(1) "0" ["count_apply"]=> string(1) "0" ["count_apply_price"]=> string(1) "0" ["count_cancel"]=> string(1) "0" ["count_other"]=> string(1) "2" ["count_click"]=> string(3) "217" ["count_imp"]=> NULL } }
                    
                    $i = 0;

                    foreach($results as $result){
                        $calData = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $result['count_cv'], $result['costs'], 12 ) ) ), True );
                        if(){
                            $s8Data[$i] = array(
                                'imp'=> ($result['count_imp'])?$result['count_imp']:0,
                                'click'=> ($result['count_click'])?$result['count_click']:0,
                                'cv'=> ($result['count_cv'])?$result['count_cv']:0,
                                'active'=> 0,
                                'partnership'=> 0,
                                'price'=> ($result['prices'] )?$result['prices']:0,
                                'cost'=> ($calData['cost'])?$calData['cost']:0 ,
                                'cpa'=> ($calData['cpa'])?$calData['cpa']:0 ,
                                'date'=> date( 'Y-m-d', strtotime( '-1 day' ) ),
                                'product'=>  $promotion[0]['id'],
                                'asp'=> $promotion[0]['asp_id'],
                                'approval'=> $result['count_apply'],
                                'approval_price'=> $result['count_apply_price'],
                                
                            );
                        }else{
                            $s8SiteData[$i] = array(
                                //'imp'=> ($result['count_imp'])?$result['count_imp']:0,
                                //'click'=> ($result['count_click'])?$result['count_click']:0,
                                'cv'=> ($result['count_cv'])?$result['count_cv']:0,
                                'active'=> 0,
                                'partnership'=> 0,
                                'price'=> ($result['prices'] )?$result['prices']:0,
                                'cost'=> ($calData['cost'])?$calData['cost']:0 ,
                                'cpa'=> ($calData['cpa'])?$calData['cpa']:0 ,
                                'date'=> date( 'Y-m-d', strtotime( '-1 day' ) ),
                                'product'=>  $promotion[0]['id'],
                                'asp'=> $promotion[0]['asp_id'],
                                'approval'=> $result['count_apply'],
                                'approval_price'=> $result['count_apply_price'],
                                
                            );
                        }
                        $i++;
                    }
                    // var_dump($s8Data);
                    $this->dailySearchService->save_daily( json_encode( $s8Data ) );
                    $this->dailySearchService->save_daily( json_encode( $s8Data ) );
                    //$product_id = $this->dailySearchService->BasetoProduct( 3, $product_base_id );
                    
                    //$s8 = curl("https://s8affi.net/api/index.php");
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $promotion[0]['id'],
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                            throw $e;
            }
        }
}
