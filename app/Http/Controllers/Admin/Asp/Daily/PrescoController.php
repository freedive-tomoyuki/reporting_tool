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

class PrescoController extends DailyCrawlerController
{
    
    /**
    * Presco
    */
    public function affitown( $product_base_id ) //OK
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
        $product_id = $this->dailySearchService->BasetoProduct( 7, $product_base_id );
        
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
                        $s_date = date( 'Ymd', strtotime( 'first day of previous month' ) );
                        $e_date = date( 'Ymd', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                        $s_date = date( 'Ym01' );
                        $e_date = date( 'Ymd', strtotime( '-1 day' ) );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        /*
                        クロール：ログイン＝＞[日別売上検索]より検索
                        */
                        
                        \Log::info($product_info->asp_product_id);
                        \Log::info($s_date);
                        \Log::info($e_date);
                        $crawler = $browser->visit( 'https://presco.ai/merchant/auth/loginForm' )
                                            ->type( '#content > div > div > div.w40-per.mt100.loginForm > form > table > tbody > tr:nth-child(1) > td > div > input[type=text]', $product_info->login_value )
                                            ->type( '#content > div > div > div.w40-per.mt100.loginForm > form > table > tbody > tr:nth-child(2) > td > div > input', $product_info->password_value )
                                            ->click( '#content > div > div > div.w40-per.mt100.loginForm > form > div.m-news-body.align-center.mt40 > p:nth-child(1) > input' )
                                            ->visit( "https://presco.ai/merchant/home/" )
                                            ->visit( "https://affi.town/adserver/merchant/report/dailysales.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&since=" . $s_date . "&until=" . $e_date )
                                            ->type( '#all_display > p > input[type=search]', '合計' )
                                            ->crawler();
                        //echo $crawler->html();

                        $crawler2 = $browser->visit( "https://affi.town/adserver/report/mc/impression.af" )
                                            ->visit( "https://affi.town/adserver/report/mc/impression.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&fromDate=" . $s_date . "&toDate=" . $e_date )
                                            ->type( '#all_display > p > input[type=search]', '合計' )
                                            ->crawler();
                        //echo $crawler2->html();
                        /*
                        selector 設定
                        */
                        $selector1 = array(
                            'click' => '#all_display > table > tbody > tr.visible.striped > td:nth-child(5)',
                            'cv' => '#all_display > table > tbody > tr.visible.striped > td:nth-child(6)',
                            //'price' => '#all_display > table > tbody > tr.visible.striped > td:nth-child(7)' 
                        );
                        
                        /*
                        selector Imp 設定
                        */
                        $selector2 = array(
                             'imp' => '#all_display > table > tbody:nth-child(2) > tr.visible.striped > td:nth-child(5)',
                        );
                        
                        /*
                        $crawler　をフィルタリング
                        */
                        $affitown_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                        {
                            
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            //$data['imp'] = 0;
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
                        //var_dump( $affitown_data );

                        /*
                        $crawler(Imp)　をフィルタリング
                        */
                        $affitown_data_imp = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                        {
                            
                            $data              = array( );
                            
                            foreach ( $selector2 as $key => $value ) {
                                if(count($node->filter( $value ))){
                                    $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            } //$selector1 as $key => $value
                            return $data;
                            
                        } );
                        //var_dump( $affitown_data_imp );
                        
                        /*
                        サイト抽出　
                        */
                        $crawler_for_count_site = $browser->visit( "https://affi.town/adserver/merchant/join.af?joinApprove=2" )->crawler();
                        
                        $site_count = 1;
                        
                        while ( $crawler_for_count_site->filter( '#form_link_approval > table > tbody > tr:nth-child(' . $site_count . ') > td:nth-child(2)' )->count() == 1 ) {
                            $site_count++;
                        }
                        //echo 'サイト件数：'.$site_count;
                        $site_count--;
                        //echo "カウントここ" . $site_count . "カウントここ";
                        
                        $crawler_for_site = $browser->visit( "https://affi.town/adserver/report/mc/site.af?advertiseId=" . $product_info->asp_product_id . "&fromDate=" . $s_date . "&toDate=" . $e_date )->crawler();
                            // ->type( '#all_display > p > input[type=search]', '合計' )->crawler();
                        $i                = 1;
                        //$selector_end = ;
                        //echo $crawler_for_site->html();
                        // #all_display > table > tbody > tr.last > td:nth-child(2) > a

                        // サイト一覧の「合計」以外の前列を1列目から最終列まで一行一行スクレイピング
                        while ( ($crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(1)' )->text()) !== '' ) {
                            //echo $i;
                            
                            $affitown_site[ $i ][ 'product' ] = $product_info->id;
                            $affitown_site[ $i ][ 'asp' ]   = $product_info->asp_id;
                            $affitown_site[ $i ][ 'imp' ]     = 0;
                            
                            $selector_for_site = array(
                                'media_id'  => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(1)',
                                'site_name' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                                'click'     => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                                'cv'        => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(5)',
                                //'price' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(6) > p' 
                            );
                            
                            foreach ( $selector_for_site as $key => $value ) {
                                if(count($crawler_for_site->filter( $value ))){
                                    if ( $key == 'site_name' ) {
                                        $affitown_site[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                    }
                                    else {
                                        $affitown_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                    }
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            }
                            $unit_price = $product_info->price;
                            $affitown_site[ $i ][ 'price' ] = $unit_price * $affitown_site[ $i ][ 'cv' ];

                            $calculated                       = json_decode( 
                                                                    json_encode( 
                                                                        json_decode( 
                                                                            $this->dailySearchService
                                                                                   ->cpa( $affitown_site[ $i ][ 'cv' ], $affitown_site[ $i ][ 'price' ], 7 ) 
                                                                        ) 
                                                                    ), True );
                            $affitown_site[ $i ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                            $affitown_site[ $i ][ 'cost' ] = $calculated[ 'cost' ];
                            $affitown_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            $i++;
                            
                        } 
                        //var_dump($affitown_site);
                        $unit_price = $product_info->price;
                        $affitown_data[ 0 ][ 'price' ] = $affitown_data[ 0 ][ 'cv' ] * $unit_price;

                        $affitown_data[ 0 ][ 'partnership' ] = $site_count;
                        $affitown_data[ 0 ][ 'active' ] = $i; //一覧をクロールした行数をサイト数としてカウント
                        $affitown_data[ 0 ][ 'imp' ] = $affitown_data_imp[ 0 ][ 'imp' ];

                        $calculated                      = json_decode( 
                                                                json_encode( 
                                                                    json_decode( 
                                                                        $this->dailySearchService
                                                                            ->cpa( $affitown_data[ 0 ][ 'cv' ], $affitown_data[ 0 ][ 'price' ], 7 ) 
                                                                        ) ), True );
                        $affitown_data[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        $affitown_data[ 0 ][ 'cost' ] = $calculated[ 'cost' ];


                        //echo "<pre>";
                        //var_dump( $affitown_data );
                        //var_dump( $affitown_site );
                        //echo "</pre>";

                        /*
                        サイトデータ・日次データ保存
                        */
                        $this->dailySearchService->save_site( json_encode( $affitown_site ) );
                        $this->dailySearchService->save_daily( json_encode( $affitown_data ) );
                        
                        //var_dump($crawler_for_site);
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'アフィタウン',
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            
            }
            
        } );
        
    }
}