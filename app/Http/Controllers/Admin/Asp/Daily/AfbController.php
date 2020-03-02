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
        $products = json_decode($this->dailySearchService->BasetoProduct( 4, $product_base_id ),true);
        
        // Chromeドライバーのインスタンス呼び出し
        //$client = new Client( new Chrome( $options ) );
        $client = new Client( new Chrome( $options ) );
        foreach($products as $p ){
            
            $product_id = $p['id'];
            $product_name = $p['product'];

            //Chromeドライバー実行
            $client->browse( function( Browser $browser ) use(&$crawler, $product_id, $product_name)
            {
                try{

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
                                ->visit( 'https://client.afi-b.com/client/b/cl/report/?r=daily' )
                                ->click('#adv_id_daily_chzn > a')
                                ->click('#adv_id_daily_chzn_o_'.$product_info->product_order)
                                ->type('#report_form_2 > div > table > tbody > tr > td > ul > li > #form_start_date', $s_date ) 
                                ->type('#report_form_2 > div > table > tbody > tr > td > ul > li > #form_end_date', $e_date )
                                ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(1)')
                                ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(2)')
                                ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(3)')
                                ->click('#report_form_2 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')->crawler();
                            

                            $crawler2 = $browser->visit( 'https://client.afi-b.com/client/b/cl/main' )->crawler();
                            
                            $crawler3 = 
                                $browser
                                ->visit( 'https://client.afi-b.com/client/b/cl/report/?r=site#report_view' )
                                ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(1)' )
                                ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(2)' )
                                ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(3)' )
                                ->click('#report_form_4 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')
                                ->crawler();
                                

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
                                'active' => '#report_view > div > ul > li:nth-child(4)' 
                            );
                            
                            $afb_data = $crawler->each( function( Crawler $node ) use ($selector_crawler, $product_info)
                            {
                                
                                $data              = array( );
                                $data[ 'asp' ]     = $product_info->asp_id;
                                $data[ 'product' ] = $product_info->id;
                                
                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                
                                foreach ( $selector_crawler as $key => $value ) {
                                    if(count($node->filter( $value ))){
                                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                    }else{
                                        throw new \Exception($value.'要素が存在しません。');
                                    }
                                    
                                } //$selector_crawler as $key => $value

                                // $unit_price = $product_info->price;
                                // $data[ 'price' ] = $data[ 'cv' ] * $unit_price;

                                $calculated        = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $data[ 'cv' ], $data[ 'price' ], 4 ) ) ), True );
                                $data[ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                                $data[ 'cost' ] = $calculated[ 'cost' ]; //獲得単価
                                return $data;
                                
                            } );
                            // $partnership = $crawler2->each( function( Crawler $node ) use ($selector_crawler2)
                            // {
                            //     foreach ( $selector_crawler2 as $key => $value ) {
                                    if(count($crawler2->filter('#main > div.wrap > div.section33 > div.section_inner.positionr.positionr > table > tbody > tr:nth-child(13) > td:nth-child(2)'))){
                                        $partnership = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                    }else {
                                        $partnership = 0;
                                        // throw new \Exception($value.'要素が存在しません。');
                                    }
                                
                                // } //$selector_crawler2 as $key => $value
                                // return $partnership;
                                //var_dump($data);
                            // } );
                            // $active = $crawler3->each( function( Crawler $node ) use ($selector_crawler3)
                            // {
                            //     foreach ( $selector_crawler3 as $key => $value ) {
                                    if(count($crawler3->filter( '#report_view > div > ul > li:nth-child(4)' ))){
                                        $active = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                    }else{
                                        $active = 0;
                                        // throw new \Exception($value.'要素が存在しません。');
                                    }
                            //     } //$selector_crawler3 as $key => $value
                            //     return $active;
                            // } );
                            
                            $count_data = $active;
                            $afb_data[ 0 ][ 'active' ]      = $count_data;
                            $afb_data[ 0 ][ 'partnership' ] = $partnership;

                            $afb_site    = array( );
                            //echo $count_data;
                            if($count_data <= 0){ throw new \Exception('アクティブパートナーが存在しませんでした。'); }
                            
                            for ( $i = 1; $count_data >= $i; $i++ ) {
                                $afb_site[ $i ][ 'product' ] = $product_info->id;
                                $afb_site[ $i ][ 'asp' ]   = $product_info->asp_id;

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
                                    if(count($crawler3->filter( $value ))){
                                        if ( $key == 'media_id' ) {
                                            //$data = trim($node->filter($value)->attr('title'));
                                            $media_id = array( );
                                            $sid      = trim( $crawler3->filter( $value )->attr( 'title' ) );
                                            preg_match( '/SID：(\d+)/', $sid, $media_id );
                                            
                                            $afb_site[ $i ][ $key ] = $media_id[ 1 ];
                                            
                                        } //$key == 'media_id'
                                        elseif ( $key == 'site_name' ) {
                                            
                                            $afb_site[ $i ][ $key ] = trim( $crawler3->filter( $value )->text() );
                                            
                                        } //$key == 'site_name'
                                        else {
                                            
                                            $afb_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler3->filter( $value )->text() ) );
                                            
                                        }
                                    }else{
                                        throw new \Exception($value.'要素が存在しません。');
                                    }
                                }
                                
                                // $unit_price = $product_info->price;
                                // $afb_site[ $i ][ 'price' ] = $unit_price * $afb_site[ $i ][ 'cv' ];

                                $calculated                 = json_decode( json_encode( json_decode( $this->dailySearchService->cpa( $afb_site[ $i ][ 'cv' ], $afb_site[ $i ][ 'price' ], 4 ) ) ), True );
                                $afb_site[ $i ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                                $afb_site[ $i ][ 'cost' ] = $calculated[ 'cost' ]; //獲得単価
                                
                                $afb_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            }
                            
                            
                            $this->dailySearchService->save_daily( json_encode( $afb_data ) );
                            $this->dailySearchService->save_site( json_encode( $afb_site ) );
                            
                        }
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'afb',
                                'type' => 'Daily',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                
                }
            });
        }
    }
    
}