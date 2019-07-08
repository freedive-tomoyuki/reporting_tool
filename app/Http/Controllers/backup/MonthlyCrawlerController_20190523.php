<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;

//use Revolution\Salvager\Facades\Salvager;

use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use DB;

//header('Content-Type: text/html; charset=utf-8');

class MonthlyCrawlerController extends Controller
{

    public function save_monthly($data){
        $data_array = json_decode(json_encode(json_decode($data)), True );

        DB::table('monthlydatas')
          ->where('product_id', $data_array[0]['product'])
          ->where('date', $data_array[0]['date'])

          ->update([
              'approval_price' => $data_array[0]['approval_price'],
              'approval' => $data_array[0]['approval'],
          ]);
        
        DB::table('monthlydatas')
              ->where('product_id', $data_array[0]['product'])
              ->where('date', $data_array[0]['last_date'])

              ->update([
                  'approval_price' => $data_array[0]['last_approval_price'],
                  'approval' => $data_array[0]['last_approval'],
        ]);

    }
    public function save_site($data){
        
        $data_array = json_decode(json_encode(json_decode($data)), True );

        foreach($data_array as $data ){

            /*echo  $data['product'];
            echo  $data['media_id'];
            echo  $data['date'];*/
            
            DB::table('monthlysites')
              ->where('product_id', $data['product'])
              ->where('media_id', $data['media_id'])
              ->where('date', $data['date'])

              ->update([
                  'approval_price' => $data['approval_price'],
                  'approval' => $data['approval'],
            ]);

        }

    }
    public function cpa($cv ,$price ,$asp){
      $calData = array();

      if( $asp == 1 ){//A8の場合
        $asp_fee = ($price * 1.2 * 1.08) * 1.08 ;

      }else{//それ以外のASPの場合
        $asp_fee = ($price * 1.3 * 1.08) ;
      }
        $total = $asp_fee * 1.2 ;

        echo $calData['cpa'] = round(($total == 0 || $cv == 0 )? 0 : $total / $cv);
        echo $calData['cost'] = $total;

      return json_encode($calData);
    }

