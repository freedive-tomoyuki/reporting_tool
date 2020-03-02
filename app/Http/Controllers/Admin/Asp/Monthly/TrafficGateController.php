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

class TrafficGateController extends MonthlyCrawlerController
{
    
    public function trafficgate( $product_base_id ) //OK
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
        
        $products =  json_decode($this->monthlySearchService->BasetoProduct( 8, $product_base_id ),true);
        
        $client = new Client( new Chrome( $options ) );
        
        foreach($products as $p ){
            
            $product_id = $p['id'];   
            $product_name = $p['product'];

            $client->browse( function( Browser $browser ) use (&$crawler, $product_id, $product_name)
            {
                try{
                        $product_infos = \App\Product::all()->where( 'id', $product_id );
                        
                        foreach ( $product_infos as $product_info ) {
                            
                            $crawler = $browser->visit( $product_info->asp->login_url )
                                                ->type( $product_info->asp->login_key, $product_info->login_value )
                                                ->type( $product_info->asp->password_key, $product_info->password_value )
                                                ->click( $product_info->asp->login_selector )
                                                ->visit( "https://www.trafficgate.net/merchant/sales/" )->crawler();
                            //echo $crawler->html();
                            
                            /**
                            * 先月・今月のセレクタ
                            */
                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $row_this   = 2;
                                $row_before = 3;
                            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            else {
                                $row_this   = 3;
                                $row_before = 4;
                            }
                            $selector_this = array(
                                'approval' => '#container > table:nth-child(4) > tbody > tr:nth-child(3) > td:nth-child(' . $row_this . ')',
                                'approval_price' => '#container > table:nth-child(4) > tbody > tr:nth-child(4) > td:nth-child(' . $row_this . ')' 
                            );
                            
                            $selector_before = array(
                                'approval' => '#container > table:nth-child(4) > tbody > tr:nth-child(3) > td:nth-child(' . $row_before . ')',
                                'approval_price' => '#container > table:nth-child(4) > tbody > tr:nth-child(4) > td:nth-child(' . $row_before . ')' 
                            );
                            
                            
                            $trafficgate_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                            {
                                
                                $data              = array( );
                                $data[ 'asp' ]     = $product_info->asp_id;
                                $data[ 'product' ] = $product_info->id;
                                
                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                
                                $unit_price = $product_info->price;
                                

                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                if(count($node->filter( $selector_this['approval'] ))){
                                    $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval'] )->text() ) );
                                }else{ throw new \Exception( $selector_this['approval'].'要素が存在しません。'); }
                                
                                // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;
                                if(count($node->filter( $selector_this['approval_price'] ))){
                                    $data[ 'approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_this['approval_price'] )->text() ) )
                                                                    ,8);
                                }else{ throw new \Exception($selector_this['approval_price'].'要素が存在しません。'); }

                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                } 
                                else {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                }

                                if(count($node->filter( $selector_before['approval'] ))){
                                    $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval']  )->text() ) );
                                }else{ throw new \Exception($selector_before['approval'].'要素が存在しません。'); }

                                if(count($node->filter( $selector_before['approval_price'] ))){
                                    $data[ 'last_approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_before['approval_price'] )->text() ) )
                                                                    ,8);
                                }else{ throw new \Exception($selector_before['approval_price'].'要素が存在しません。'); }

                                // $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;
                                
                                return $data;
                                
                            } );
                            
                            //var_dump( $trafficgate_data );
                            
                            //$rtsite = array();
                            
                            $active_count = 0;
                            
                            /**
                            *    $x = 0：今月
                            *    $x = 1：先月
                            */
                            for ( $x = 0; $x < 2; $x++ ) {
                                $page = 0;
                                /*
                                if($x == 0){//今月
                                $y = date('Y');
                                $m = date('n');
                                }else{//先月
                                $y = date('Y',strtotime('-1 month'));
                                $m = date('n',strtotime('-1 month'));
                                }
                                */
                                if ( $x == 0 ) {
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $y = date( 'Y', strtotime( '-1 month' ) ); //先月
                                        $m = date( 'n', strtotime( '-1 month' ) ); //先月
                                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                    else {
                                        $y = date( 'Y' ); //今月
                                        $m = date( 'n' ); //今月
                                    }
                                    //先月分のクロール
                                } //$x == 0
                                else {
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $y = date( 'Y', strtotime( '-2 month' ) ); //先々月
                                        $m = date( 'n', strtotime( '-2 month' ) ); //先々月
                                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                    else {
                                        $y = date( 'Y', strtotime( '-1 month' ) ); //先月
                                        $m = date( 'n', strtotime( '-1 month' ) ); //先月
                                    }
                                }
                                // echo 'Y' . $y;
                                // echo 'm' . $m;
                                //$crawler_for_site = $browser
                                //  ->visit("https://www.trafficgate.net/merchant/report/site_monthly.cgi?year=".$y."&month=".$m)
                                //  ->crawler();
                                
                                //   ページ単位でクロール
                                
                                while ( trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $browser->visit( "https://www.trafficgate.net/merchant/report/site_monthly.cgi?page=" . $page . "&year=" . $y . "&month=" . $m )->crawler()->filter( '#container-big2 > table > tbody > tr:nth-child(7) > td:nth-child(2)' )->text() ) ) ) != "" ) { //１行目が空になるまで
                                    
                                    $i                = 7;
                                    $crawler_for_site = $browser->visit( "https://www.trafficgate.net/merchant/report/site_monthly.cgi?page=" . $page . "&year=" . $y . "&month=" . $m )->crawler();
                                    //echo $crawler_for_site->html();
                                    // 行単位でクロール
                                    while ( trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $crawler_for_site->filter( '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->text() ) ) ) != "" ) { //最終行が空になるまで
                                        
                                        $trafficgate_site[ $active_count ][ 'product' ] = $product_info->id;
                                        
                                        if ( $x == 0 ) {
                                            $trafficgate_site[ $active_count ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                        } 
                                        else {
                                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                                $trafficgate_site[ $active_count ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                            }
                                            else {
                                                $trafficgate_site[ $active_count ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                            }
                                        }
                                        
                                        //echo $trafficgate_site[$active_count]['date'];
                                        //$iPlus = $i+1;
                                        
                                        $approval_selector      = '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(14)';
                                        $approvalprice_selector = '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(15)';
                                        
                                        $selector_for_site = array(
                                            'media_id' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(3)',
                                            'site_name' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                                            'approval' => $approval_selector,
                                            'approval_price' => $approvalprice_selector 
                                            
                                        );
                                        
                                        foreach ( $selector_for_site as $key => $value ) {
                                            if(count($crawler_for_site->filter( $value ))){
                                                if ( $key == 'site_name' ) {
                                                    
                                                    $trafficgate_site[ $active_count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                                    
                                                }
                                                elseif ( $key == 'approval_price' ) {
                                                
                                                    $trafficgate_site[ $active_count ][ $key ] = $this->monthlySearchService->calc_approval_price( 
                                                                                    trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) )
                                                                                ,8);
                                                }
                                                else {
                                                    
                                                    $trafficgate_site[ $active_count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                                }
                                            }else{
                                                throw new \Exception($value.'要素が存在しません。');
                                            }
                                        }
                                        // $trafficgate_site[ $active_count ][ 'approval_price' ] = $trafficgate_site[ $active_count ][ 'approval' ] * $product_info->price;

                                        $active_count++;
                                        $i++;
                                    }

                                    $page++;
                                }
                            } 

                            $this->monthlySearchService->save_monthly( json_encode( $trafficgate_data ) );
                            $this->monthlySearchService->save_site( json_encode( $trafficgate_site ) );
                            
                        }
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'TrafficGate',
                                'type' => 'Monthly',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                                throw $e;
                }        
            } );
        }
    }
    
}
