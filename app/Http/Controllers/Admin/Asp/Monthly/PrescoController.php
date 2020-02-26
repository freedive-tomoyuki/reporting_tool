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

class PrescoController extends MonthlyCrawlerController
{
    
    public function presco( $product_base_id ) //OK
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
        
        $product_id = $this->monthlySearchService->BasetoProduct( 14, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );

                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                        $s_date_last_month     = date( 'Y/m/d', strtotime( 'first day of'. date( 'Y-m', strtotime( '-2 month' ) )));
                        $e_date_last_month     = date( 'Y/m/d', strtotime( 'last day of '. date( 'Y-m', strtotime( '-2 month' ) )));
                        $s_date  = date( 'Y/m/d', strtotime( 'first day of previous month') );
                        $e_date  = date( 'Y/m/d', strtotime( 'last day of previous month') );
                    } 
                    else {
                        $s_date_last_month     = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                        $e_date_last_month     = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                        $s_date  = date( 'Y/m/01' );
                        $e_date  = date( 'Y/m/d', strtotime( '-1 day' ) );
                    
                    }
                    \Log::info($s_date_last_month);
                    \Log::info($e_date_last_month);
                    \Log::info($s_date);
                    \Log::info($e_date);

                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        //今月分
                        \Log::info('point0');

                        \Log::info($product_info);
                        $crawler = $browser->visit($product_info->asp->login_url )
                                            ->type( $product_info->asp->login_key, $product_info->login_value )
                                            ->type( $product_info->asp->password_key, $product_info->password_value )
                                            ->click( $product_info->asp->login_selector )
                                            ->visit( $product_info->asp->lp1_url )
                                            ->visit( "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on" )
                                            ->crawler();
                        //先月分
                        $crawler2 = $browser->visit(  "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date_last_month . "&searchDateTimeTo=" . $e_date_last_month . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on" )->crawler();
                        
                        $selector   = array(
                            'approval' => '#reportTable > tbody > tr > td:nth-child(4) > div > div',
                            'approval_price' => '#reportTable > tbody > tr > td:nth-child(6)'
                        );
                        // var_dump($crawler);
                        \Log::info($selector);
                        \Log::info('point0.5');
                        //今月用のデータ取得selector
                        $presco_data = $crawler->each( function( Crawler $node ) use ($selector, $product_info)
                        {
                            
                            // $data              = array();
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            // $unit_price = $product_info->price;
                            \Log::info($data[ 'asp' ] );
                            \Log::info($data[ 'product' ] );
                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );

                            // $value = $selector_this[ 'approval' ];
                            
                            if(count($node->filter( $selector['approval'] ))){
                                $data[ 'approval' ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector['approval'] )->text() ) );
                            }else{ throw new \Exception( $selector[ 'approval' ].'要素が存在しません。'); }
                            
                            if(count($node->filter( $selector['approval_price'] ))){
                                $data[ 'approval_price' ]   = $this->monthlySearchService->calc_approval_price(
                                                                    trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector['approval_price'] )->text() ) ) ,14
                                                                );
                            }else{ throw new \Exception( $selector[ 'approval_price' ].'要素が存在しません。'); }
                            \Log::info($data[ 'approval' ] );
                            \Log::info($data[ 'approval_price' ] );
                            return $data;

                        });
                        // var_dump($presco_data);
                        \Log::info('point1');
                        $presco_data2 = $crawler2->each( function( Crawler $node ) use ($selector )
                        {
                            // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            }
                            else {
                                $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                            // $selector = $selector_before['approval'];

                            if(count($node->filter( $selector['approval'] ))){
                                $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector['approval'] )->text() ) );
                            }else{ throw new \Exception( $selector['approval'].'要素が存在しません。'); }

                            if(count($node->filter( $selector['approval_price'] ))){
                                $data[ 'last_approval_price' ]   = $this->monthlySearchService->calc_approval_price(
                                                                    trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector['approval_price'] )->text() ) ) ,14
                                                                );
                            }else{ throw new \Exception( $selector[ 'approval_price' ].'要素が存在しません。'); }


                            return $data;
                            
                        } );
                        var_dump($presco_data);
                        \Log::info($presco_data);
                        var_dump($presco_data2);
                        var_dump($presco_data + $presco_data2);
                        // $array_site = array( );
                        // $presco_site = array( );
                        // $x = 0;
                        // //1回目：今月分　2回目：先月分
                        // \Log::info('1');

                        for ( $x = 0; $x < 2; $x++ ) {
                            $i = 1;
                            \Log::info('aaa'.$i);
                            if ( $x == 0 ) {//
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $s_date     = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                                    $e_date     = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                                } //date( 'Y/m/d' ) == dave( 'Y/m/01' )
                                else {
                                    $s_date  = date( 'Y/m/01' );
                                    $e_date  = date( 'Y/m/d', strtotime( '-1 day' ) ); 
                                }
                                //先月分のクロール
                            } //$x == 0
                            else {
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $s_date     = date( 'Y/m/d', strtotime( 'first day of'. date( 'Y-m', strtotime( '-2 month' ) )));
                                    $e_date     = date( 'Y/m/d', strtotime( 'last day of '. date( 'Y-m', strtotime( '-2 month' ) )));
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else {
                                    $s_date     = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                                    $e_date     = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                                }
                            }
                            $crowle_url_for_site = "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=2&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=1&_searchJoinType=on";
                            
                            $crawler_for_site = $browser->visit( $crowle_url_for_site )->crawler();
                        
                            while ( $crawler_for_site->filter( '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(5) > div > div' )->count() > 0 ) { //１行目が空になるまで
                                
                            
                                    $presco_site[ $i ][ 'product' ] = $product_info->id;
                                    
                                    if ( $x == 0 ) {
                                        $presco_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                    } 
                                    else {
                                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                            $presco_site[ $i ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                        }
                                        else {
                                            $presco_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                        }
                                    }
                                    
                                    $selector_for_site = array(
                                        'media_id'      => '#reportTable_wrapper > div.m-table--list-container > div > div > div.DTFC_LeftWrapper > div.DTFC_LeftBodyWrapper > div > table > tbody > tr:nth-child('.$i.') > td.sorting_1.sorting_2 > div > div',
                                        'site_name'     => '#reportTable_wrapper > div.m-table--list-container > div > div > div.DTFC_LeftWrapper > div.DTFC_LeftBodyWrapper > div > table > tbody > tr:nth-child('.$i.') > td:nth-child(2) > div > div',
                                        'approval'      => '#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(7) > div > div',
                                        'approval_price' => '#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(9)'
                                    );
                                    
                                    foreach ( $selector_for_site as $key => $value ) {
                                        if(count($crawler_for_site->filter( $value ))){
                                            if ( $key == 'site_name' ) {
                                            
                                                $presco_site[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                                
                                            }
                                            elseif ( $key == 'approval_price' ) {
                                                
                                                $presco_site[ $i ][ $key ] = $this->monthlySearchService->calc_approval_price( 
                                                                                trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) )
                                                                            ,5);
                                            } 
                                            else {
                                                
                                                $presco_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                            }
                                        }else{
                                            throw new \Exception($value.'要素が存在しません。');
                                        }
                                    }
                                    $i++;
                                }
                            }
                            \Log::info($presco_site);
                            var_dump($presco_site);
                        $this->monthlySearchService->save_site( json_encode( $presco_site ) );
                        $this->monthlySearchService->save_monthly( json_encode( $presco_data2 ) );
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'プレスコ',
                            'type' => 'Monthly',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }        
        } );
        
    }
    
    
}