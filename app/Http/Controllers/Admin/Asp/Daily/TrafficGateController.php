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

class TrafficGateController extends DailyCrawlerController
{
    
    /**
    TrafficGate
    */
    public function TrafficGate( $product_base_id ) //OK
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
        $product_id = $this->dailySearchService->BasetoProduct( 8, $product_base_id );
        
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
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
                    
                    //クロール実行が1日のとき
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                        $s_Y = date( 'Y', strtotime( '-1 day' ) );
                        $s_M = date( 'n', strtotime( '-1 day' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                        $s_Y = date( 'Y' );
                        $s_M = date( 'n' );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        /*
                        クロール：ログイン＝＞[日別売上検索]より検索
                        */
                        $crawler = $browser->visit( $product_info->asp->login_url )->type( $product_info->asp->login_key, $product_info->login_value )->type( $product_info->asp->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( "https://www.trafficgate.net/merchant/report/total_daily.cgi?year=" . $s_Y . "&month=" . $s_M )->crawler();
                        //echo $crawler->html();
                        
                        $crawler2 = $browser->visit( "https://www.trafficgate.net/merchant/alliance/index.cgi?all_mer_status=3" )->crawler();
                        
                        /*
                        selector 設定
                        */
                        $selector1 = array(
                            'imp' => '#container > form > table > tbody > tr > td.report-total:nth-child(2)',
                            'click' => '#container > form > table > tbody > tr > td.report-total:nth-child(3)',
                            'cv' => '#container > form > table > tbody > tr > td.report-total:nth-child(4)',
                            //'price' => '#container > form > table > tbody > tr > td.report-total:nth-child(6)'
                            
                        );
                        $selector2 = array(
                             'partnership' => '#container > table > tbody > tr:nth-child(1) > td > table:nth-child(7) > tbody > tr > td' 
                        );
                        #container > table > tbody > tr:nth-child(4) > td:nth-child(5)
                        /*
                        $crawler　をフィルタリング
                        */
                        $trafficgate_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
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
                        $trafficgate_data2 = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                        {
                            
                            foreach ( $selector2 as $key => $value ) {
                                $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                            } //$selector2 as $key => $value
                            return $data;
                            
                        } );
                        //var_dump($trafficgate_data2);
                        /*
                        サイト抽出　
                        */
                        /**
                        アクティブ数　サイト別データ抽出
                        */
                        $active_count      = 0;
                        $page              = 0;
                        
                        while ( trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $browser->visit( "https://www.trafficgate.net/merchant/report/site_monthly.cgi?page=" . $page . "&year=" . $s_Y . "&month=" . $s_M )->crawler()->filter( '#container-big2 > table > tbody > tr:nth-child(7) > td:nth-child(2)' )->text() ) ) ) != "" ) {
                            $i = 7;
                            //echo $page;
                            
                            $crawler_for_site = $browser->visit( "https://www.trafficgate.net/merchant/report/site_monthly.cgi?page=" . $page . "&year=" . $s_Y . "&month=" . $s_M )->crawler();
                            
                            while ( trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $crawler_for_site->filter( '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->text() ) ) ) != "" ) {

                                $trafficgate_site[ $active_count ][ 'product' ] = $product_info->id;
                                $trafficgate_site[ $active_count ][ 'imp' ]     = 0;

                                $selector_for_site = array(
                                    'media_id' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(3)',
                                    'site_name' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                                    'imp' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(8)',
                                    'click' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(9)',
                                    'cv' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(10)',
                                    'price' => '#container-big2 > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(11)' 
                                );
                                
                                foreach ( $selector_for_site as $key => $value ) {
                                    if ( $key == 'site_name' ) {
                                        
                                        $trafficgate_site[ $active_count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                        
                                    } //$key == 'site_name'
                                    else {
                                        
                                        $trafficgate_site[ $active_count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                    }
                                    
                                } //$selector_for_site as $key => $value
                                $calData                                     = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $trafficgate_site[ $active_count ][ 'cv' ], $trafficgate_site[ $active_count ][ 'price' ], 7 ) ) ), True );
                                $trafficgate_site[ $active_count ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                                $trafficgate_site[ $active_count ][ 'cost' ] = $calData[ 'cost' ];
                                $trafficgate_site[ $active_count ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                var_dump( $trafficgate_site[ $active_count ] );
                                $i++;
                                $active_count++;
                            } //行単位
                            $page++;
                        } //Page単位
                                                
                        $unit_price = $product_info->price;
                        $trafficgate_data[ 0 ][ 'price' ] = $trafficgate_data[ 0 ][ 'cv' ] * $unit_price;
                        
                        $trafficgate_data[ 0 ][ 'partnership' ] = $trafficgate_data2[ 0 ][ "partnership" ];
                        $trafficgate_data[ 0 ][ 'active' ]      = $active_count;
                        
                        $calData                         = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $trafficgate_data[ 0 ][ 'cv' ], $trafficgate_data[ 0 ][ 'price' ], 7 ) ) ), True );
                        $trafficgate_data[ 0 ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                        $trafficgate_data[ 0 ][ 'cost' ] = $calData[ 'cost' ];
                        
                        
                        //サイトデータ・日次データ保存
                        $this->dailySearchService->save_site( json_encode( $trafficgate_site ) );
                        $this->dailySearchService->save_daily( json_encode( $trafficgate_data ) );
                    
                    } 
            
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