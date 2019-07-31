<?php

namespace App\Http\Controllers\Admin\Asp\Monthly;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Http\Controllers\Admin\MonthlyCrawlerController;
use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use DB;
//header('Content-Type: text/html; charset=utf-8');

class FelmatController extends MonthlyCrawlerController
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
    public function felmat( $product_base_id ) //OK
    {

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
        $product_id = $this->BasetoProduct( 6, $product_base_id );

        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            $product_infos = \App\Product::all()->where( 'id', $product_id );
            $array = array();

            foreach ( $product_infos as $product_info ) {
                $crawler = $browser->visit($product_info->asp->login_url)
                        ->type($product_info->asp->login_key, $product_info->login_value)
                        ->type($product_info->asp->password_key, $product_info->password_value)
                        ->click($product_info->asp->login_selector);
                
                for ( $x = 0; $x < 2; $x++ ) {
                        if ( $x == 0 ) {
                            $first = date( 'Y-m-01', strtotime( '-1 day' ) );
                            $end = date( 'Y-m-d', strtotime( '-1 day' ) );
                        } //$x == 0
                        else {
                            
                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $first = date( 'Y-m-01', strtotime( '-2 month' ) );
                                $end = date( 'Y-m-t', strtotime( '-2 month' ) );
                            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            else {
                                $first = date( 'Y-m-01', strtotime( 'first day of previous month' ) );
                                $end = date( 'Y-m-t', strtotime( 'last day of previous month' ) );
                            }
                            
                        }
                    
                    $crawler = $browser
                        ->visit("https://www.felmat.net/advertiser/report/daily")
                        ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                        ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                        ->click('#sel_promotion_id_chosen')
                        ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                        //->select('adv_id', '1050' )
                        ->click('#view > div > button.btn.btn-primary.btn-sm')->crawler();


                    $selector   = array(
                            'approval' => '#report > div > table > tfoot > tr > th:nth-child(8)', 
                            'approval_price' => '#report > div > table > tfoot > tr > th:nth-child(9)'
                    );


                    /**
                    今月・先月用のデータ取得selector
                    */
                    $felmat_data[$x] = $crawler->each( function( Crawler $node ) use ($selector, $product_info, $end){
                        
                        $data              = array( );
                        $data[ 'asp' ]     = $product_info->asp_id;
                        $data[ 'product' ] = $product_info->id;

                        foreach ( $selector as $key => $value ) {
                            $data[ 'date' ] = $end;

                            if($key == 'approval_price'){
                                $data[ $key ]   = 
                                    $this->calc_approval_price(
                                        trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ) ,2
                                    );
                            }
                            else{
                                $data[ $key ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                            }

                        }
                        return $data;
                    });
                }

                foreach ($felmat_data as $value){
                    array_push($array , $value[0]);
                }
                /*
                  $crawler サイト用　をフィルタリング
                */
                //$count      = 0;
                $count           = 0;

                for ( $y = 0; $y < 2; $y++ ) {
                    if ( $y == 0 ) {
                        $first = date( 'Y-m-01', strtotime( '-1 day' ) );
                        $end = date( 'Y-m-d', strtotime( '-1 day' ) );
                    } //$x == 0
                    else {
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $first = date( 'Y-m-01', strtotime( '-2 month' ) );
                            $end = date( 'Y-m-t', strtotime( '-2 month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $first = date( 'Y-m-01', strtotime( 'first day of previous month' ) );
                            $end = date( 'Y-m-t', strtotime( 'last day of previous month' ) );
                        }
                    }

                    //アクティブ件数取得
                    $crawler = $browser->visit("https://www.felmat.net/advertiser/report/partnersite") //->crawler();
                    ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                    ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                    ->click('#sel_promotion_id_chosen')
                    ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                    ->click('#view > div > button.btn.btn-primary.btn-sm')
                    ->crawler();
                    $selector ='body > div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(5) > div > div:nth-child(2) > div:nth-child(1) > div:nth-child(3) > div';
                    
                    echo "アクティブ数";
                    echo $active = intval(trim(preg_replace('/[^0-9]/', '', mb_substr($crawler->filter($selector)->text(), 0, 7))));

                    $page            = ceil($active / 20);
                    $count_last_page = $active % 20;


                    for ($i = 1; $page >= $i; $i++) {
                        echo "ページ数page:" . $page;
                        echo "ページ数i:" . $i;
                        $crawlCountPerOne = ($page == $i) ? $count_last_page : 20;
                        
                        //最後のページ
                        if ($i > 1) {
                            $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite") ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)->click('#sel_promotion_id_chosen')->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')->click('#view > div > button.btn.btn-primary.btn-sm');
                            $p = $i + 1;
                            
                            $crawler_for_site->click('div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(5) > div > div:nth-child(2) > div:nth-child(1) > div:nth-child(2) > div > ul > li:nth-child(' . $p . ') > a');
                        }else{
                            $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite") ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)->click('#sel_promotion_id_chosen')->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')->click('#view > div > button.btn.btn-primary.btn-sm');
                        }
                        
                        $crawler_for_site = $crawler_for_site->crawler();
                        
                        //var_dump($crawler_for_site->html());
                        
                        for ($x = 1; $crawlCountPerOne >= $x; $x++) {
                            $felmat_site[$count]['product'] = $product_info->id;
                            echo "CountX:" . $x;
                            //$iPlus = $x ;
                            //echo 'iPlus'.$iPlus;
                            
                            $selector_for_site = array(
                                'site_name' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td.left',
                                'approval' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(8)',
                                'approval_price' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(9)',
                            );

                            $felmat_site[$count]['date'] = $end;

                            foreach ($selector_for_site as $key => $value) {
                                if ($key == 'site_name') {
                                    
                                    $felmat_site[$count][$key]       = trim($crawler_for_site->filter($value)->text());
                                    $felmat_site[$count]['media_id'] = $this->siteCreate(trim($crawler_for_site->filter($value)->text()), 20);
                                } else {
                                    
                                    $felmat_site[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                }
                                
                            }
                            $count++;
                            
                        }
                    }
                    
                }

                echo "<pre>";
                var_dump($array);
                var_dump($felmat_site);
                echo "</pre>";
                /*
                サイトデータ・日次データ保存
                */
                $this->save_site( json_encode( $felmat_site ) );
                $this->save_monthly( json_encode( $array ) );
            
            } //$product_infos as $product_info
        } );
        
    }
}