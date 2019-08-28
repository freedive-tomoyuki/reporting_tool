<?php

namespace App\Http\Controllers\Admin\Asp\Daily;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\DailyCrawlerController;
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

class CrossPartnerController extends DailyCrawlerController
{
/**
　再現性のある数値を生成 サイトIDとして適用
*/
    public function siteCreate($siteName,$seed){
      $siteId='';
      //echo $siteName;
      mt_srand($seed, MT_RAND_MT19937);
      foreach(str_split($siteName) as $char) {
            $char_array[] = ord($char) + mt_rand(0, 255) ;
      }
      //var_dump($char_array);
      $siteId = mb_substr(implode($char_array),0,100);
      //echo $siteId;

      return $siteId;
    }    
    public function crosspartner( $product_base_id ) //OK
    {
        echo $product_base_id ;
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
        
        //案件の大本IDからASP別のプロダクトIDを取得
        $product_id = $this->BasetoProduct( 10, $product_base_id );
        var_dump($product_id);
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            
            $product_infos = \App\Product::all()->where( 'id', $product_id );
	        var_dump($product_infos);

            $ym = date( 'Ym', strtotime( '-1 day' ) );

            foreach ( $product_infos as $product_info ) {
                
                $crawler_1 = $browser
                ->visit( $product_info->asp->login_url )
                ->keys( $product_info->asp->login_key, $product_info->login_value )
                ->keys( $product_info->asp->password_key, $product_info->password_value )
                ->click( $product_info->asp->login_selector )
                ->visit( $product_info->asp->lp1_url )
                ->visit('http://crosspartners.net/agent/clients/su/'.$product_info->asp_sponsor_id)
                //->visit('http://crosspartners.net/master/result_reports/ajax_paging/is_monthly:1/start:201907/end:201907/user_site_id:/ad_id:252?_=1563862637371')
                ->visit('http://crosspartners.net/master/result_reports/index/is_daily:1')
                ->visit('http://crosspartners.net/master/result_reports/ajax_paging/page:1/is_daily:1/start:'.$ym.'/end:'.$ym.'/ad_id:'.$product_info->asp_product_id.'/sort:start/direction:asc?_=1563862637371')
                ->crawler();

                echo $crawler_1->html();

                //パートナー数
                $crawler_2 = $browser->visit('http://crosspartners.net/master/joins/index/in_session:0')->visit('http://crosspartners.net/master/joins/ajax_paging?_=1564026842857')->crawler();
                echo $crawler_2->html();

                //パートナー別レポート
                //$crawler_3 = $browser->visit('http://crosspartners.net/master/result_reports/ajax_paging/is_partners:1/start:201907/end:201907/user_site_id:/ad_id:252?_=1563862637371')->crawler();
                //echo $crawler_3->html();
                
                //

                $selector_2 = array(
                    'partnership' => 'div.paging_top > div.paging_counter' 
                );
                
                /*
                  $crawler　をフィルタリング
                */
                $crosspartner_data1 = $crawler_1->each(function (Crawler $node)use ( $product_info){
                        $data = array();
                        $data['asp'] = $product_info->asp_id;
                        $data['product'] = $product_info->id;
                        $y = 0;
                        $data['date'] = date('Y-m-d', strtotime('-1 day'));
                        $limit = date('d');

                        for( $d = 1 ; $d <= $limit ; $d++ ){
                            $selector_1 = array(
                                'imp' => 'table.highlight > tbody > tr:nth-child('.$d.') > td:nth-child(2)',
                                'click' => 'table.highlight > tbody > tr:nth-child('.$d.') > td:nth-child(3)',
                                'cv' => 'table.highlight > tbody > tr:nth-child('.$d.') > td:nth-child(5)',
                                'price' => 'table.highlight > tbody > tr:nth-child('.$d.') > td:nth-child(9)',
                            );

                            foreach($selector_1 as $key => $value){
                                if($y == 0){
                                    $imp = 0;
                                    $click = 0;
                                    $cv = 0;
                                    $price = 0;
                                }
                                if( $key == 'imp' ){
                                    $imp += trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                    $data['imp'] = $imp;
                                }elseif( $key == 'click' ){
                                    $click += trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                    $data['click'] = $click;
                                }elseif( $key == 'cv' ){
                                    $cv += trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                    $data['cv'] = $cv;
                                }else{
                                    $price += trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                    $data['price'] = $price;
                                }
                                $y++ ;
                            }
                        }

                        return $data;
                });
                //var_dump($crosspartner_data1);
                /*
                  $crawler　をフィルタリング
                */
                $crosspartner_data2 = $crawler_2->each(function (Crawler $node)use ( $selector_2 ,$product_info){
                                
                        $data = array();

                        foreach($selector_2 as $key => $value){
                            $data[$key] = intval(trim(preg_replace('/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 8))));
                        }

                        return $data;
                });
                //var_dump($crosspartner_data2);
                /*
                  $crawler サイト用　をフィルタリング
                */
                //$page = ceil($felmat_data2[0]['active'] / 20 );
                //$count_last_page = $felmat_data2[0]['active'] % 20;
                $count      = 0;
                $iPlus       = 1;
                //for( $i = 1 ; $page >= $i ; $i++ ){

                $crawler_for_site = $browser->visit('http://crosspartners.net/master/result_reports/ajax_paging/is_partners:1/start:'.$ym.'/end:'.$ym.'/user_site_id:/ad_id:'.$product_info->asp_product_id.'?_=1563862637371')->crawler();
                

                while ( trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $crawler_for_site->filter( 'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(1)' )->count() ) ) ) ) {
                    echo "iPlus".$iPlus ;
                    echo trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $crawler_for_site->filter( 'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(1)' )->text() ) ) );

