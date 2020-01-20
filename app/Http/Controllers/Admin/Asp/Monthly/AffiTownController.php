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
        
        $product_id = $this->monthlySearchService->BasetoProduct( 7, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
                    /*
                    日付　取得
                    */
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                        $start  = date( "Ym", strtotime( "-2 month" ) );
                        $end    = date( "Ym", strtotime( "-1 month" ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                        $start  = date( "Ym", strtotime( "-1 month" ) );
                        $end    = date( "Ym" );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        
                        
                        $crawler = $browser->visit( $product_info->asp->login_url )
                        ->type( $product_info->asp->login_key, $product_info->login_value )
                        ->type( $product_info->asp->password_key, $product_info->password_value )
                        ->click( $product_info->asp->login_selector )
                        ->visit( "https://affi.town/adserver/report/mc/monthly.af?advertiseId=" . $product_info->asp_product_id . "&fromDate=" . $start . "&toDate=" . $end )
                        ->crawler();
                        //echo $crawler->html();
                        
                        //先月・今月のセレクタ
                        $selector_this   = array(
                            'approval' => '#all_display > table > tbody > tr:nth-child(2) > td:nth-child(5)'
                        );
                        $selector_before = array(
                            'approval' => '#all_display > table > tbody > tr:nth-child(1) > td:nth-child(5)'
                        );
                        //Selectorから承認件数・承認金額を取得
                        //先月と今月分
                        $affitown_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                        {
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;

                            $unit_price = $product_info->price;

                            
                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );

                            if(count($node->filter( $selector_this['approval'] ))){
                                $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval'] )->text() ) );
                            }else{ throw new \Exception($selector_this['approval'].'要素が存在しません。'); }
                    
                            $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            }
                            else {
                                $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }

                            if(count($node->filter( $selector_before['approval'] ))){
                                $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval'] )->text() ) );
                            }else{ throw new \Exception($selector_before['approval'].'要素が存在しません。'); }

                            $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;
                                                        
                            return $data;
                            
                        } );
                        
                        // var_dump( $affitown_data );
                        
                        
                        // $x = 0：今月
                        // $x = 1：先月
                        
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
                                }
                                else {
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $affitown_site[ $active_count ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                    }
                                    else {
                                        $affitown_site[ $active_count ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                    }
                                }
                                
                                $selector_for_site = array(
                                    
                                    'media_id' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(1)',
                                    'site_name' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                                    'approval' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(7)',
                                    // 'approval_price' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(8) > p' 
                                );
                                
                                foreach ( $selector_for_site as $key => $value ) {
                                    if(count($crawler_for_site->filter( $value ))){
                                        if ( $key == 'site_name' ) {
                                            
                                            $affitown_site[ $active_count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                            
                                        } 
                                        else {
                                            
                                            $affitown_site[ $active_count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                        
                                        }
                                    }else{
                                        throw new \Exception($value.'要素が存在しません。');
                                    }
                                } //$selector_for_site as $key => $value
                                $affitown_site[ $active_count ][ 'approval_price' ] = $affitown_site[ $active_count ][ 'approval' ] * $product_info->price;

                                $i++;
                                $active_count++;
                            } //trim( $crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a' )->text() ) != "合計"
                            
                        } //$x = 0; $x < 2; $x++
                        //var_dump( $affitown_site );
                        $this->monthlySearchService->save_monthly( json_encode( $affitown_data ) );
                        $this->monthlySearchService->save_site( json_encode( $affitown_site ) );
                    } //foreach ($product_infos as $product_info)
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'アフィタウン',
                            'type' => 'Monthly',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }        
        } );
    }
    
}