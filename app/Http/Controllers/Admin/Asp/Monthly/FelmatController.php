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
use App\Mail\Alert;
use Mail;
use DB;
//header('Content-Type: text/html; charset=utf-8');

class FelmatController extends MonthlyCrawlerController
{
//再現性のある数値を生成 サイトIDとして適用
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
        $products =  json_decode($this->monthlySearchService->BasetoProduct( 6, $product_base_id ),true);

        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        foreach($products as $p ){
            
            $product_id = $p['id'];
            $product_name = $p['product'];

            //Chromeドライバー実行
            $client->browse( function( Browser $browser ) use (&$crawler, $product_id, $product_name)
            {
                try{
                        $product_infos = \App\Product::all()->where( 'id', $product_id );
                        $felmat_data = array();

                        foreach ( $product_infos as $product_info ) {
                            
                            for ( $x = 0; $x < 2; $x++ ) {
                                    if ( $x == 0 ) {
                                        $first = date( 'Y-m-01', strtotime( '-1 day' ) );
                                        $end = date( 'Y-m-d', strtotime( '-1 day' ) );
                                    }
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
                                
                                $crawler = $browser->visit($product_info->asp->login_url)
                                    ->type($product_info->asp->login_key, $product_info->login_value)
                                    ->type($product_info->asp->password_key, $product_info->password_value)
                                    ->click($product_info->asp->login_selector)
                                    ->visit("https://www.felmat.net/advertiser/report/daily")
                                    ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                                    ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                                    ->click('#sel_promotion_id_chosen')
                                    ->click($product_info->product_order)
                                    // ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                                    // ->select('adv_id', '1050' )
                                    ->click('#view > div > button.btn.btn-primary.btn-sm')
                                    ->crawler();


                                $selector   = array(
                                    'approval' => '#report > div > table > tfoot > tr > th:nth-child(8)', 
                                    'approval_price' => '#report > div > table > tfoot > tr > th:nth-child(9)'
                                );


                                //今月・先月用のデータ取得selector
                                $crawler_data[$x] = $crawler->each( function( Crawler $node ) use ($selector, $product_info, $end){
                                    
                                    $data              = array( );
                                    $data[ 'asp' ]     = $product_info->asp_id;
                                    $data[ 'product' ] = $product_info->id;


                                    // $unit_price = $product_info->price;
                                    $data[ 'date' ] = $end;

                                    if(count($node->filter( $selector['approval'] ))){
                                        $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector['approval'] )->text() ) );
                                    }else{ $data[ 'approval' ] = 0; }//throw new \Exception( $selector['approval'].'要素が存在しません。'); }
                                
                                    if(count($node->filter( $selector['approval_price'] ))){
                                        $data[ 'approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                            trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector['approval_price'] )->text() ) )
                                                                        ,6);
                                    }else{ $data[ 'approval_price' ] = 0; }//throw new \Exception($selector['approval_price'].'要素が存在しません。'); }

                                    // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                                    return $data;
                                });
                            }
                            //var_dump($crawler_data);
                            foreach ($crawler_data as $value){
                                array_push($felmat_data , $value[0]);
                            }
                            // $crawler サイト用　をフィルタリング
                            $count           = 0;

                            for ( $y = 0; $y < 2; $y++ ) {
                                if ( $y == 0 ) {
                                    $first = date( 'Y-m-01', strtotime( '-1 day' ) );
                                    $end = date( 'Y-m-d', strtotime( '-1 day' ) );
                                } 
                                else {
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $first = date( 'Y-m-01', strtotime( '-2 month' ) );
                                        $end = date( 'Y-m-t', strtotime( '-2 month' ) );
                                    }
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
                                
                                //echo "アクティブ数";

                                if(count($crawler->filter( $selector ))){
                                
                                    $active = intval(trim(preg_replace('/[^0-9]/', '', mb_substr($crawler->filter($selector)->text(), 0, 7))));
                                
                                }else{ $active = 0;}//throw new \Exception($selector.'要素が存在しません。'); }
                                
                                $page            = ceil($active / 20);
                                $count_last_page = $active % 20;
                                \Log::info('active：'.$active );

                                if( $active > 0 ){
                                    for ($i = 1; $page >= $i; $i++) {
                                        // echo "ページ数page:" . $page;
                                        // echo "ページ数i:" . $i;
                                        $crawlCountPerOne = ($page == $i) ? $count_last_page : 20;
                                        
                                        //最後のページ
                                        if ($i > 1) {
                                            $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite?pg=".$i);
                                                \Log::info('1以降ページ数：'.$i);
                                            // $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite")
                                            //                             ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                                            //                             ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                                            //                             ->click('#sel_promotion_id_chosen')
                                            //                             // ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                                            //                             ->click($product_info->product_order)
                                            //                             ->click('#view > div > button.btn.btn-primary.btn-sm');
                                            // $p = $i + 1;
                                            
                                            // $crawler_for_site->click('div.wrapper > div.page-content.no-left-sidebar > div > div:nth-child(5) > div > div:nth-child(2) > div:nth-child(1) > div:nth-child(2) > div > ul > li:nth-child(' . $p . ') > a');
                                        }else{
                                            $crawler_for_site = $browser->visit("https://www.felmat.net/advertiser/report/partnersite")
                                                                        ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(1)', $first)
                                                                        ->type('#search > div > div:nth-child(2) > div.col-sm-4.form-inline > div > input:nth-child(3)', $end)
                                                                        ->click('#sel_promotion_id_chosen')
                                                                        // ->click('#sel_promotion_id_chosen > div > ul > li:nth-child(2)')
                                                                        ->click($product_info->product_order)
                                                                        ->click('#view > div > button.btn.btn-primary.btn-sm');
                                        }
                                        
                                        $crawler_for_site = $crawler_for_site->crawler();
                                        
                                        //var_dump($crawler_for_site->html());
                                        
                                        for ($x = 1; $crawlCountPerOne >= $x; $x++) {
                                            $felmat_site[$count]['product'] = $product_info->id;
                                            //echo "CountX:" . $x;
                                            //$iPlus = $x ;
                                            //echo 'iPlus'.$iPlus;
                                            
                                            $selector_for_site = array(
                                                'site_name' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td.left',
                                                'approval' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(8)',
                                                'approval_price' => '#report > div > table > tbody > tr:nth-child(' . $x . ') > td:nth-child(9)',
                                            );

                                            $felmat_site[$count]['date'] = $end;

                                            foreach ($selector_for_site as $key => $value) {
                                                if(count($crawler_for_site->filter( $value ))){
                                                    if ($key == 'site_name') {
                                                        
                                                        $felmat_site[$count][$key]       = trim($crawler_for_site->filter($value)->text());
                                                        $felmat_site[$count]['media_id'] = $this->siteCreate(trim($crawler_for_site->filter($value)->text()), 20);
                                                    }
                                                    elseif ( $key == 'approval_price' ) {
                                                    
                                                        $felmat_site[ $count ][ $key ] = $this->monthlySearchService->calc_approval_price( 
                                                                                        trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) )
                                                                                    ,6);
                                                    } 
                                                    else {
                                                        
                                                        $felmat_site[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                                    }
                                                }else{ throw new \Exception($value.'要素が存在しません。'); }
                                            }
                                            $felmat_site[ $count ][ 'approval_price' ] = $felmat_site[ $count ][ 'approval' ] * $product_info->price;

                                            $count++;
                                            
                                        }
                                    }
                                    if ( $y == 1 ) {
                                        $this->monthlySearchService->save_site( json_encode( $felmat_site ) );
                                    }
                                }
                                
                                
                            }

                            // echo "<pre>";
                            // var_dump($felmat_data);
                            // var_dump($felmat_site);
                            // echo "</pre>";
                            /*
                            サイトデータ・日次データ保存
                            */
                            $this->monthlySearchService->save_monthly( json_encode( $felmat_data ) );
                        
                        }
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'フェルマ',
                                'type' => 'Monthly',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                }        
            } );
        }
    }
}