    public function BasetoProduct($asp_id, $baseproduct){
        $converter = Product::select();
        $converter->where('product_base_id', $baseproduct);
        $converter->where('asp_id', $asp_id );
        $converter = $converter->get()->toArray();
              //var_dump($converter[0]["id"]);
        return $converter[0]["id"];
    } 
    public function a8($product_base_id){

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
              $product_id = $this->BasetoProduct(1, $product_base_id);

              $client = new Client(new Chrome($options));

              $client->browse(function (Browser $browser) use (&$crawler,$product_id) {

                $product_infos = \App\Product::all()->where('id',$product_id);
                
                foreach ($product_infos as $product_info){

                  $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )
                                  ->click($product_info->asp->login_selector) 
                                  ->visit($product_info->asp->lp2_url)
                                  ->select('#reportOutAction > table > tbody > tr:nth-child(2) > td > select','21')
                                  ->radio('insId',$product_info->asp_product_id)
                                  ->click('#reportOutAction > input[type="image"]:nth-child(3)')
                                  ->crawler();


                                $selector_this = array (
                                    'approval' => '#element > tbody > tr:nth-child(1) > td:nth-child(10)',
                                    'approval_price' => '#element > tbody > tr:nth-child(1) > td:nth-child(13)',
                                );
                                $selector_before = array (
                                    'approval' => '#element > tbody > tr:nth-child(1) > td:nth-child(10)',
                                    'approval_price' => '#element > tbody > tr:nth-child(1) > td:nth-child(13)',
                                );

                                $a8data = $crawler->each(function (Crawler $node)use ( $selector_this,$selector_before ,$product_info){
                                  $data = array();
                                  $data['asp'] = $product_info->asp_id;
                                  $data['product'] = $product_info->id;

                                  foreach($selector_this as $key => $value){
                                      $data[$key] = trim($node->filter($value)->text());
                                      $data['date'] = date('Y-m-d', strtotime('-1 day'));
                                  }
                                  foreach($selector_before as $key => $value){
                                      $data['last_'.$key] = trim($node->filter($value)->text());
                                      $data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                                  }
                                  return $data;

                                });

                                var_dump($a8data);
                            
                            $this->save_monthly(json_encode($a8data));
                }
            });

    }
   public function accesstrade($product_base_id){//OK

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
            
            $product_id = $this->BasetoProduct(2, $product_base_id);

            $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {

              $product_infos = \App\Product::all()->where('id',$product_id);
                
                foreach ($product_infos as $product_info){
                  // /var_dump($product_info->asp);
                  $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
                                /**
                                *    承認ベース用のページに変更
                                */
                                  ->visit('https://merchant.accesstrade.net/matv3/program/report/monthly/approved.html?programId='.$product_info->asp_product_id )
                                  ->crawler();

                                /**
                                *    今月用のデータ取得selector
                                */
                                $selector_this = array (
                                    'approval' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(1) > td:nth-child(4)',
                                    'approval_price' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(1) > td:nth-child(7)'
                                );
                                $selector_before = array (
                                    'approval' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(2) > td:nth-child(4)',
                                    'approval_price' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(2) > td:nth-child(7)'
                                );
                                var_dump($crawler);
                                //$crawler->each(function (Crawler $node) use ( $selector ){
                              /**
                                  今月用のデータ取得selector
                              */
                                $atdata = $crawler->each(function (Crawler $node)use ( $selector_this ,$selector_before,$product_info){
                                
                                      $data = array();
                                      $data['asp'] = $product_info->asp_id;
                                      $data['product'] = $product_info->id;
                                      //$data['date'] = date('Y-m-d', strtotime('-1 day'));

                                      foreach($selector_this as $key => $value){
                                            $data['date'] = date('Y-m-d', strtotime('-1 day'));
                                            $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                      }
                                      foreach($selector_before as $key => $value){
                                            $data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                                            $data['last_'.$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                      }
                                  return $data;

                                });

                                $array_site = array();

                                $start_date = [ date('Y-m-01'), date('Y-m-d', strtotime('first day of previous month'))];
                                $end_date = [ date('Y-m-d',strtotime('-1 day')), date('Y-m-d', strtotime('last day of previous month'))];

                                $x = 0; 

                                for($i = 0 ; $i < count($start_date); $i++ ){

                                  $crawler_for_site = $browser->visit("https://merchant.accesstrade.net/mapi/program/".$product_info->asp_product_id."/report/partner/monthly/approved?targetFrom=".$start_date[$i]."&targetTo=".$end_date[$i]."&pointbackSiteFlagList=0,1")->crawler();


                                  $array_site=$crawler_for_site->text();

                                  $array_site = json_decode($array_site,true);
                                  
                                  $array_sites = $array_site["report"];

                                
                                  foreach($array_sites as $site){
                                    $data[$x]['product'] = $product_info->id ;
                                    $data[$x]['date'] = $end_date[$i];
                                    $data[$x]['media_id'] = $site["partnerSiteId"] ;
                                    $data[$x]['site_name'] = $site["partnerSiteName"] ;
                                    $data[$x]['approval'] = $site["approvedCount"] ;
                                    $data[$x]['approval_price'] = $site["approvedTotalReward"] ;

                                    $x++;
                                  
                                  }

                                }
                                //var_dump($data);
                                //var_dump($atdata);

                            $this->save_site(json_encode($data));
                            $this->save_monthly(json_encode($atdata));
                }
                                
            });

    }
   public function valuecommerce($product_base_id){//OK
/**

  ブラウザ立ち上げ

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
            $product_id = $this->BasetoProduct(3, $product_base_id);

            $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {
            
              //$product_infos = \App\Product::all()->where('id',$product_id);
              $product_infos = \App\Product::all()->where('id',$product_id);

              foreach ($product_infos as $product_info){
/**

  実装：ログイン

*/
              $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
/**

  実装：初期ページ

*/
                                  ->visit("https://mer.valuecommerce.ne.jp/report/sales_performance/")
                                  ->select('#condition_fromMonth','4')
                                  ->select('#condition_toMonth','5')
                                  ->click('#show_statistics')
                                  ->crawler();
                                  //var_dump($crawler);
/**
  先月・今月のセレクタ
*/
                              $selector_this = array (
                                    'approval' => '#reportCompare > tbody > tr:nth-child(2) > td:nth-child(11)',
                                    'approval_price' => '#reportCompare > tbody > tr:nth-child(2) > td:nth-child(20)'
                              );
                              $selector_before = array (
                                    'approval' => '#reportCompare > tbody > tr:nth-child(1) > td:nth-child(11)',
                                    'approval_price' => '#reportCompare > tbody > tr:nth-child(1) > td:nth-child(20)'
                              );
/**
  セレクターからフィルタリング
*/
                              $vcdata = $crawler->each(function (Crawler $node) use ( $selector_this ,$selector_before,$product_info){
                                  $data = array();
                                  $data['asp'] = $product_info->asp_id;
                                  $data['product'] = $product_info->id;
                                  
                                  //echo $node->html();
                                  foreach($selector_this as $key => $value){
                                        $data['date'] = date('Y-m-d', strtotime('-1 day'));
                                        $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                  }
                                  foreach($selector_this as $key => $value){
                                        $data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                                        $data['last_'.$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                  }
                                  return $data;
                              });

                              var_dump($vcdata);
                              //１ページ目クロール
                              //$pagination_page = $product_info->asp->lp2_url;
                              //$crawler_for_site = $browser->visit($pagination_page)->crawler();
                              
/**
  サイト取得用クロール
*/
                                
                                //$x = 0; 
                                $addtion = 0 ;

                                for($x = 0 ; $x < 2 ; $x++ ){

                                      //サイト数取得用にクロール
                                      //デフォルトでは、今月分のクロールを実行
                                      if( $x == 0 ){
                                        $crawler_for_site = $browser->visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/')
                                        ->crawler();
                                      //先月分のクロール
                                      } else{
                                        $y = date('Y',strtotime('-1 month'));
                                        $n = date('n',strtotime('-1 month'));

                                        $crawler_for_site = //$browser->visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/')
                                        //->select('#condition_fromMonth', $n )
                                        //->select('#condition_toMonth', $n )
                                        //->click('#show_statistics')
                                        $browser->visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D='.$y.'&condition%5BfromMonth%5D='.$n.'&condition%5BtoYear%5D='.$y.'&condition%5BtoMonth%5D='.$n.'&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page='.$target_page)
                                        ->crawler();
                                      }

                                      //　アクティブサイト数（https://mer.valuecommerce.ne.jp/affiliate_analysis/） 
                                      $active = explode("/", $crawler_for_site->filter("#cusomize_wrap > span")->text());
                                      echo "active件数→".$active[1]."←active件数";

                                      //ページ数を計算　＝　アクティブサイト数 / ４０
                                      $count_page = ($active[1]>40)? ceil($active[1]/40) : 1 ;
                                      echo "count_page件数→".$count_page."←count_page件数";

                                      //var_dump($crawler_for_site);

                                      
                                      /**
                                      *      １ページ　クロール
                                      */
                                      for($page = 0 ; $page < $count_page ; $page++){
                                            
                                            $target_page = $page+1;

                                            $y = ( $x == 0 )? date("Y") : date("Y",strtotime('-1 month')) ;

                                            $m = ( $x == 0 )? date("n") : date("n",strtotime('-1 month')) ;

                                          //今月分クロール
                                            $crawler_for_site = $browser->visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D='.$y.'&condition%5BfromMonth%5D='.$m.'&condition%5BtoYear%5D='.$y.'&condition%5BtoMonth%5D='.$m.'&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page='.$target_page)->crawler();
                                            echo 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D='.$y.'&condition%5BfromMonth%5D='.$m.'&condition%5BtoYear%5D='.$y.'&condition%5BtoMonth%5D='.$m.'&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page='.$target_page;
                                              //最終ページのみ件数でカウント
                                              $crawler_count = ( $target_page == $count_page )? $active[1]-($page * 40) : 40 ;
                                              echo $crawler_count;
                                              echo $target_page."ページ目のcrawler_count＞＞".$crawler_count."</br>" ;
                                          /**
                                            １行ごと　クロール
                                          */
                                          for($i=1 ; $i <= $crawler_count ; $i++){
                                            
                                              //1ページMAXの件数は４０件
                                              $count = ($page*40)+$i+$addtion;
                                              echo "count→".$count."←count";
                                              $data[$count]['product'] = $product_info->id;

                                            if($crawler_for_site->filter('#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(2)')->count() != 0){
                                              
                                                $selector_for_site = array(
                                                      'media_id'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(2)',
                                                      'site_name'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(3) > a',
                                                      'approval'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(10)',
                                                      'approval_price'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(21)',
                                                );
                                            
                                                foreach($selector_for_site as $key => $value){
                                                    
                                                    $data[$count]['date'] =( $x == 0 )? date('Y-m-d',strtotime('-1 day')):date('Y-m-d',strtotime('last day of previous month'));

                                                    if( $key == 'site_name' ){
                                                    
                                                        $data[$count][$key] = trim($crawler_for_site->filter($value)->text());
                                                    
                                                    }elseif( $key == 'approval' ){
                                                        $approval_array = array();
                                                        $approval = trim($crawler_for_site->filter($value)->text());
                                                        preg_match('/(\d+)/', $approval , $approval_array);
                                                        $data[$count][$key] = $approval_array[1];
                                                    }else{
                                                      $data[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                                    }
                                                }

                                              }
                                          }
                                          
                                      }
                                      $addtion = $active[1];
                                }
                              var_dump($data);
                              $this->save_monthly(json_encode($vcdata));
                              $this->save_site(json_encode($data));

              }
            });
    }
   public function afb($product_base_id){//OK
/**

  ブラウザ立ち上げ

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

            $product_id = $this->BasetoProduct(4, $product_base_id);

            $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {
              
              $product_infos = \App\Product::all()->where('id', $product_id);
                
              foreach ($product_infos as $product_info){

/**

  実装：ログイン

*/
                  //$crawler = $browser->visit("https://www.nursejinzaibank.com/glp")->crawler();
                  $crawler = $browser->visit("https://www.afi-b.com/")
                                  
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )
                                  ->click($product_info->asp->login_selector)
                                  
                                  ->visit("https://client.afi-b.com/client/b/cl/report/?r=monthly")
                                  /**
                                    レポート期間
                                  */
                                  ->select('#form_start_month','04')
                                  ->select('#form_end_month','05')
                                  /**
                                    案件選択
                                  */
                                  ->click('#adv_id_monthly_chzn > a')
                                  ->click('#adv_id_monthly_chzn_o_1')
                                  /**
                                    表示するデバイスを絞る
                                  */
                                  ->click('#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(1)')
                                  ->click('#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(2)')
                                  ->click('#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(3)')
                                  ->click('#report_form_1 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')
                                  ->crawler();
                              var_dump( $crawler);
