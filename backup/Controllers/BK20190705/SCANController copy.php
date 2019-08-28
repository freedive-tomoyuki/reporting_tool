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

class SCANController extends MonthlyCrawlerController
{
    
    public function scan( $product_base_id ) //OK
    {
        /**
        
        ブラウザ立ち上げ
        
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
        
        $product_id = $this->BasetoProduct( 9, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            
            $product_infos = \App\Product::all()->where( 'id', $product_id );
            
            foreach ( $product_infos as $product_info ) {
                
                /**
                
                実装：ログイン
                
                */
                //$crawler = $browser->visit("https://www.nursejinzaibank.com/glp")->crawler();
                $crawler = $browser->visit( $product_info->asp->login_url )
                ->type( $product_info->asp->login_key, $product_info->login_value )
                ->type( $product_info->asp->password_key, $product_info->password_value )
                ->click( $product_info->asp->login_selector )
                ->visit( "https://www.scadnet.com/merchant/report/monthly.php?s=" . $product_info->asp_sponsor_id . "&c_id=" . $product_info->asp_product_id )
                ->crawler();
                var_dump( $crawler );
                /**
                先月・今月のセレクタ
                */
                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                    $row_this   = 4;
                    $row_before = 5;
                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                else {
                    $row_this   = 3;
                    $row_before = 4;
                }
                $selector_this   = array(
                     'approval' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $row_this . ') > td:nth-child(13)',
                    'approval_price' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $row_this . ') > td:nth-child(14)' 
                );
                $selector_before = array(
                     'approval' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $row_before . ') > td:nth-child(13)',
                    'approval_price' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $row_before . ') > td:nth-child(14)' 
                );
                /**
                セレクターからフィルタリング
                */
                $scan_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    
                    foreach ( $selector_this as $key => $value ) {
                        $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                        $data[ $key ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector_this as $key => $value
                    foreach ( $selector_before as $key => $value ) {
                        $data[ 'last_date' ]    = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                        $data[ 'last_' . $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector_before as $key => $value
                    return $data;
                } );
                
                //var_dump( $afbdata);
                
                /**
                サイト取得用クロール
                */
                
                $count_site = 0;
                
                
                for ( $x = 0; $x < 2; $x++ ) {
                    
                    if ( $x == 0 ) { //今月
                        
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $s_Y = date( 'Y', strtotime( '-1 month' ) );
                            $s_M = date( 'n', strtotime( '-1 month' ) );
                            $s_D = 1;
                            $e_Y = date( 'Y', strtotime( '-1 month' ) );
                            $e_M = date( 'n', strtotime( '-1 month' ) );
                            $e_D = date( 'd', strtotime( '-1 month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $s_Y = date( 'Y', strtotime( '-1 day' ) );
                            $s_M = date( 'n', strtotime( '-1 day' ) );
                            $s_D = 1;
                            $e_Y = date( 'Y', strtotime( '-1 day' ) );
                            $e_M = date( 'n', strtotime( '-1 day' ) );
                            $e_D = date( 'd', strtotime( '-1 day' ) );
                        }
                    } //$x == 0
                    else { //先月
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $s_Y = date( 'Y', strtotime( '-2 month' ) );
                            $s_M = date( 'n', strtotime( '-2 month' ) );
                            $s_D = '01';
                            $e_Y = date( 'Y', strtotime( '-2 month' ) );
                            $e_M = date( 'n', strtotime( '-2 month' ) );
                            $e_D = date( 't', strtotime( '-2 month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $s_Y = date( 'Y', strtotime( '-1 month' ) );
                            $s_M = date( 'n', strtotime( '-1 month' ) );
                            $s_D = '01';
                            $e_Y = date( 'Y', strtotime( '-1 month' ) );
                            $e_M = date( 'n', strtotime( '-1 month' ) );
                            $e_D = date( 't', strtotime( '-1 month' ) );
                        }
                    }
                    
                    $crawler_for_site = $browser->visit( 'https://www.scadnet.com/merchant/report/site.php?s=' . $product_info->asp_sponsor_id . '&s_yy=' . $s_Y . '&s_mm=' . $s_M . '&s_dd=' . $s_D . '&e_yy=' . $e_Y . '&e_mm=' . $e_M . '&e_dd=' . $e_D )->crawler();
                    
                    //サイト一覧　１ページ分のクロール
                    $i = 3;
                    
                    while ( $crawler_for_site->filter( '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->count() > 0 ) {
                        //echo $i ."<br>";
                        $scan_site[ $count_site ][ 'product' ] = $product_info->id;
                        
                        if ( $x == 0 ) {
                            $scan_site[ $count_site ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                        } //$x == 0
                        else {
                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $scan_site[ $count_site ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            else {
                                $scan_site[ $count_site ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                        }
                        
                        $selector_for_site = array(
                            #reportTable > tbody > tr:nth-child(6) > td.maxw150
                             'media_id' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)',
                            'site_name' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                            'approval' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(12)',
                            'approval_price' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(13)' 
                        );
                        //  サイト一覧　１行ずつクロール
                        foreach ( $selector_for_site as $key => $value ) {
                            if ( $key == 'site_name' || $key == 'media_id' ) {
                                $scan_site[ $count_site ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                            } //$key == 'site_name' || $key == 'media_id'
                            else {
                                $scan_site[ $count_site ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                            }
                        } // endforeach 
                        $count_site++;
                        $i++;
                    } // endfor
                } //$x = 0; $x < 2; $x++
                
                var_dump( $scan_data );
                var_dump( $scan_site );
                
                $this->save_monthly( json_encode( $scan_data ) );
                $this->save_site( json_encode( $scan_site ) );
                
            } //$product_infos as $product_info
        } );
    }
    
    
}