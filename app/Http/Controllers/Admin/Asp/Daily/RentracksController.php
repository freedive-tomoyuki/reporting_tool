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

class RentracksController extends DailyCrawlerController
{
    
    public function rentracks( $product_base_id ) //OK
    {
        
        /*
        ChromeDriverのオプション設定
        */
        Browser::macro( 'crawler', function( )
        {
                return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
        } );
        
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
        $product_id = $this->dailySearchService->BasetoProduct( 5, $product_base_id );
        
        /*
        Chromeドライバーのインスタンス呼び出し
        */
        $client = new Client( new Chrome( $options ) );
        
        /*
        Chromeドライバー実行
        　引数
        　　$product_id:案件ID
        */
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
                    /*
                    日付　取得
                    */
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                         $s_Y = date( 'Y', strtotime( 'first day of previous month' ) );
                         $s_M = date( 'n', strtotime( 'first day of previous month' ) );
                         $s_D = 1;
                         $e_Y = date( 'Y', strtotime( 'last day of previous month' ) );
                         $e_M = date( 'n', strtotime( 'last day of previous month' ) );
                         $e_D = date( 'j', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                         $s_Y = date( 'Y', strtotime( '-1 day' ) );
                         $s_M = date( 'n', strtotime( '-1 day' ) );
                         $s_D = 1;
                         $e_Y = date( 'Y', strtotime( '-1 day' ) );
                         $e_M = date( 'n', strtotime( '-1 day' ) );
                         $e_D = date( 'j', strtotime( '-1 day' ) );
                    }
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        /*
                        クロール：ログイン＝＞パートナー分析より検索
                        */
                        
                        $crawler = $browser->visit( $product_info->asp->login_url )->type( $product_info->asp->login_key, $product_info->login_value )->type( $product_info->asp->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( $product_info->asp->lp1_url )->select( '#idDropdownlist1', $product_info->asp_product_id )->select( '#idGogoYear', $s_Y )->select( '#idGogoMonth', $s_M )->select( '#idGogoDay', $s_D )->select( '#idDoneYear', $e_Y )->select( '#idDoneMonth', $e_M )->select( '#idDoneDay', $e_D )->click( '#idButton1' )->crawler();
                        //echo $crawler->html();
                        /*
                        クロール：
                        */
                        
                        $crawler2 = $browser->visit( $product_info->asp->lp2_url )->crawler();
                        
                        /*
                        クロール：
                        */
                        
                        $crawler3 = $browser->visit( $product_info->asp->lp3_url )->crawler();
                        
                        /*
                        selector 設定
                        */
                        $selector1 = array(
                            'imp' => $product_info->asp->daily_imp_selector,
                            'click' => $product_info->asp->daily_click_selector,
                            'cv' => $product_info->asp->daily_cv_selector 
                            
                        );
                        $selector2 = array(
                             'partnership' => $product_info->asp->daily_partnership_selector 
                        );
                        $selector3 = array(
                             'active' => $product_info->asp->daily_active_selector 
                        );
                        
                        /*
                        $crawler　をフィルタリング
                        */
                        $rentracks_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                        {
                            
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            
                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            foreach ( $selector1 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector1 as $key => $value
                            
                            return $data;
                            
                        } );
                        /*
                        $crawler2　をフィルタリング
                        */
                        $rentracks_data2 = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                        {
                            
                            $data = array( );
                            
                            foreach ( $selector2 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector2 as $key => $value
                            
                            return $data;
                            
                        } );
                        /*
                        $crawler3　をフィルタリング
                        */
                        $rentracks_data3 = $crawler3->each( function( Crawler $node ) use ($selector3, $product_info)
                        {
                            
                            $data = array( );
                            
                            foreach ( $selector3 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector3 as $key => $value
                            
                            return $data;
                            
                        } );
                        //var_dump($rentracks_data3);
                        /*
                        サイト抽出　
                        */

                        $crawler_for_site = $browser->visit( "https://manage.rentracks.jp/sponsor/detail_partner" )->select( '#idDropdownlist1', $product_info->asp_product_id )->select( '#idGogoYear', $s_Y )->select( '#idGogoMonth', $s_M )->select( '#idGogoDay', $s_D )->select( '#idDoneYear', $e_Y )->select( '#idDoneMonth', $e_M )->select( '#idDoneDay', $e_D )->select( '#idPageSize', '300' )->click( '#idButton1' )->crawler();
                        
                        //var_dump( $crawler_for_site->html() );
                        //アクティブ件数を取得
                        if(count($crawler_for_site->filter( '#main > div.hitbox > em' ))){
                            $active_partner = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( '#main > div.hitbox > em' )->text() ) );
                        }else{
                            throw new \Exception('#main > div.hitbox > em要素が存在しません。');
                        }
                        //echo $active_partner;
                        
                        for ( $i = 1; $active_partner >= $i; $i++ ) {
                            $rentracks_site[ $i ][ 'product' ] = $product_info->id;
                            $rentracks_site[ $i ][ 'asp' ]     = $product_info->asp_id;
                            $iPlus = $i + 1;
                            //echo 'iPlus' . $iPlus;
                            
                            $selector_for_site = array(
                                'media_id' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c03',
                                'site_name' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c04 > a',
                                'imp' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c05',
                                'click' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c06',
                                'cv' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c10',
                                //'price' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c15' 
                            );
                            
                            foreach ( $selector_for_site as $key => $value ) {
                                if(count($crawler_for_site->filter( $value ))){

                                    if ( $key == 'site_name' ) {
                                        
                                        $rentracks_site[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                        
                                    } //$key == 'site_name'
                                    else {
                                        
                                        $rentracks_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                    }
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                                
                            }
                            $unit_price = $product_info->price;
                            $rentracks_site[ $i ][ 'price' ] = $unit_price * $rentracks_site[ $i ][ 'cv' ];

                            $calculated                = json_decode( 
                                                        json_encode( 
                                                            json_decode( 
                                                                $this->dailySearchService
                                                                    ->cpa( $rentracks_site[ $i ][ 'cv' ], $rentracks_site[ $i ][ 'price' ], 5 ) 
                                                            ) 
                                                        ), True );
                            $rentracks_site[ $i ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                            $rentracks_site[ $i ][ 'cost' ] = $calculated[ 'cost' ];
                            $rentracks_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                        }
                        
                        //$rentracks_data[ 0 ][ 'price' ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( '#main > table > tbody > tr.total > td:nth-child(15)' )->text() ) );
                        $unit_price = $product_info->price;
                        $rentracks_data[ 0 ][ 'price' ] = $rentracks_data[ 0 ][ 'cv' ] * $unit_price;

                        $rentracks_data[ 0 ][ 'partnership' ] = $rentracks_data2[ 0 ][ 'partnership' ];
                        $rentracks_data[ 0 ][ 'active' ]      = $rentracks_data3[ 0 ][ 'active' ];
                        
                        $calculated               = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $rentracks_data[ 0 ][ 'cv' ], $rentracks_data[ 0 ][ 'price' ], 5 ) ) ), True );
                        $rentracks_data[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        $rentracks_data[ 0 ][ 'cost' ] = $calculated[ 'cost' ];
                        

                        
                        //echo "<pre>";
                        //var_dump( $rentracks_data );
                        //var_dump( $rentracks_site );
                        //echo "</pre>";
                        /*
                        サイトデータ・日次データ保存
                        */
                        $this->dailySearchService->save_site( json_encode( $rentracks_site ) );
                        $this->dailySearchService->save_daily( json_encode( $rentracks_data ) );
                        
                        //var_dump($crawler_for_site);
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }
        } );
        
    } //rentracks
}