/**
  先月・今月のセレクタ
*/
                              $selector_this = array (
                                    'approval' => '#reportTable > tbody > tr:nth-child(2) > td:nth-child(10) > p',
                                    'approval_price' => '#reportTable > tbody > tr:nth-child(2) > td:nth-child(13) > p'
                              );
                              $selector_before = array (
                                    'approval' => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(10) > p',
                                    'approval_price' => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(13) > p'
                              );
/**
  セレクターからフィルタリング
*/
                              $afbdata = $crawler->each(function (Crawler $node) use ( $selector_this ,$selector_before,$product_info){
                                
                                $data = array();
                                $data['asp'] = $product_info->asp_id;
                                $data['product'] = $product_info->id;

                                /*foreach($selector_this as $key => $value){
                                  $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                }*/
                                foreach($selector_this as $key => $value){
                                  $data['date'] = date('Y-m-d', strtotime('-1 day'));
                                  $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                }
                                foreach($selector_before as $key => $value){
                                  $data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                                  $data['last_'.$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                }
                                return $data;
                              });

                              var_dump( $afbdata);

/**
  サイト取得用クロール
*/
#report_form_4 > div > table > tbody > tr:nth-child(4) > td > p > label:nth-child(5)
                              $click_month = [ "#report_form_4 > div > table > tbody > tr:nth-child(4) > td > p > label:nth-child(5)", "#report_form_4 > div > table > tbody > tr:nth-child(4) > td > p > label:nth-child(6)"];
                              $end_date = [ date('Y-m-d', strtotime('last day of previous month')) , date('Y-m-d',strtotime('-1 day'))];
                              $y = 0;
                              $afbsite = array();

                              for($x = 0 ; $x < count($click_month); $x++ ){
                                $crawler_for_site = $browser
                                  ->visit('https://client.afi-b.com/client/b/cl/report/?r=site')
                                  //->radio('span','tm')
                                  ->click('#site_tab_bth')
                                  /**
                                    レポート期間（今月）
                                  */
                                  //->type('#form_start_date',date('Y/m/01'), strtotime('-1 month'))//
                                  ->type('#report_form_4 > div > table > tbody > tr:nth-child(4) > td > ul > li:nth-child(1) > input',date('Y/m/01', strtotime('-1 month')))
                                  //->type('#form_end_date',date('Y/m/d', strtotime('-1 day')))

                                  ->type('#report_form_4 > div > table > tbody > tr:nth-child(4) > td > ul > li:nth-child(3) > input',date('Y/m/d', strtotime('-1 day')))
                                  //->click()
                                  /**
                                    案件選択
                                  */
                                  ->click('#adv_id_pssite_chzn > a')
                                  ->click('#adv_id_pssite_chzn_o_1')
                                  /**
                                    表示するデバイスを絞る
                                  */
                                  ->click('#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(1)')
                                  ->click('#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(2)')
                                  ->click('#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(3)')
                                  ->click('#report_form_4 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')
                                  ->crawler();

                                //$count_data = trim($crawler_for_site->filter('#report_view > div > ul > li:nth-child(5)')->text());
                                $count_data = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#report_view > div > ul > li:nth-child(5)')->text()));

                                
                                //echo "ここから";
                                //echo $count_data;

                                //echo "ここまで";
                                //var_dump( $crawler_for_site->html());
                                /**
                                  サイト一覧　１ページ分のクロール
                                */
                                  for( $i = 1 ; intval($count_data) >= $i ; $i++ ){
                                    $afbsite[$y]['product'] = $product_info->id;
                                    $afbsite[$y]['date'] = $end_date[$x];

                                    $selector_for_site = array(
                                      #reportTable > tbody > tr:nth-child(6) > td.maxw150
                                              'media_id'=>'#reportTable > tbody > tr:nth-child('.$i.') > td.maxw150',
                                              'site_name'=>'#reportTable > tbody > tr:nth-child('.$i.') > td.maxw150 > p > a',
                                              'approval'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(13) > p',
                                              'approval_price'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(16) > p',
                                    );
                                    /**
                                      サイト一覧　１行ずつクロール
                                    */
                                    foreach($selector_for_site as $key => $value){
                                        
                                        if( $key == 'media_id' ){
                                          //$data = trim($node->filter($value)->attr('title'));
                                          $media_id = array();
                                          $sid = trim($crawler_for_site->filter($value)->attr('title'));
                                          preg_match('/SID：(\d+)/', $sid , $media_id);
                                          //echo "sid:".$sid;
                                          //echo "media_id:".
                                          //var_dump($media_id);

                                          $afbsite[$y][$key] = $media_id[1];

                                        }elseif( $key == 'site_name' ){
                                        
                                          $afbsite[$y][$key] = trim($crawler_for_site->filter($value)->text());
                                        
                                        }else{
                                        
                                          $afbsite[$y][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                        
                                        }
                                       
                                    }// endforeach 
                                    $y++ ;
                                  }// endfor
                              }
                              var_dump($afbsite);

                              //$afbdata[0]['active'] = $active[0];
                              //$afbdata[0]['partnership'] = $partnership[0];
                              
                              

                              $this->save_monthly(json_encode($afbdata));
                              $this->save_site(json_encode($afbsite));

                          }
                    });
    }

   public function rentracks($product_base_id){//OK

    /*
     昨日の日付　取得
    */
            $yesterday = date('d', strtotime('-1 day'));

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
            $product_id = $this->BasetoProduct(5, $product_base_id);

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
            $client->browse(function (Browser $browser) use (&$crawler,$product_id,$yesterday) {

              $product_infos = \App\Product::all()->where('id',$product_id);
            

                foreach ($product_infos as $product_info){
                  // /var_dump($product_info->asp);
                /*
                  クロール：ログイン＝＞アクセス統計分析より検索
                      https://manage.rentracks.jp/sponsor/detail_access
                */
                  $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->asp->login_key , $product_info->login_value)
                                  ->type($product_info->asp->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
                                  ->visit($product_info->asp->lp1_url)
                                  ->select('#idDropdownlist1',$product_info->asp_product_id)
                                  ->select('#idDoneDay', $yesterday )
                                  ->click('#idButton1')
                                  ->crawler();
                /*
                  selector 設定
                */
                                $selector_this = array (
                                      'approval' => '#main > table > tbody > tr:nth-child(8) > td:nth-child(4)',
                                      'approval_price' => '#main > table > tbody > tr.total > td:nth-child(4)'
                                );
                                $selector_before = array (
                                      'approval' => '#main > table > tbody > tr:nth-child(8) > td:nth-child(3)',
                                      'approval_price' => '#main > table > tbody > tr.total > td:nth-child(3)'
                                );

                                //var_dump($crawler);
                                //var_dump($crawler2);
                                //var_dump($crawler3);
                                //$crawler->each(function (Crawler $node) use ( $selector ){

                /*
                  $crawler　をフィルタリング
                */
                                $rtdata = $crawler->each(function (Crawler $node)use ( $selector_this ,$selector_before,$product_info){
                                
                                      $data = array();
                                      $data['asp'] = $product_info->asp_id;
                                      $data['product'] = $product_info->id;
                                      
                                      foreach($selector_this as $key => $value){
                                        $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                        $data['date'] = date('Y-m-d', strtotime('-1 day'));
                                      }
                                      foreach($selector_before as $key => $value){
                                        $data['last_'.$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                        $data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                                      }
                                  return $data;

                                });

                /*
                  サイト抽出　
                */
                                $rtsite = array();

                                //$x = 0; 
                                $y = 1;

                                for($x = 0 ; $x < 2 ; $x++ ){
                                        echo 'xのあたい'.$x ;
                                        if($x == 0){
                                           $start = $product_info->asp_product_id;
                                           $end = $yesterday;
                                        }else{
                                           $start = date('Y-m-01',strtotime('-1 month'));
                                           $end = date('Y-m-d', strtotime('last day of previous month'));
                                        }
                                          $crawler_for_site = $browser
                                            ->visit("https://manage.rentracks.jp/sponsor/detail_partner")
                                            ->select('#idDropdownlist1',$start)
                                            ->select('#idDoneDay', $end)
                                            ->select('#idPageSize','300')
                                            ->click('#idButton1')
                                            ->crawler();

                                            $active_partner = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > div.hitbox > em')->text()));
                                            //echo $crawler_for_site->html();
                                        for( $i = 1 ; $active_partner >= $i ; $i++ ){

                                            $rtsite[$y]['product'] = $product_info->id;
                                            $rtsite[$y]['date'] = ( $x == 0 )? date('Y-m-d',strtotime('-1 day')) : date('Y-m-d',strtotime('last day of previous month'));
                                              echo $rtsite[$y]['date'];
                                              $iPlus = $i+1;
                                              //月内の場合は、「承認」先月のものに関しては、「請求済」からデータを取得
                                              $approval_selector = ( $x == 0 )? '#main > table > tbody > tr:nth-child('.$iPlus.') > td.c13':'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c14';
                                              $approvalprice_selector= ( $x == 0 )? '#main > table > tbody > tr:nth-child('.$iPlus.') > td.c18':'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c19';

                                            $selector_for_site = array(
                                              'media_id'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c03',
                                              'site_name'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c04',
                                              'approval'=>$approval_selector,
                                              'approval_price'=>$approvalprice_selector,
 
                                            );

                                          foreach($selector_for_site as $key => $value){
                                              if( $key == 'site_name' ){
                                        
                                                $rtsite[$y][$key] = trim($crawler_for_site->filter($value)->text());
                                              
                                              }else{
                                              
                                                $rtsite[$y][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                              }

                                          }
                                          $y++;
                                        }
                                        
                                }

                                  echo "<pre>";
                                  var_dump($rtdata);
                                  var_dump($rtsite);
                                  echo "</pre>";
                /*
                  サイトデータ・月次データ保存
                */
                            $this->save_site(json_encode($rtsite));
                            $this->save_monthly(json_encode($rtdata));
                
                            //var_dump($crawler_for_site);
                }

            });

    }
    public function filterAsp( $product_id ){
      $target_asp = Product::select('asp_id','name')
                  ->join('asps','products.asp_id','=','asps.id')
                  ->where('product_base_id', $product_id )
                  ->where('products.killed_flag', 0 )
                  ->get();

      return json_encode($target_asp);
    }
    public function run(Request $request){

        $aspRow = array();
        $asp_array = array();

        $asp_name = $this->filterAsp($request->product);
        var_dump($asp_name);
        $asp_array = (json_decode($asp_name,true));
        //echo gettype($asp_id);
        foreach ($asp_array as $name){
          array_push($aspRow,str_replace(' ', '' ,mb_strtolower($name["name"])));
        }
        var_dump($aspRow);

        foreach($aspRow as $function_name){
          $this->{$function_name}($request->product);
        }
        //推定値　
        app()->call( 'App\Http\Controllers\EstimateController@dailyCal', ['product_id'=> $request->product] );
          //$this->a8($request->product);
          //$this->rentracks($request->product);
          //$this->accesstrade($request->product);
          //
          //$this->valuecommerce($request->product);
          //$this->afb($request->product);
          //echo "a";
          //return view('daily_result');
          return redirect()->to('/daily_result', $status = 302, $headers = [], $secure = null);
    }
    public function monthlytimer(){

      $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();
      var_dump($products);

      foreach($products as $product){
          $aspRow = array();
          $asp_array = array();
          //echo $product["product_base_id"];
          $asp_name = $this->filterAsp($product["product_base_id"]);
          var_dump($asp_name);
          $asp_array = (json_decode($asp_name,true));
          //echo gettype($asp_id);
          foreach ($asp_array as $name){
            array_push($aspRow,str_replace(' ', '' ,mb_strtolower($name["name"])));
          }
          //var_dump($aspRow);

          foreach($aspRow as $function_name){
            $this->{$function_name}($product["product_base_id"]);
            
          }
          app()->call( 'App\Http\Controllers\EstimateController@dailyCal', ['product_id'=> $product["product_base_id"]] );
      }
    }



  }

