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

class AccesstradeController extends MonthlyCrawlerController
{
    
    public function accesstrade( $product_base_id ) //OK
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
        
        $product_id = $this->monthlySearchService->BasetoProduct( 2, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
                    
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        $crawler = $browser
                        ->visit( $product_info->asp->login_url )
                        ->type( $product_info->asp->login_key, $product_info->login_value )
                        ->type( $product_info->asp->password_key, $product_info->password_value )
                        ->click( $product_info->asp->login_selector ) 
                        //    承認ベース用のページに変更
                        ->visit( 'https://merchant.accesstrade.net/matv3/program/report/monthly/approved.html?programId=' . $product_info->asp_product_id )->crawler();
                        
                        // 今月用のデータ取得selector
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $row_this   = 2;
                            $row_before = 3;
                        }else {
                            $row_this   = 1;
                            $row_before = 2;
                        }
                        $selector_this   = array(
                            'approval' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(' . $row_this . ') > td:nth-child(4)',
                            'approval_price' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(' . $row_this . ') > td:nth-child(7)'
                        );
                        $selector_before = array(
                            'approval' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(' . $row_before . ') > td:nth-child(4)',
                            'approval_price' => 'body > report-page > div > div > main > ng-component > section > div > div > div > display > div > table > tbody > tr:nth-child(' . $row_before . ') > td:nth-child(7)'
                        );
                        
                        //var_dump( $crawler );
                        //$crawler->each(function (Crawler $node) use ( $selector ){
                        //今月用のデータ取得selector
                        $accesstrade_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                        {
                            
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            // $unit_price = $product_info->price;

                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );

                            // $value = $selector_this[ 'approval' ];
                            
                            if(count($node->filter( $selector_this['approval'] ))){
                                $data[ 'approval' ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval'] )->text() ) );
                            }else{ throw new \Exception( $selector_this[ 'approval' ].'要素が存在しません。'); }
                            
                            if(count($node->filter( $selector_this['approval_price'] ))){
                                $data[ 'approval_price' ]   = $this->monthlySearchService->calc_approval_price(
                                                                    trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval_price'] )->text() ) ) ,2
                                                                );
                            }else{ throw new \Exception( $selector_this[ 'approval_price' ].'要素が存在しません。'); }

                            // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            }
                            else {
                                $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                            // $selector = $selector_before['approval'];

                            if(count($node->filter( $selector_before['approval'] ))){
                                $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval'] )->text() ) );
                            }else{ throw new \Exception( $selector_before['approval'].'要素が存在しません。'); }

                            if(count($node->filter( $selector_before['approval_price'] ))){
                                $data[ 'last_approval_price' ]   = $this->monthlySearchService->calc_approval_price(
                                                                    trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval_price'] )->text() ) ) ,2
                                                                );
                            }else{ throw new \Exception( $selector_before[ 'approval_price' ].'要素が存在しません。'); }

                            // $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;

                            return $data;
                            
                        } );
                        
                        $array_site = array( );
                        $accesstrade_site = array( );
                        $x = 0;
                        
                        for ( $i = 0; $i < 2; $i++ ) {
                            
                            if ( $x == 0 ) { //今月
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                                    $start = date( 'Y-m-d', strtotime( 'first day of previous month' ) );
                                    $end   = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else {
                                    $start = date( 'Y-m-01' );
                                    $end   = date( 'Y-m-d', strtotime( '-1 day' ) );
                                }
                            } //$x == 0
                            else { //先月
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                                    $start = date( 'Y-m-01', strtotime( '-2 month' ) );
                                    $end   = date( 'Y-m-t', strtotime( '-2 month' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else {
                                    $start = date( 'Y-m-d', strtotime( 'first day of previous month' ) );
                                    $end   = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                }
                            }
                            
                            
                            $crawler_for_site = $browser->visit( "https://merchant.accesstrade.net/mapi/program/" . $product_info->asp_product_id . "/report/partner/monthly/approved?targetFrom=" . $start . "&targetTo=" . $end . "&pointbackSiteFlagList=0,1" )->crawler();
                            
                            
                            $array_site = $crawler_for_site->text();
                            
                            $array_site = json_decode( $array_site, true );
                            
                            $array_sites = $array_site[ "report" ];
                            
                            
                            foreach ( $array_sites as $site ) {
                                $accesstrade_site[ $x ][ 'product' ]        = $product_info->id;
                                $accesstrade_site[ $x ][ 'date' ]           = $end;
                                $accesstrade_site[ $x ][ 'media_id' ]       = $site[ "partnerSiteId" ];
                                $accesstrade_site[ $x ][ 'site_name' ]      = $site[ "partnerSiteName" ];
                                $accesstrade_site[ $x ][ 'approval' ]       = $site[ "approvedCount" ];
                                $accesstrade_site[ $x ][ 'approval_price' ] = $this->monthlySearchService->calc_approval_price($site[ "approvedTotalReward" ] ,2);
                                // $accesstrade_site[ $x ][ 'approval_price' ] = $product_info->price * $site[ "approvedCount" ];
                                
                                $x++;
                                
                            } //$array_sites as $site
                            
                        } //$i = 0; $i < 2; $i++
                        
                        $this->monthlySearchService->save_site( json_encode( $accesstrade_site ) );
                        $this->monthlySearchService->save_monthly( json_encode( $accesstrade_data ) );
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'アクセストレード',
                            'type' => 'Monthly',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }        
        } );
        
    }
    
    
}