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


class AffiTownController extends MonthlyCrawlerController
{
    
    public function affitown( $product_base_id ) //OK
    {
        
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
        
        $product_id = $this->BasetoProduct( 7, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            
            $product_infos = \App\Product::all()->where( 'id', $product_id );
            /*
            日付　取得
            */
            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                echo $start = date( "Ym", strtotime( "-2 month" ) );
                echo $end = date( "Ym", strtotime( "-1 month" ) );
            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
            else {
                echo $start = date( "Ym", strtotime( "-1 month" ) );
                echo $end = date( "Ym" );
            }
            
            foreach ( $product_infos as $product_info ) {
                
                
                $crawler = $browser->visit( $product_info->asp->login_url )
                ->type( $product_info->asp->login_key, $product_info->login_value )
                ->type( $product_info->asp->password_key, $product_info->password_value )
                ->click( $product_info->asp->login_selector )
                ->visit( "https://affi.town/adserver/report/mc/monthly.af?advertiseId=" . $product_info->asp_product_id . "&fromDate=" . $start . "&toDate=" . $end )
                ->crawler();
                echo $crawler->html();
                
                /**
                先月・今月のセレクタ
                */
                $selector_this   = array(
                     'approval' => '#all_display > table > tbody > tr.bg_gray > td:nth-child(5) > p',
                    'approval_price' => '#all_display > table > tbody > tr.bg_gray > td:nth-child(6) > p' 
                );
                $selector_before = array(
                     'approval' => '#all_display > table > tbody > tr:nth-child(1) > td:nth-child(5) > p',
                    'approval_price' => '#all_display > table > tbody > tr:nth-child(1) > td:nth-child(6) > p' 
                );
                /**
                Selectorから承認件数・承認金額を取得
                先月と今月分
                */
                $affitown_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    
                    $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                    
                    foreach ( $selector_this as $key => $value ) {
                        
                        if($key == 'approval_price'){
                            $data[ $key ]   =
                                $this->calc_approval_price(
                                    trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ), 7);
                                
                        }else{
                            $data[ $key ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        }

                        $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                    } //$selector_this as $key => $value
                    foreach ( $selector_before as $key => $value ) {
                        
                        if( $key == 'approval_price' ){
                            $data[ 'last_' . $key ] = 
                                $this->calc_approval_price(
                                    trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ),7);

                        }else{
                            $data[ 'last_' . $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        }

                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                        }

                    } //$selector_before as $key => $value
                    
                    return $data;
                    
                } );
                
                var_dump( $affitown_data );
                
                
                /**
                $x = 0：今月
                $x = 1：先月
                */
                $active_count = 0;
                
                for ( $x = 0; $x < 2; $x++ ) {
                    
                    $page = 0;
                    $i    = 1;
                    
                    if ( $x == 0 ) { //今月
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                            $start = date( 'Ymd', strtotime( 'first day of previous month' ) );
                            $end   = date( 'Ymd', strtotime( 'last day of previous month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $start = date( 'Ym01' );
                            $end   = date( 'Ymd', strtotime( '-1 day' ) );
                        }
                    } //$x == 0
                    else { //先月
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                            $start = date( 'Ymd', strtotime( 'first day of previous month' ) );
                            $end   = date( 'Ymd', strtotime( 'last day of previous month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $start = date( 'Ym01', strtotime( '-2 month' ) );
                            $end   = date( 'Ymt', strtotime( '-2 month' ) );
                        }
                    }
                    
                    $crawler_for_site = $browser->visit( "https://affi.town/adserver/report/mc/site.af?advertiseId=" . $product_info->asp_product_id . "&fromDate=" . $start . "&toDate=" . $end )->crawler();
                    
                    
                    while ( trim( $crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a' )->text() ) != "合計" ) {
                        
                        $affitown_site[ $active_count ][ 'product' ] = $product_info->id;
                        if ( $x == 0 ) {
                            $affitown_site[ $active_count ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                        } //$x == 0
                        else {
                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $affitown_site[ $active_count ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            else {
                                $affitown_site[ $active_count ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                        }
                        
                        $selector_for_site = array(
                            
                             'media_id' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td.underline',
                            'site_name' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                            'approval' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(7) > p',
                            'approval_price' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(8) > p' 
                        );
                        
                        foreach ( $selector_for_site as $key => $value ) {
                            if ( $key == 'site_name' ) {
                                
                                $affitown_site[ $active_count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                
                            } //$key == 'site_name'
                            elseif($key == 'approval_price'){
                                $affitown_site[ $active_count ][ $key ] = 
                                        $this->calc_approval_price(
                                            trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) ),7 );
                            }
                            else {
                                
                                $affitown_site[ $active_count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                            }
                            
                        } //$selector_for_site as $key => $value
                        
                        $i++;
                        $active_count++;
                    } //trim( $crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a' )->text() ) != "合計"
                    
                } //$x = 0; $x < 2; $x++
                //var_dump( $affitown_site );
                $this->save_monthly( json_encode( $affitown_data ) );
                $this->save_site( json_encode( $affitown_site ) );
            } //foreach ($product_infos as $product_info)
            
        } );
    }
    
}