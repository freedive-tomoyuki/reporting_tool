<?php

namespace App\Http\Controllers\Asp;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use App\Http\Controllers\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\DailyDiff;
use App\DailySiteDiff;
//header('Content-Type: text/html; charset=utf-8');

class FelmatController extends Controller
{

/**
 Felmat
*/
    public function felmat(){//OK

    /*
     昨日の日付　取得
    */
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            //$yesterday = date('d', strtotime('-1 day'));
    /*
     ChromeDriverのオプション設定
    */
            Browser::macro('crawler', function () {
                return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
            });

            $options = [
                '--window-size=1920,1080',
                '--start-maximized',
                '--headless',
                '--disable-gpu',
                '--no-sandbox'
                
            ];
    /*
      案件の大本IDからASP別のプロダクトIDを取得
    */
            //$product_id = $this->BasetoProduct(5, $product_base_id);

    /*
      Chromeドライバーのインスタンス呼び出し
    */
            $client = new Client(new Chrome($options));

    /*
      Chromeドライバー実行
      　引数
      　　$product_id:案件ID
      　　$yesterday:昨日の日付
    */
            $client->browse(function (Browser $browser) use (&$crawler,$yesterday) {

              $product_infos = \App\Product::all()->where('id',15);
            

                foreach ($product_infos as $product_info){
                  // /var_dump($product_info->asp);
                /*
                  クロール：ログイン＝＞パートナー分析より検索
                */
                  echo $yesterday;
                  $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->asp->login_key , $product_info->login_value)
                                  ->type($product_info->asp->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
                                  ->visit("https://www.felmat.net/advertiser/report/daily")

                                  ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)',$yesterday)
                                  //->select('#sel_promotion_id', 1050 )
                                  ->click('#sel_promotion_id_chosen')
                                  ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                                  ->click('#view > div > button.btn.btn-primary.btn-sm')
                                  ->crawler();
                                  //echo $crawler->html();
                /*
                  クロール：
                */

                  $crawler2 = $browser->visit("https://www.felmat.net/advertiser/report/partnersite")//->crawler();
                                  ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)',$yesterday)
                                  //->select('#sel_promotion_id', 1050 )
                                  ->click('#sel_promotion_id_chosen')
                                  ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                                  ->click('#view > div > button.btn.btn-primary.btn-sm')
                                  ->crawler();
                                  //echo $crawler2->html();
                /*
                  クロール：
                */

                  $crawler3 = $browser->visit("https://www.felmat.net/advertiser/publisher/data")
                                  ->click('#sel_adv_id_chosen')
                                  ->click('#sel_adv_id_chosen > div > ul > li.active-result.result-selected')
                                  ->click('#view > div > button.btn.btn-primary.btn-sm')
                                  ->crawler();
                                  echo $crawler3->html();

                  $crawler4 = $browser->visit("https://www.felmat.net/advertiser/report/partnersite")
                                  ->click('#view > div > button.btn.btn-warning.btn-sm.hidden-xs');
                                  //->crawler();
                                  //echo $crawler3->html();

                                  
                                  

                /*
                  selector 設定
                */
                                $selector1 = array (
                                      'imp' => '#report > div > table > tfoot > tr > th:nth-child(2)',//$product_info->asp->daily_imp_selector,
                                      'click' => '#report > div > table > tfoot > tr > th:nth-child(3)',//$product_info->asp->daily_click_selector,
                                      'cv' => '#report > div > table > tfoot > tr > th:nth-child(5)',//$product_info->asp->daily_cv_selector,
                                      'price' => '#report > div > table > tfoot > tr > th:nth-child(6)',
                                      
                                );
                                $selector2 = array (
                                      'active' => 'body > div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(5) > div > div:nth-child(2) > div:nth-child(1) > div:nth-child(3) > div'
                                      //$product_info->asp->daily_partnership_selector,
                                );
                                $selector3 = array (
                                      'active' => 'body > div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(4) > div > div:nth-child(2) > div.row > div:nth-child(3) > div' ,
                                );



                /*
                  $crawler　をフィルタリング
                */
                                $felmat_data = $crawler->each(function (Crawler $node)use ( $selector1 ,$product_info){
                                
                                      $data = array();
                                      $data['asp'] = $product_info->asp_id;
                                      $data['product'] = $product_info->id;
                                      
                                      $data['date'] = date('Y-m-d', strtotime('-1 day'));

                                      foreach($selector1 as $key => $value){
                                        $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                      }

                                  return $data;

                                });
                /*
                  $crawler2　をフィルタリング
                */
                                $felmat_data2 = $crawler2->each(function (Crawler $node)use ( $selector2 ,$product_info){
                                
                                      $data = array();

                                      foreach($selector2 as $key => $value){

                                        //$data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                        //echo mb_substr($node->filter($value)->text(), 0, 7);
                                        $data[$key] = intval(
                                                     trim(
                                                       preg_replace(
                                                         '/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 7)
                                                       )
                                                     )
                                                 );
                                

                                      }

                                  return $data;

                                });
                                var_dump($felmat_data2);
                /*
                  $crawler3　をフィルタリング
                */
                                $felmat_data3 = $crawler3->each(function (Crawler $node)use ( $selector3 ,$product_info){
                                
                                      $data = array();

                                      foreach($selector3 as $key => $value){
                                        echo preg_replace(
                                                         '/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 7)
                                                       );
                                        echo mb_substr($node->filter($value)->text(), 0, 7);
                                        $data[$key] = intval(
                                                     trim(
                                                       preg_replace(
                                                         '/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 7)
                                                       )
                                                      )
                                                   );
                                                    
                                      }

                                  return $data;

                                });
                                var_dump($felmat_data3);
                /*
                  サイト抽出　
                */
                                $crawler_for_site = $browser
                                            ->visit("https://manage.rentracks.jp/sponsor/detail_partner")
                                            ->select('#idDropdownlist1',$product_info->asp_product_id)
                                            ->select('#idDoneDay', $yesterday)
                                            ->select('#idPageSize','300')
                                            ->click('#idButton1')
                                            ->crawler();

                                            var_dump($crawler_for_site->html());
                                            //アクティブ件数を取得
                                            $active_partner = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > div.hitbox > em')->text()));
                                            echo $active_partner;
                                        for( $i = 1 ; $active_partner >= $i ; $i++ ){
                                            $rtsite[$i]['product'] = $product_info->id;

                                            $iPlus = $i+1;
                                            echo 'iPlus'.$iPlus;
                                            
                                            $selector_for_site = array(
                                              'media_id'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c03',
                                              'site_name'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c04 > a',
                                              'imp'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c05',
                                              'click'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c06',
                                              'cv'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c10',
                                              'price'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c15',
                                            );

                                          foreach($selector_for_site as $key => $value){
                                              if( $key == 'site_name' ){
                                        
                                                $rtsite[$i][$key] = trim($crawler_for_site->filter($value)->text());
                                              
                                              }else{
                                              
                                                $rtsite[$i][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                              }

                                          }
                                          $calData = json_decode(
                                                      json_encode(
                                                        json_decode($this->cpa($felmat_site[$i]['cv'] ,$felmat_site[$i]['price'] , 5))
                                                      ), True
                                                    );
                                          $felmat_site[$i]['cpa']= $calData['cpa']; //CPA
                                          $felmat_site[$i]['cost']= $calData['cost'];
                                          $felmat_site[$i]['date'] = date('Y-m-d', strtotime('-1 day'));
                                        }


                            $felmat_data[0]['price'] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > table > tbody > tr.total > td:nth-child(15)')->text()));

                            $felmat_data[0]['partnership'] = $felmat_data2[0]['partnership'];
                            $felmat_data[0]['active'] = $felmat_data3[0]['active'];

                            $calData = json_decode(
                                          json_encode(json_decode($this->cpa($felmat_data[0]['cv'] ,$felmat_data[0]['price'] , 5))), True
                                        );
                            $felmat_data[0]['cpa']= $calData['cpa']; //CPA
                            $felmat_data[0]['cost']= $calData['cost'];
                            
/*                            echo "<pre>";
                            var_dump($rtdata);
                            var_dump($rtsite);
                            echo "</pre>";
*/
                /*
                  サイトデータ・日次データ保存
                */
                 //           $this->save_site(json_encode($rtsite));
                 //           $this->save_daily(json_encode($rtdata));
                
                            //var_dump($crawler_for_site);
                }

            });

    }
}