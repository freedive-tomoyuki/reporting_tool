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
        $product_id = $this->BasetoProduct( 5, $product_base_id );
        
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
                $rtdata = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    
                    $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                    
                    foreach ( $selector1 as $key => $value ) {
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector1 as $key => $value
                    
                    return $data;
                    
                } );
                /*
                $crawler2　をフィルタリング
                */
                $rtdata2 = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                {
                    
                    $data = array( );
                    
                    foreach ( $selector2 as $key => $value ) {
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector2 as $key => $value
                    
                    return $data;
                    
                } );
                /*
                $crawler3　をフィルタリング
                */
                $rtdata3 = $crawler3->each( function( Crawler $node ) use ($selector3, $product_info)
                {
                    
                    $data = array( );
                    
                    foreach ( $selector3 as $key => $value ) {
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector3 as $key => $value
                    
                    return $data;
                    
                } );
                //var_dump($rtdata3);
                /*
                サイト抽出　
                */

                $crawler_for_site = $browser->visit( "https://manage.rentracks.jp/sponsor/detail_partner" )->select( '#idDropdownlist1', $product_info->asp_product_id )->select( '#idGogoYear', $s_Y )->select( '#idGogoMonth', $s_M )->select( '#idGogoDay', $s_D )->select( '#idDoneYear', $e_Y )->select( '#idDoneMonth', $e_M )->select( '#idDoneDay', $e_D )->select( '#idPageSize', '300' )->click( '#idButton1' )->crawler();
                
                var_dump( $crawler_for_site->html() );
                //アクティブ件数を取得
                $active_partner = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( '#main > div.hitbox > em' )->text() ) );
                //echo $active_partner;
                
                for ( $i = 1; $active_partner >= $i; $i++ ) {
                    $rtsite[ $i ][ 'product' ] = $product_info->id;
                    
                    $iPlus = $i + 1;
                    //echo 'iPlus' . $iPlus;
                    
                    $selector_for_site = array(
                         'media_id' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c03',
                        'site_name' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c04 > a',
                        'imp' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c05',
                        'click' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c06',
                        'cv' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c10',
                        'price' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c15' 
                    );
                    
                    foreach ( $selector_for_site as $key => $value ) {
                        if ( $key == 'site_name' ) {
                            
                            $rtsite[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                            
                        } //$key == 'site_name'
                        else {
                            
                            $rtsite[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                        }
                        
                    } //$selector_for_site as $key => $value
                    $calData                = json_decode( json_encode( json_decode( $this->cpa( $rtsite[ $i ][ 'cv' ], $rtsite[ $i ][ 'price' ], 5 ) ) ), True );
                    $rtsite[ $i ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                    $rtsite[ $i ][ 'cost' ] = $calData[ 'cost' ];
                    $rtsite[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                } //$i = 1; $active_partner >= $i; $i++
                
                
                $rtdata[ 0 ][ 'price' ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( '#main > table > tbody > tr.total > td:nth-child(15)' )->text() ) );
                
                $rtdata[ 0 ][ 'partnership' ] = $rtdata2[ 0 ][ 'partnership' ];
                $rtdata[ 0 ][ 'active' ]      = $rtdata3[ 0 ][ 'active' ];
                
                $calData               = json_decode( json_encode( json_decode( $this->cpa( $rtdata[ 0 ][ 'cv' ], $rtdata[ 0 ][ 'price' ], 5 ) ) ), True );
                $rtdata[ 0 ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                $rtdata[ 0 ][ 'cost' ] = $calData[ 'cost' ];
                
                
                
                //echo "<pre>";
                //var_dump( $rtdata );
                //var_dump( $rtsite );
                //echo "</pre>";
                /*
                サイトデータ・日次データ保存
                */
                $this->save_site( json_encode( $rtsite ) );
                $this->save_daily( json_encode( $rtdata ) );
                
                //var_dump($crawler_for_site);
            } //$product_infos as $product_info
            
        } );
        
    } //rentracks
}