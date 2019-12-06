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

class AccesstradeController extends DailyCrawlerController
{
    
    public function accesstrade( $product_base_id ) //OK
    {
        
        Browser::macro('crawler', function () {
        return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
        });
        
        $options = [
        '--window-size=1920,1080',
        '--start-maximized',
        '--headless',
        '--disable-gpu',
        '--lang=ja_JP',
        '--no-sandbox'
        
        ];
        
        $product_id = $this->dailySearchService->BasetoProduct( 2, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
                    //クロール実行が1日のとき
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                        $s_date = date( 'Y-m-d', strtotime( 'first day of previous month' ) );
                        $e_date = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                        $s_date = date( 'Y-m-01' );
                        $e_date = date( 'Y-m-d', strtotime( '-1 day' ) );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        $crawler = $browser->visit( $product_info->asp->login_url )->type( $product_info->asp->login_key, $product_info->login_value )->type( $product_info->asp->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( $product_info->asp->lp1_url . $product_info->asp_product_id )->crawler();
                        
                       //X月1日のときのセレクタ変更
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $selector = array(
                                'imp' => 'body > program-page > div > div > main > program-home > section:nth-child(2) > div > div > div > summary-report > div > table > tbody > tr:nth-child(2) > td:nth-child(2)',
                                'click' => 'body > program-page > div > div > main > program-home > section:nth-child(2) > div > div > div > summary-report > div > table > tbody > tr:nth-child(2) > td:nth-child(3)',
                                'cv' => 'body > program-page > div > div > main > program-home > section:nth-child(2) > div > div > div > summary-report > div > table > tbody > tr:nth-child(2) > td:nth-child(4)',
                                'partnership' => $product_info->asp->daily_partnership_selector,
                                'active' => $product_info->asp->daily_active_selector,
                                //'price' => 'body > program-page > div > div > main > program-home > section:nth-child(2) > div > div > div > summary-report > div > table > tbody > tr:nth-child(2) > td:nth-child(9)' 
                            );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $selector = array(
                                'imp' => $product_info->asp->daily_imp_selector,
                                'click' => $product_info->asp->daily_click_selector,
                                'cv' => $product_info->asp->daily_cv_selector,
                                'partnership' => $product_info->asp->daily_partnership_selector,
                                'active' => $product_info->asp->daily_active_selector,
                                //'price' => $product_info->asp->daily_price_selector 
                            );
                        }
                        //var_dump( $crawler );
                        //$crawler->each(function (Crawler $node) use ( $selector ){
                        
                        $atdata = $crawler->each( function( Crawler $node ) use ($selector, $product_info)
                        {
                            $unit_price = $product_info->price;
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            foreach ( $selector as $key => $value ) {
                                
                                if ( $key == 'active' ) {
                                    $active       = explode( "/", $node->filter( $value )->text() );
                                    $data[ $key ] = trim( $active[ 0 ] );
                                    
                                } //$key == 'active'
                                elseif ( $key == 'partnership' ) {
                                    
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                    
                                } //$key == 'partnership'
                                else {
                                    
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                    
                                }
                            } //$selector as $key => $value
                            //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 2);
                            
                            $data[ 'price' ] = $data['cv'] * $unit_price;

                            $calData = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $data[ 'cv' ], $data[ 'price' ], 2 ) ) ), True );
                            
                            $data[ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                            $data[ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                            
                            //var_dump($data);
                            return $data;
                            
                        } );
                        
                        
                        $array_site       = array( );
                        $crawler_for_site = $browser->visit( "https://merchant.accesstrade.net/mapi/program/" . $product_info->asp_product_id . "/report/partner/monthly/occurred?targetFrom=" . $s_date . "&targetTo=" . $e_date . "&pointbackSiteFlagList=0,1" )->crawler();
                        
                        $array_site = $crawler_for_site->text();
                        
                        $array_site = json_decode( $array_site, true );
                        
                        $array_sites = $array_site[ "report" ];
                        
                        $x = 0;
                        
                        foreach ( $array_sites as $site ) {
                            $data[ $x ][ 'product' ]   = $product_info->id;
                            $data[ $x ][ 'media_id' ]  = $site[ "partnerSiteId" ];
                            $data[ $x ][ 'site_name' ] = $site[ "partnerSiteName" ];
                            $data[ $x ][ 'imp' ]       = $site[ "impressionCount" ];
                            $data[ $x ][ 'click' ]     = $site[ "clickCount" ];
                            $data[ $x ][ 'cv' ]        = $site[ "actionCount" ];
                            $data[ $x ][ 'price' ]     = $site[ "occurredTotalReward" ];
                            
                            //$data[$x]['cpa']= $this->cpa($site['occurredTotalReward'] ,$site["actionCount"] , 1)
                            $calData              = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $site[ "actionCount" ], $site[ 'occurredTotalReward' ], 1 ) ) ), True );
                            $data[ $x ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                            $data[ $x ][ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                            
                            $data[ $x ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            $x++;
                            
                        } //$array_sites as $site
                        //var_dump( $data );
                        
                        $this->dailySearchService->save_site( json_encode( $data ) );
                        $this->dailySearchService->save_daily( json_encode( $atdata ) );
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
                            throw $e;
            }
        } );
        
    }
    
}