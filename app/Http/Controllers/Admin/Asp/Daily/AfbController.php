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


class AfbController extends DailyCrawlerController
{
    
    public function afb( $product_base_id ) //OK
    {
        /*
        ChromeDriverのオプション設定
        */
        
        Browser::macro('crawler', function () {
          //return new Crawler( $this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '' );
            return new Crawler( $this->driver->getPageSource() ?? '' );
        });
        
        $options = [
                '--window-size=1920,3000',
                '--start-maximized',
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--lang=ja_JP',

        ];

        
    
        //案件の大本IDからASP別のプロダクトIDを取得
        $product_id = $this->dailySearchService->BasetoProduct( 4, $product_base_id );
        
        // Chromeドライバーのインスタンス呼び出し
        //$client = new Client( new Chrome( $options ) );
        $client = new Client( new Chrome( $options ) );

        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use($product_id)
        {
            echo '<pre>';
            //var_dump($browser->visit( 'https://www.afi-b.com' )->crawler()->html());
            echo '</pre>';

            $product_infos = \App\Product::all()->where( 'id', $product_id );
            
            //クロール実行が1日のとき
            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                $s_date = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                $e_date = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
            }
            else {
                $s_date = date( 'Y/m/01', strtotime( '-1 day' ) );
                $e_date = date( 'Y/m/d', strtotime( '-1 day' ) );
            }
            
            foreach ( $product_infos as $product_info ) {
                
                //クロール：ログイン→レポートより検索
                //$crawler = new Crawler();
                //$index = 0;
                //$crawler = new Crawler();
                $crawler = $browser->visit( 'https://www.afi-b.com' )
                    ->type( $product_info->asp->login_key, $product_info->login_value )
                    ->type( $product_info->asp->password_key, $product_info->password_value )
                    ->click( $product_info->asp->login_selector )
                    //->type( '#pageTitle > aside.m-grid__itemOrder--03.m-gheader__loginForm > g-header-loginform > div.m-form__wrap > form > div > div:nth-child(1) > input', 'broadwimax' )
                    //->type( '#pageTitle > aside.m-grid__itemOrder--03.m-gheader__loginForm > g-header-loginform > div.m-form__wrap > form > div > div:nth-child(2) > input', '0hS6gmTN5RHGYn1MSHhf')
                    //->click( '#pageTitle > aside.m-grid__itemOrder--03.m-gheader__loginForm > g-header-loginform > div.m-form__wrap > form > div > div.m-gLoginGlid__btn > m-btn > div > input' )
                    
                    ->visit( 'https://client.afi-b.com/client/b/cl/report/?r=daily' )
                    ->click('#adv_id_daily_chzn > a')
                    ->click('#adv_id_daily_chzn_o_1')
                    ->type( '#report_form_2 > div > table > tbody > tr > td > ul > li > #form_start_date', $s_date ) 
                    ->type( '#report_form_2 > div > table > tbody > tr > td > ul > li > #form_end_date', $e_date )
                    ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(1)')
                    ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(2)')
                    ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(3)')
                    ->click('#report_form_2 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')->crawler();
                    //var_dump();
                    //->screenshot(date('Ymd_His_') . '_' . str_pad(++ $index, 3, 0, STR_PAD_LEFT));
                    //header('Content-type: text/html; charset=utf-8');
                    //echo '<pre>';
                    //var_dump($crawler);
                    //$crawler->dump();
                    //echo $crawler->html();
                    //var_dump($crawler->dump());//->crawler()->html();
                    //echo '</pre>';

                    //var_dump($crawler1->crawler()->html());
                    //echo $crawler->html();

                $crawler2 = $browser->visit( 'https://client.afi-b.com/client/b/cl/main' )->crawler();
                
                $crawler3 = 
                    $browser
                    ->visit( 'https://client.afi-b.com/client/b/cl/report/?r=site#report_view' )
                    //->click( '#site_tab_bth' )
                    //->type( '#report_form_4 > div > table > tbody > tr:nth-child(4) > td > ul > li:nth-child(1) > #form_start_date', $s_date ) 
                    //->type( '#report_form_4 > div > table > tbody > tr:nth-child(4) > td > ul > li:nth-child(3) > #form_end_date', $e_date )
                    ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(1)' )
                    ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(2)' )
                    ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(3)' )
                    //->click( '#report_form_4 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input' )
                    ->click('#report_form_4 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')
                    //->press('input[name=]')
                    ->crawler();
                    //echo $crawler3->html();


                $selector_crawler  = array(
                    'imp' => '#reportTable > tfoot > tr > td:nth-child(3) > p',
                    'click' => '#reportTable > tfoot > tr > td:nth-child(4) > p',
                    'cv' => '#reportTable > tfoot > tr > td:nth-child(7) > p',
                    'price' => '#reportTable > tfoot > tr > td:nth-child(10) > p' 
                );
                $selector_crawler2 = array(
                     'partnership' => '#main > div.wrap > div.section33 > div.section_inner.positionr.positionr > table > tbody > tr:nth-child(13) > td:nth-child(2)' 
                );
                
                $selector_crawler3 = array(
                     'active' => $product_info->asp->daily_active_selector 
                );
                
                $afbdata = $crawler->each( function( Crawler $node ) use ($selector_crawler, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    
                    $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                    
                    foreach ( $selector_crawler as $key => $value ) {
                        
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        
                    } //$selector_crawler as $key => $value

                    $calData        = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $data[ 'cv' ], $data[ 'price' ], 4 ) ) ), True );
                    $data[ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                    $data[ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                    return $data;
                    
                } );
                $partnership = $crawler2->each( function( Crawler $node ) use ($selector_crawler2)
                {
                    foreach ( $selector_crawler2 as $key => $value ) {
                        $partnership = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector_crawler2 as $key => $value
                    return $partnership;
                    //var_dump($data);
                } );
                $active = $crawler3->each( function( Crawler $node ) use ($selector_crawler3)
                {
                    foreach ( $selector_crawler3 as $key => $value ) {
                        $active = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector_crawler3 as $key => $value
                    return $active;
                } );
                
                $count_data = $active[ 0 ];
                $afbsite    = array( );
                //echo $count_data;
                
                for ( $i = 1; $count_data >= $i; $i++ ) {
                    $afbsite[ $i ][ 'product' ] = $product_info->id;
                    
                    $selector_for_site = array(
                        'media_id' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td.maxw150',
                        'site_name' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td.maxw150 > p > a',
                        'imp' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(5) > p',
                        'click' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(6) > p',
                        'cv' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(9) > p',
                        'ctr' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(7) > p',
                        'cvr' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(10) > p',
                        'price' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(12) > p' 
                    );
                    
                    foreach ( $selector_for_site as $key => $value ) {
                        
                        if ( $key == 'media_id' ) {
                            //$data = trim($node->filter($value)->attr('title'));
                            $media_id = array( );
                            $sid      = trim( $crawler3->filter( $value )->attr( 'title' ) );
                            preg_match( '/SID：(\d+)/', $sid, $media_id );
                            
                            $afbsite[ $i ][ $key ] = $media_id[ 1 ];
                            
                        } //$key == 'media_id'
                        elseif ( $key == 'site_name' ) {
                            
                            $afbsite[ $i ][ $key ] = trim( $crawler3->filter( $value )->text() );
                            
                        } //$key == 'site_name'
                        else {
                            
                            $afbsite[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler3->filter( $value )->text() ) );
                            
                        }
                        
                    } //$selector_for_site as $key => $value
                    $calData                 = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $afbsite[ $i ][ 'cv' ], $afbsite[ $i ][ 'price' ], 4 ) ) ), True );
                    $afbsite[ $i ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                    $afbsite[ $i ][ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                    
                    $afbsite[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                } //$i = 1; $count_data >= $i; $i++

                //var_dump($afbsite);

                $afbdata[ 0 ][ 'active' ]      = $active[ 0 ];
                $afbdata[ 0 ][ 'partnership' ] = $partnership[ 0 ];
                
                $this->dailySearchService->save_daily( json_encode( $afbdata ) );
                $this->dailySearchService->save_site( json_encode( $afbsite ) );
                
            }//$product_infos as $product_info
        } ); 
    }
    
}