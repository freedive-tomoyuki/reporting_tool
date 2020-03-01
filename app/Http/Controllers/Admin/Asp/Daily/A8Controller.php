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

class A8Controller extends DailyCrawlerController
{
    
    public function a8( $product_base_id ) //OK
    {
        
        Browser::macro('crawler', function () {
        return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
        });
        
        $options = [
        '--window-size=1920,1080',
        '--start-maximized',
        '--headless',
        '--disable-gpu',
        '--no-sandbox'
        
        ];
        
        //案件の大本IDからASP別のプロダクトIDを取得
        $products = json_decode($this->dailySearchService->BasetoProduct( 1, $product_base_id ),true);
        
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        foreach($products as $p ){
            
            $product_id = $p['id'];
            $product_name = $p['product'];

            //Chromeドライバー実行
            $client->browse( function( Browser $browser ) use (&$crawler, $product_id, $product_name)
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

                    //案件IDごとにスクレイピング
                    //
                    foreach ( $product_infos as $product_info ) {
                        //ルート①
                        $crawler_1 = $browser
                                ->visit( $product_info->asp->login_url )
                                ->type( $product_info->asp->login_key,$product_info->login_value )
                                ->type( $product_info->asp->password_key,$product_info->password_value)
                                ->click( $product_info->asp->login_selector )
                                ->visit( $product_info->asp->lp1_url . $product_info->asp_product_id )
                                ->crawler();
                        //ルート②
                        $crawler_2 = $browser
                                ->visit( $product_info->asp->lp2_url )
                                ->select( '#reportOutAction > table > tbody > tr:nth-child(2) > td > select', '23' )
                                ->radio( 'insId', $product_info->asp_product_id )
                                ->click( '#reportOutAction > input[type="image"]:nth-child(3)' )
                                ->crawler();

                        //ルート①：アクティブ数／低係数
                        //毎月1日→TRUE／毎月1日以外→False
                        //TRUE:管理画面で先月の1日〜末日まで数値の取得を行う。
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $selector_1 = array(
                                'active' => '#element > tbody > tr:nth-child(2) > td:nth-child(3)',
                                'partnership' => '#element > tbody > tr:nth-child(2) > td:nth-child(2)' 
                            );
                        }
                        else {
                            $selector_1 = array(
                                'active' => $product_info->asp->daily_active_selector,
                                'partnership' => $product_info->asp->daily_partnership_selector 
                            );
                        }
                        //ルート②：インプレッション／クリック数／CV数
                        $selector_2 = array(
                            'imp' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(2)',
                            'click' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(3)',
                            'price' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(12)',
                            'cv' => $product_info->asp->daily_cv_selector 
                        );
                        
                        //ルート①：+ASPID／案件ID追加 且つ　セレクタをもとに抽出
                        $a8_data_1 = $crawler_1->each( function( Crawler $node ) use ($selector_1, $product_info)
                        {
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            
                            foreach ( $selector_1 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( $node->filter( $value )->text() );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector_1 as $key => $value
                            return $data;
                        } );
                        //var_dump( $a8_data_1 );

                        //ルート②：セレクタをもとに抽出
                        $a8_data_2 = $crawler_2->each( function( Crawler $node ) use ($selector_2)
                        {
                            foreach ( $selector_2 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( $node->filter( $value )->text() );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            }
                            return $data;
                        } );

                        //var_dump( $a8_data_2 );

                        // $unit_price = $product_info->price;
                        
                        //数値変換
                        $a8_data_1[ 0 ][ 'cv' ]    = trim( preg_replace( '/[^0-9]/', '', $a8_data_2[ 0 ][ "cv" ] ) );
                        $a8_data_1[ 0 ][ 'click' ] = trim( preg_replace( '/[^0-9]/', '', $a8_data_2[ 0 ][ "click" ] ) );
                        $a8_data_1[ 0 ][ 'imp' ]   = trim( preg_replace( '/[^0-9]/', '', $a8_data_2[ 0 ][ "imp" ] ) );
                        // $a8_data_1[ 0 ][ 'price' ] = $a8_data_1[ 0 ][ 'cv' ] * $unit_price;
                        $a8_data_1[ 0 ][ 'price' ] = trim( preg_replace( '/[^0-9]/', '', $a8_data_2[ 0 ][ "price" ] ) );

                        // echo "合計<br>";
                        // echo $a8_data_1[ 0 ][ 'cv' ]."<br>";
                        // echo $unit_price."<br>";
                        // echo $a8_data_1[ 0 ][ 'price' ];
                        
                        //CPA／
                        $calculated = json_decode( 
                                        json_encode( 
                                            json_decode( 
                                                $this->dailySearchService
                                                    ->cpa( $a8_data_1[ 0 ][ 'cv' ], $a8_data_1[ 0 ][ 'price' ], 1 ) 
                                            ) 
                                        ), True );
                        
                        $a8_data_1[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        $a8_data_1[ 0 ][ 'cost' ] = $calculated[ 'cost' ]; //獲得単価
                        $a8_data_1[ 0 ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                        
                        //
                        //サイト単位スクレイピング
                        //
                        $crawler_for_site = $browser
                                            ->visit('https://adv.a8.net/a8v2/ecAsRankingReportAction.do?reportType=11&insId=' . $product_info->asp_product_id . '&asmstId=&termType=1&d-2037996-p=1&multiSelectFlg=0&year=' . $s_Y . '&month=' . $s_M )
                                            ->crawler();
                        //https://adv.a8.net/a8v2/ecAsRankingReportAction.do?reportType=11&insId=s00000015456001&asmstId=&termType=1&d-2037996-p=1&multiSelectFlg=0&year=2020&month=01
                        $count_selector   = '#contents1clm > form:nth-child(6) > span.pagebanner';

                        if(count($crawler_for_site->filter( $count_selector ))){
                            $count_data       = intval( trim( preg_replace( '/[^0-9]/', '', substr( $crawler_for_site->filter( $count_selector )->text(), 0, 7 ) ) ) );
                        }else{
                            throw new \Exception($count_selector.'要素が存在しません。');
                        }
                        
                        if($count_data <= 0){ throw new \Exception('アクティブパートナーが存在しませんでした。'); }
                        //echo 'count_data＞'.$count_data;
                        $page_count = ceil( $count_data / 500 );
                        //echo 'page_count' . $page_count;

                       

                        //ページ数毎にfor文を回す
                        for ( $page = 0; $page < $page_count; $page++ ) {
                            
                            $target_page = $page + 1;
                            
                                $url = 'https://adv.a8.net/a8v2/ecAsRankingReportAction.do?reportType=11&insId=' . $product_info->asp_product_id . '&asmstId=&termType=1&d-2037996-p=' . $target_page . '&multiSelectFlg=0&year=' . $s_Y . '&month=' . $s_M;
                                
                                //echo $url;
                                
                                $crawler_for_site = $browser->visit( $url )->crawler();
                                
                                $count_deff = intval( $count_data ) - ( 500 * $page );
                                
                                $count_deff = ( intval( $count_deff ) > 500 ) ? 500 : intval( $count_deff );
                                
                                //echo "サイト数＞" . $count_data;
                                //echo $page . "ページのサイト数＞" . $count_deff;
                                
                                for ( $i = 1; $i <= $count_deff; $i++ ) {
                                    
                                    $count = $i + ( 500 * $page );
                                    
                                    $selector_for_site = array(
                                        'media_id'  => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                                        'site_name' => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                                        'imp'       => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(5)',
                                        'click'     => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(6)',
                                        'cv'        => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(10)',
                                        'price'     => '#ReportList > tbody > tr:nth-child(' . $i . ') > td:nth-child(13)' 
                                    );
                                    
                                    foreach ( $selector_for_site as $key => $value ) {
                                        
                                        if(count($crawler_for_site->filter( $value ))){
                                            if ( $key == 'media_id' || $key == 'site_name'){
                                                    
                                                $a8_site[ $count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                                
                                            }else{
                                                $a8_site[ $count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                            }
                                        
                                        }else{
                                            throw new \Exception($value.'要素が存在しません。');
                                        }
                                    } //$selector_for_site as $key => $value
                                    
                                    // $unit_price = $product_info->price;
                                    // $a8_site[ $count ][ 'price' ] = $unit_price * $a8_site[ $count ][ 'cv' ];
                                    
                                    $calculated = json_decode( 
                                        json_encode( 
                                            json_decode( 
                                                $this->dailySearchService
                                                ->cpa( $a8_site[ $count ][ 'cv' ], $a8_site[ $count ][ 'price' ], 1 ) 
                                                ) 
                                            ), True );
                                            
                                    //$a8_site[$count]['product'] = $product_info->id;
                                    $a8_site[ $count ][ 'asp' ]   = $product_info->asp_id;
                                    $a8_site[ $count ][ 'product' ] = $product_info->id;
                                    $a8_site[ $count ][ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                                            
                                    $a8_site[ $count ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                                    $a8_site[ $count ][ 'cost' ] = $calculated[ 'cost' ]; //獲得単価
                                    
                                }
                            }
                            
                            //１サイトずつサイト情報の登録を実行
                            $this->dailySearchService->save_site( json_encode( $a8_site ) );
                            $this->dailySearchService->save_daily( json_encode( $a8_data_1 ) );
                            
                            
                        }

                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'A8',
                                'type' => 'Daily',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                                //throw $e;
                }
            } );
        }
    }
}