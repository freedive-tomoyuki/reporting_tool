<?php

namespace App\Http\Controllers\Admin\Asp\Daily;

use Laravel\Dusk\Browser;
use Illuminate\Http\Request;
use Revolution\Salvager\Client;
use App\Http\Controllers\Controller;
use Revolution\Salvager\Drivers\Chrome;
use Symfony\Component\DomCrawler\Crawler;
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

class FelmatController extends DailyCrawlerController
{
    
    
    /**
    　再現性のある数値を生成 サイトIDとして適用
    */
    public function siteCreate($siteName, $seed)
    {
        $siteId = '';
        //echo $siteName;
        mt_srand($seed, MT_RAND_MT19937);
        foreach (str_split($siteName) as $char) {
            $char_array[] = ord($char) + mt_rand(0, 255);
        }
        //var_dump($char_array);
        $siteId = mb_substr(implode($char_array), 0, 100);
        //echo $siteId;
        
        return $siteId;
    }
    /**
    Felmat
    */
    public function felmat($product_base_id) //OK
    {
        
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
        $product_id = $this->dailySearchService->BasetoProduct(6, $product_base_id);
        
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
        $client->browse(function(Browser $browser) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where('id', $product_id);
                    
                    $first = date( 'Y-m-01', strtotime( '-1 day' ) );
                    $end = date( 'Y-m-d', strtotime( '-1 day' ) );
                    
                    foreach ($product_infos as $product_info) {
                        // /var_dump($product_info->asp);
                        /*
                        クロール：ログイン＝＞パートナー分析より検索
                        */
                        $crawler = $browser
                            ->visit($product_info->asp->login_url)
                            ->type($product_info->asp->login_key, $product_info->login_value)
                            ->type($product_info->asp->password_key, $product_info->password_value)
                            ->click($product_info->asp->login_selector)
                            ->visit("https://www.felmat.net/advertiser/report/daily")
                            //->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $yesterday)
                            ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                            ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                            ->click('#sel_promotion_id_chosen')
                            ->click($product_info->product_order)
                            ->click('#view > div > button.btn.btn-primary.btn-sm')->crawler();
                        //echo $crawler->html();
                        /*
                        クロール：
                        */
                        
                        $crawler2 = $browser->visit("https://www.felmat.net/advertiser/report/partnersite") //->crawler();
                            //->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $yesterday)
                            ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                            ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                            ->click('#sel_promotion_id_chosen')->click($product_info->product_order)->click('#view > div > button.btn.btn-primary.btn-sm')->crawler();
                        //echo $crawler2->html();
                        /*
                        クロール：
                        */
                        
                        $crawler3 = $browser->visit("https://www.felmat.net/advertiser/publisher/data")->click('#sel_adv_id_chosen')->click('#sel_adv_id_chosen > div > ul > li.active-result.result-selected')->click('#view > div > button.btn.btn-primary.btn-sm')->crawler();
                        //echo $crawler3->html();
                        
                        /*
                        selector 設定
                        */
                        $selector1 = array(
                            'imp' => '#report > div > table > tfoot > tr > th:nth-child(2)', //$product_info->asp->daily_imp_selector,
                            'click' => '#report > div > table > tfoot > tr > th:nth-child(3)', //$product_info->asp->daily_click_selector,
                            'cv' => '#report > div > table > tfoot > tr > th:nth-child(5)', //$product_info->asp->daily_cv_selector,
                            //'price' => '#report > div > table > tfoot > tr > th:nth-child(6)'
                            
                        );
                        $selector2 = array(
                            'active' => 'body > div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(5) > div > div:nth-child(2) > div:nth-child(1) > div:nth-child(3) > div'
                            //$product_info->asp->daily_partnership_selector,
                        );
                        $selector3 = array(
                            'partnership' => 'body > div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(4) > div > div:nth-child(2) > div.row > div:nth-child(3) > div'
                        );
                        
                        
                        
                        /*
                        $crawler　をフィルタリング
                        */
                        $felmat_data = $crawler->each(function(Crawler $node) use ($selector1, $product_info)
                        {
                            
                            $data            = array();
                            $data['asp']     = $product_info->asp_id;
                            $data['product'] = $product_info->id;
                            
                            $data['date'] = date('Y-m-d', strtotime('-1 day'));
                            
                            foreach ($selector1 as $key => $value) {
                                if(count($node->filter( $value ))){
                                    $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            }
                            
                            return $data;
                            
                        });
                        /*
                        $crawler2　をフィルタリング
                        */
                        $felmat_data2 = $crawler2->each(function(Crawler $node) use ($selector2, $product_info)
                        {
                            
                            $data = array();
                            
                            foreach ($selector2 as $key => $value) {
                                if(count($node->filter( $value ))){
                                    $data[$key] = intval(trim(preg_replace('/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 7))));
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            }
                            return $data;
                            
                        });
                        //var_dump($felmat_data2);
                        /*
                        $crawler3　をフィルタリング
                        */
                        $felmat_data3 = $crawler3->each(function(Crawler $node) use ($selector3, $product_info)
                        {
                            
                            $data = array();
                            
                            foreach ($selector3 as $key => $value) {
                                if(count($node->filter( $value ))){
                                    preg_replace('/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 7));
                                    mb_substr($node->filter($value)->text(), 0, 7);
                                    $data[$key] = intval(trim(preg_replace('/[^0-9]/', '', mb_substr($node->filter($value)->text(), 0, 7))));
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            }
                            
                            return $data;
                            
                        });
                        //var_dump($felmat_data3);
                        /*
                        サイト抽出　
                        */
                        
                        //echo "アクティブ数:".$felmat_data3[0]['active'];
                        //echo "パートナー数:".$felmat_data2[0]['partnership'];
                        $page            = ceil($felmat_data2[0]['active'] / 20);
                        $count_last_page = $felmat_data2[0]['active'] % 20;
                        $count           = 0;
                        
                        for ($i = 1; $page >= $i; $i++) {
                            //echo "ページ数page:" . $page;
                            //echo "ページ数i:" . $i;
                            $crawlCountPerOne = ($page == $i) ? $count_last_page : 20;
                            
                            //最後のページ
                                if ($i > 1) {
                                    $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite") ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)->click('#sel_promotion_id_chosen')->click($product_info->product_order)->click('#view > div > button.btn.btn-primary.btn-sm');
                                    $p = $i + 1;
                                    
                                    $crawler_for_site->click('div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(5) > div > div:nth-child(2) > div:nth-child(1) > div:nth-child(2) > div > ul > li:nth-child(' . $p . ') > a');
                                }else{
                                    $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite") ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)->click('#sel_promotion_id_chosen')->click($product_info->product_order)->click('#view > div > button.btn.btn-primary.btn-sm');
                                }

                            
                            $crawler_for_site = $crawler_for_site->crawler();
                            
                            //var_dump($crawler_for_site->html());
                            
                            
                            for ($x = 1; $crawlCountPerOne >= $x; $x++) {
                                $felmat_site[$count]['product'] = $product_info->id;
                                $felmat_site[$count][ 'asp' ]   = $product_info->asp_id;
                                //echo "CountX:" . $x;
                                
                                
                                //echo 'iPlus'.$iPlus;
                                
                                $selector_for_site = array(
                                    'site_name' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td.left',
                                    'imp' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(2)',
                                    'click' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(3)',
                                    'cv' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(5)',
                                    //'price' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(6)'
                                );
                                
                                foreach ($selector_for_site as $key => $value) {
                                    if(count($crawler_for_site->filter( $value ))){
                                        if ($key == 'site_name') {
                                            
                                            $felmat_site[$count][$key]       = trim($crawler_for_site->filter($value)->text());
                                            $felmat_site[$count]['media_id'] = $this->siteCreate(trim($crawler_for_site->filter($value)->text()), 20);

                                        } else {
                                            
                                            $felmat_site[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                        }
                                    }else{
                                        throw new \Exception($value.'要素が存在しません。');
                                    }
                                    
                                }
                                $unit_price = $product_info->price;
                                $felmat_site[ $count ][ 'price' ] = $unit_price * $felmat_site[ $count ][ 'cv' ];

                                $calculated                     = json_decode(
                                                                    json_encode(
                                                                        json_decode(
                                                                            $this->dailySearchService
                                                                                ->cpa($felmat_site[$count]['cv'], $felmat_site[$count]['price'], 5)
                                                                        )
                                                                    ), 
                                                                True);
                                $felmat_site[$count]['cpa']  = $calculated['cpa']; //CPA
                                $felmat_site[$count]['cost'] = $calculated['cost'];
                                $felmat_site[$count]['date'] = date('Y-m-d', strtotime('-1 day'));
                                $count++;
                                
                            }
                        }
                        
                        
                        //$felmat_data[0]['price'] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > table > tbody > tr.total > td:nth-child(15)')->text()));
                        $unit_price = $product_info->price;
                        $felmat_data[ 0 ][ 'price' ] = $felmat_data[ 0 ][ 'cv' ] * $unit_price;

                        $felmat_data[0]['active']      = $felmat_data2[0]['active'];
                        $felmat_data[0]['partnership'] = $felmat_data3[0]['partnership'];
                        
                        $calculated                = json_decode(json_encode(json_decode($this->dailySearchService->cpa($felmat_data[0]['cv'], $felmat_data[0]['price'], 5))), True);
                        $felmat_data[0]['cpa']  = $calculated['cpa']; //CPA
                        $felmat_data[0]['cost'] = $calculated['cost'];

                        //echo "<pre>";
                        //var_dump($felmat_data);
                        //var_dump($felmat_site);
                        //echo "</pre>";
                        
                        /*
                        サイトデータ・日次データ保存
                        */
                        $this->dailySearchService->save_site(json_encode($felmat_site));
                        $this->dailySearchService->save_daily(json_encode($felmat_data));
                        
                        //var_dump($crawler_for_site);
                    }
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'フェルマ',
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }
        });
        
    }
}