                                $crosspartner_site[$count]['product'] = $product_info->id;

                                $selector_for_site = array(
                                    'media_id'  =>'table.highlight > tbody > tr:nth-child('.$iPlus.')',
                                    'site_name' =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(1)',
                                    'imp'       =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(3)',
                                    'click'     =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(4)',
                                    'cv'        =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(6)',
                                    'price'     =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(10)',
                                );

                                foreach($selector_for_site as $key => $value){
                                    if( $key == 'site_name' ){
                                        $crosspartner_site[$count][$key] = trim($crawler_for_site->filter($value)->text());
                                        //$crosspartner_site[$count]['media_id'] = $this->siteCreate(trim($crawler_for_site->filter($value)->text()),20);
                                    }elseif($key == 'media_id' ){
                                        $member_id_array = array( );
                                        $member_id_source = $crawler_for_site->filter($value)->each(function (Crawler $c) {
                                          return $c->attr('id');
                                        });
                                        preg_match( '/member_id:(\d+)/', $member_id_source[0], $member_id_array );
                                        echo $crosspartner_site[$count][$key] = $member_id_array[ 1 ];
                                    }else{
                                        $crosspartner_site[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                    }

                                }
                                

                                $calData = json_decode(
                                            json_encode(
                                              json_decode($this->cpa($crosspartner_site[$count]['cv'] ,$crosspartner_site[$count]['price'] , 5))
                                            ), True
                                          );
                                $crosspartner_site[$count]['cpa']= $calData['cpa']; //CPA
                                $crosspartner_site[$count]['cost']= $calData['cost'];
                                $crosspartner_site[$count]['date'] = date('Y-m-d', strtotime('-1 day'));
                                
                                $count++;
                                $iPlus++;
                            //}
                }
                


                            //$felmat_data[0]['price'] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > table > tbody > tr.total > td:nth-child(15)')->text()));

                            //$crosspartner_data[0]['active'] = $crosspartner_data2[0]['active'];
                $crosspartner_data1[0]['active'] = $iPlus ;
                $crosspartner_data1[0]['partnership'] = $crosspartner_data2[0]['partnership'];

                $calData = json_decode(
                                  json_encode(json_decode($this->cpa($crosspartner_data1[0]['cv'] ,$crosspartner_data1[0]['price'] , 5))), True
                            );
                $crosspartner_data1[0]['cpa']= $calData['cpa']; //CPA
                $crosspartner_data1[0]['cost']= $calData['cost'];
                            //var_dump($media_id_pre);
                echo "<pre>";
                var_dump($crosspartner_data1);
                var_dump($crosspartner_site);
                echo "</pre>";
                /*
                サイトデータ・日次データ保存
                */
                $this->save_site( json_encode( $crosspartner_site ) );
                $this->save_daily( json_encode( $crosspartner_data1 ) );
            
            } //$product_infos as $product_info
        } );
        
    }
}