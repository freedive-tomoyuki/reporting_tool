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


class SCANController extends DailyCrawlerController
{
    
    /**
    AffTown
    */
    public function scan( $product_base_id ) //OK
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
        $product_id = $this->dailySearchService->BasetoProduct( 9, $product_base_id );
        
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
                    /*
                    日付　取得
                    */
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                        $s_Y = date( 'Y', strtotime( 'first day of previous month' ) );
                        $s_M = date( 'n', strtotime( 'first day of previous month' ) );
                        $s_D = date( 'd', strtotime( 'first day of previous month' ) );
                        $e_Y = date( 'Y', strtotime( 'last day of previous month' ) );
                        $e_M = date( 'n', strtotime( 'last day of previous month' ) );
                        $e_D = date( 'd', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                        $s_Y = date( 'Y' );
                        $s_M = date( 'n' );
                        $s_D = 1;
                        $e_Y = date( 'Y', strtotime( '-1 day' ) );
                        $e_M = date( 'n', strtotime( '-1 day' ) );
                        $e_D = date( 'd', strtotime( '-1 day' ) );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        /*
                        クロール：ログイン＝＞[日別売上検索]より検索
                        */
                        $crawler  = $browser->visit( $product_info->asp->login_url )->type( $product_info->asp->login_key, $product_info->login_value )->type( $product_info->asp->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( "https://www.scadnet.com/merchant/report/daily.php?s=" . $product_info->asp_sponsor_id . "&c_id=" . $product_info->asp_product_id . "&m_id=&s_yy=" . $s_Y . "&s_mm=" . $s_M . "&s_dd=" . $s_D . "&e_yy=" . $e_Y . "&e_mm=" . $e_M . "&e_dd=" . $e_D )->crawler();
                        //echo $crawler->html();
                        //アクティブ／提携件数
                        $crawler2 = $browser->visit( "https://www.scadnet.com/merchant/report/monthly.php?s=" . $product_info->asp_sponsor_id . "&c_id=" . $product_info->asp_product_id . "&s_yy=" . $s_Y . "&s_mm=" . $s_M . "&e_yy=" . $e_Y . "&e_mm=" . $e_M )->crawler();
                        
                        /*
                        selector 設定
                        */
                        $selector1 = array(
                            'imp' => '#report_clm > div > div.report_table > table > tbody > tr.tr_sum > td:nth-child(2)',
                            'click' => '#report_clm > div > div.report_table > table > tbody > tr.tr_sum > td:nth-child(3)',
                            'cv' => '#report_clm > div > div.report_table > table > tbody > tr.tr_sum > td:nth-child(6)' 
                            
                        );
                        $selector2 = array(
                            'partnership' => '#report_clm > div > div.report_table > table > tbody > tr.tr_even > td:nth-child(4)',
                            'active' => '#report_clm > div > div.report_table > table > tbody > tr.tr_even > td:nth-child(5)',
                            
                            'price' => '#report_clm > div > div.report_table > table > tbody > tr.tr_even > td:nth-child(12)' 
                        );
                        
                        
                        /*
                        $crawler　をフィルタリング
                        */
                        $scan_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                        {
                            
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            $data[ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            foreach ( $selector1 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector1 as $key => $value
                            return $data;
                            
                        } );
                        $scan_data2 = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                        {
                            $data = array( );
                            foreach ( $selector2 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector2 as $key => $value
                            return $data;
                            
                        } );
                        
                        //var_dump( $scan_data );
                        //var_dump( $scan_data2 );
                        //var_dump($scan_data3);
                        /*
                        サイト抽出　
                        */
                        $crawler_for_site = $browser->visit( "https://www.scadnet.com/merchant/report/site.php?s=" . $product_info->asp_sponsor_id . "&s_yy=" . $s_Y . "&s_mm=" . $s_M . "&s_dd=" . $s_D . "&e_yy=" . $e_Y . "&e_mm=" . $e_M . "&e_dd=" . $e_D )->crawler();
                        $y                = 0;
                        $i                = 3;
                        
                        //echo $crawler_for_site->html();
                        while ( $crawler_for_site->filter( '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->count() > 0 ) {
                            $scan_site[ $y ][ 'product' ] = $product_info->id;
                            $scan_site[ $y ][ 'asp' ]     = $product_info->asp_id;
                            $scan_site[ $y ][ 'imp' ]     = 0;
                            
                            $selector_for_site = array(
                                'media_id' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)',
                                'site_name' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                                'imp' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(6)',
                                'click' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(7)',
                                'approval' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(12)',
                                'cv' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(10)',
                                'price' => '#report_clm > div > div.report_table > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(13)' 
                            );
                            
                            foreach ( $selector_for_site as $key => $value ) {
                                if(count($crawler_for_site->filter( $value ))){
                                    if ( $key == 'site_name' || $key == 'media_id' ) {
                                        
                                            $scan_site[ $y ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                       
                                    } //$key == 'site_name' || $key == 'media_id'
                                    else {
                                        
                                        $scan_site[ $y ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                    }
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                                
                            } //$selector_for_site as $key => $value
                            
                            //$scan_site[ $y ][ 'price' ] = ($scan_site[ $y ][ 'price' ] != 0 && $scan_site[ $y ][ 'approval' ] != 0  )? round (( $scan_site[ $y ][ 'price' ] / $scan_site[ $y ][ 'approval' ] ) * $scan_site[ $y ][ 'cv' ]) : 0 ;
                            
                            // $unit_price = $product_info->price;
                            // $scan_site[ $y ][ 'price' ] = $unit_price * $scan_site[ $y ][ 'cv' ];

                            $calculated                   = json_decode( 
                                                            json_encode( 
                                                                json_decode( 
                                                                    $this->dailySearchService
                                                                        ->cpa( $scan_site[ $y ][ 'cv' ], $scan_site[ $y ][ 'price' ], 7 ) 
                                                                ) 
                                                            ), True );
                            $scan_site[ $y ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                            $scan_site[ $y ][ 'cost' ] = $calculated[ 'cost' ];
                            $scan_site[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            $i++;
                            $y++;
                        } 
                        
                        // $unit_price = $product_info->price;
                        // $scan_data[ 0 ][ 'price' ] = $scan_data[ 0 ][ 'cv' ] * $unit_price;
                        
                        $scan_data[ 0 ][ 'partnership' ] = $scan_data2[ 0 ][ 'partnership' ];
                        
                        $scan_data[ 0 ][ 'active' ] = $scan_data2[ 0 ][ 'active' ];
                        
                        //$scan_data[ 0 ][ 'price' ] = $scan_data2[ 0 ][ 'price' ];
                        
                        $calculated                  = json_decode( 
                                                            json_encode( 
                                                                json_decode( 
                                                                    $this->dailySearchService
                                                                                    ->cpa( $scan_data[ 0 ][ 'cv' ], $scan_data[ 0 ][ 'price' ], 7 ) 
                                                                ) ), True );
                        $scan_data[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        $scan_data[ 0 ][ 'cost' ] = $calculated[ 'cost' ];
                        
                        /*
                        サイトデータ・日次データ保存
                        */
                        
                        $this->dailySearchService->save_daily( json_encode( $scan_data ) );
                        $this->dailySearchService->save_site( json_encode( $scan_site ) );
                        
                        //var_dump($crawler_for_site);
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'SCAN',
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }
        } );
        
    }
}