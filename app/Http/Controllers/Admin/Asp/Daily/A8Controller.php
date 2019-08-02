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

class A8Controller extends DailyCrawlerController
{
    
    public function a8( $product_base_id ) //OK
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
        $product_id = $this->BasetoProduct( 1, $product_base_id );
        
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            
            $product_infos = \App\Product::all()->where( 'id', $product_id );
            
            //クロール実行が1日のとき
            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
               echo $s_Y = date( 'Y', strtotime( '-1 day' ) );
               echo  $s_M = date( 'n', strtotime( '-1 day' ) );
            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
            else {
               echo  $s_Y = date( 'Y' );
               echo  $s_M = date( 'n' );
            }
            foreach ( $product_infos as $product_info ) {
                
                $crawler_1 = $browser->visit( $product_info->asp->login_url )->type( $product_info->login_key, $product_info->login_value )->type( $product_info->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( $product_info->asp->lp1_url . $product_info->asp_product_id )->crawler();
                
                $crawler_2 = $browser->visit( $product_info->asp->lp2_url )->select( '#reportOutAction > table > tbody > tr:nth-child(2) > td > select', '23' )->radio( 'insId', $product_info->asp_product_id )->click( '#reportOutAction > input[type="image"]:nth-child(3)' )->crawler();
                
                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                    $selector_1 = array(
                         'active' => '#element > tbody > tr:nth-child(2) > td:nth-child(3)',
                        #element > tbody > tr:nth-child(1) > td:nth-child(3)
                        'partnership' => '#element > tbody > tr:nth-child(2) > td:nth-child(2)' 
                    );
                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                else {
                    $selector_1 = array(
                         'active' => $product_info->asp->daily_active_selector,
                        'partnership' => $product_info->asp->daily_partnership_selector 
                    );
                }
                //
                $selector_2 = array(
                    'imp' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(2)',
                    'click' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(3)',
                    'price' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(12)',
                    'cv' => $product_info->asp->daily_cv_selector 
                );
                
                $a8data_1 = $crawler_1->each( function( Crawler $node ) use ($selector_1, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    
                    foreach ( $selector_1 as $key => $value ) {
                        $data[ $key ] = trim( $node->filter( $value )->text() );
                    } //$selector_1 as $key => $value
                    return $data;
                } );
                //var_dump( $a8data_1 );
                $a8data_2 = $crawler_2->each( function( Crawler $node ) use ($selector_2)
                {
                    
                    foreach ( $selector_2 as $key => $value ) {
                        $data[ $key ] = trim( $node->filter( $value )->text() );
                    } //$selector_2 as $key => $value
                    return $data;
                } );
                //var_dump( $a8data_2 );
                
                $a8data_1[ 0 ][ 'cv' ]    = trim( preg_replace( '/[^0-9]/', '', $a8data_2[ 0 ][ "cv" ] ) );
                $a8data_1[ 0 ][ 'click' ] = trim( preg_replace( '/[^0-9]/', '', $a8data_2[ 0 ][ "click" ] ) );
                $a8data_1[ 0 ][ 'imp' ]   = trim( preg_replace( '/[^0-9]/', '', $a8data_2[ 0 ][ "imp" ] ) );
                $a8data_1[ 0 ][ 'price' ] = trim( preg_replace( '/[^0-9]/', '', $a8data_2[ 0 ][ "price" ] ) );
                
                $calData = json_decode( json_encode( json_decode( $this->cpa( $a8data_1[ 0 ][ 'cv' ], $a8data_1[ 0 ][ 'price' ], 1 ) ) ), True );
                
                $a8data_1[ 0 ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                $a8data_1[ 0 ][ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                $a8data_1[ 0 ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                
                $crawler_for_site = $browser->visit('https://adv.a8.net/a8v2/ecAsRankingReportAction.do?reportType=11&insId=' . $product_info->asp_product_id . '&asmstId=&termType=1&d-2037996-p=1&multiSelectFlg=0&year=' . $s_Y . '&month=' . $s_M )->crawler();
                $count_selector   = '#contents1clm > form:nth-child(6) > span.pagebanner';
                echo $count_data       = intval( trim( preg_replace( '/[^0-9]/', '', substr( $crawler_for_site->filter( $count_selector )->text(), 0, 7 ) ) ) );
                
                //echo 'count_data＞'.$count_data;
                echo $page_count = ceil( $count_data / 500 );
                //echo 'page_count' . $page_count;
                
                for ( $page = 0; $page < $page_count; $page++ ) {
                    
                    $target_page = $page + 1;
                    
                    $url = 'https://adv.a8.net/a8v2/ecAsRankingReportAction.do?reportType=11&insId=' . $product_info->asp_product_id . '&asmstId=&termType=1&d-2037996-p=' . $target_page . '&multiSelectFlg=0&year=' . $s_Y . '&month=' . $s_M;
                    
                    //echo $url;
                    
                    $crawler_for_site = $browser->visit( $url )->crawler();
                    
                    $count_deff = intval( $count_data ) - ( 500 * $page );
                    
                    $count_deff = ( intval( $count_deff ) > 500 ) ? 500 : intval( $count_deff );
                    
                    //echo "サイト数＞" . $count_data;
                    //echo $page . "ページのサイト数＞" . $count_deff;
                    
                    for ( $i = 1; $i <= $count_deff; $i++ ) {
                        
                        $count = $i + ( 500 * $page );
                        
                        $selector_for_site = array(
                             'media_id' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                            'site_name' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                            'imp' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(5)',
                            'click' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(6)',
                            'cv' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(10)',
                            'price' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(13)' 
                        );
                        
                        foreach ( $selector_for_site as $key => $value ) {
                            $data[ $count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                        } //$selector_for_site as $key => $value
                        
                        $calData = json_decode( json_encode( json_decode( $this->cpa( $data[ $count ][ 'cv' ], $data[ $count ][ 'price' ], 1 ) ) ), True );
                        
                        //$data[$count]['product'] = $product_info->id;
                        $data[ $count ][ 'product' ] = $product_info->id;
                        $data[ $count ][ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                        
                        $data[ $count ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                        $data[ $count ][ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                        
                        //echo '<pre>';
                        //echo $i;
                        //var_dump( $data );
                        //echo '</pre>';
                    } //$i = 1; $i <= $count_deff; $i++
                } //$page = 0; $page < $page_count; $page++
                //var_dump( $data );
                //var_dump( $a8data_1 );
                /**
                １サイトずつサイト情報の登録を実行
                */
                $this->save_site( json_encode( $data ) );
                $this->save_daily( json_encode( $a8data_1 ) );
                
                
            } //$product_infos as $product_info
        } );
        
    }
}