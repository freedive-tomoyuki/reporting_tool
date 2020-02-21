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
        $product_id = $this->dailySearchService->BasetoProduct( 14, $product_base_id );
        
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
                        $s_date = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                        $e_date = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                        $s_date = date( 'Y/m/01' );
                        $e_date = date( 'Y/m/d', strtotime( '-1 day' ) );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        // /var_dump($product_info->asp);
                        /*
                        クロール：ログイン＝＞[日別売上検索]より検索
                        */
                        
                        \Log::info($product_info->asp_product_id);
                        \Log::info($s_date);
                        \Log::info($e_date);
                        $crawler = $browser->visit($product_info->asp->login_url )
                                            ->type( $product_info->asp->login_key, $product_info->login_value )
                                            ->type( $product_info->asp->password_key, $product_info->password_value )
                                            ->click( $product_info->asp->login_selector )
                                            ->visit( $asp_info[0]['lp1_url'] )
                                            ->visit( "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on" )
                                            ->crawler();
                        //echo $crawler->html();
                        //アクティブ
                        $crawler2 = $browser->visit(  "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on"  )->crawler();
                        
                        $crawler3 = $browser->visit(  "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=1&_searchJoinType=on"  )->crawler();
                        //echo $crawler2->html();
                        /*
                        selector 設定
                        */
                        $selector1 = array(
                            'click'     => '#reportTable > tbody > tr > td:nth-child(2) > div > div',
                            'cv'        => '#reportTable > tbody > tr > td:nth-child(3) > div > div',
                            'approval_price' => '#reportTable > tbody > tr > td:nth-child(6)',
                            'price'     => '#reportTable > tbody > tr > td:nth-child(14)' 
                        );
                        
                        /*
                        selector アクティブ数 設定
                        */
                        $selector2 = array(
                             'active' => '#reportTable_info > div > span.num',
                        );
                        /*
                        selector 提携数 設定
                        */
                        $selector3 = array(
                            'partnership' => '#reportTable_info > div > span.num',
                       );
                       
                        /*
                        $crawler　をフィルタリング
                        */
                        $presco_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
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
                        //var_dump( $affitown_data );

                        /*
                        $crawler(Imp)　をフィルタリング
                        */
                        $presco_data2 = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
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
                        /*
                        $crawler(Imp)　をフィルタリング
                        */
                        $presco_data3 = $crawler2->each( function( Crawler $node ) use ($selector3, $product_info)
                        {
                            
                            $data              = array( );
                            
                            foreach ( $selector3 as $key => $value ) {
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
                        // $crawler_for_count_site = $browser->visit( "https://affi.town/adserver/merchant/join.af?joinApprove=2" )->crawler();
                        
                        // $site_count = 1;
                        
                        // while ( $crawler_for_count_site->filter( '#form_link_approval > table > tbody > tr:nth-child(' . $site_count . ') > td:nth-child(2)' )->count() == 1 ) {
                        //     $site_count++;
                        // }
                        // //echo 'サイト件数：'.$site_count;
                        // $site_count--;
                        // //echo "カウントここ" . $site_count . "カウントここ";
                        
                        // $crawler_for_site = $browser->visit( "https://affi.town/adserver/report/mc/site.af?advertiseId=" . $product_info->asp_product_id . "&fromDate=" . $s_date . "&toDate=" . $e_date )->crawler();
                        //     // ->type( '#all_display > p > input[type=search]', '合計' )->crawler();
                        // $i                = 1;
                        // //$selector_end = ;
                        // //echo $crawler_for_site->html();
                        // // #all_display > table > tbody > tr.last > td:nth-child(2) > a

                        // // サイト一覧の「合計」以外の前列を1列目から最終列まで一行一行スクレイピング
                        // while ( ($crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(1)' )->text()) !== '' ) {
                        //     //echo $i;
                            
                        //     $affitown_site[ $i ][ 'product' ] = $product_info->id;
                        //     $affitown_site[ $i ][ 'asp' ]   = $product_info->asp_id;
                        //     $affitown_site[ $i ][ 'imp' ]     = 0;
                            
                        //     $selector_for_site = array(
                        //         'media_id'  => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(1)',
                        //         'site_name' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                        //         'click'     => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4)',
                        //         'cv'        => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(5)',
                        //         //'price' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(6) > p' 
                        //     );
                            
                        //     foreach ( $selector_for_site as $key => $value ) {
                        //         if(count($crawler_for_site->filter( $value ))){
                        //             if ( $key == 'site_name' ) {
                        //                 $affitown_site[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                        //             }
                        //             else {
                        //                 $affitown_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                        //             }
                        //         }else{
                        //             throw new \Exception($value.'要素が存在しません。');
                        //         }
                        //     }
                        //     $unit_price = $product_info->price;
                        //     $affitown_site[ $i ][ 'price' ] = $unit_price * $affitown_site[ $i ][ 'cv' ];

                        //     $calculated                       = json_decode( 
                        //                                             json_encode( 
                        //                                                 json_decode( 
                        //                                                     $this->dailySearchService
                        //                                                            ->cpa( $affitown_site[ $i ][ 'cv' ], $affitown_site[ $i ][ 'price' ], 7 ) 
                        //                                                 ) 
                        //                                             ), True );
                        //     $affitown_site[ $i ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        //     $affitown_site[ $i ][ 'cost' ] = $calculated[ 'cost' ];
                        //     $affitown_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                        //     $i++;
                            
                        // } 
                        // //var_dump($affitown_site);
                        // $unit_price = $product_info->price;
                        // $affitown_data[ 0 ][ 'price' ] = $affitown_data[ 0 ][ 'cv' ] * $unit_price;

                        // $affitown_data[ 0 ][ 'partnership' ] = $site_count;
                        // $affitown_data[ 0 ][ 'active' ] = $i; //一覧をクロールした行数をサイト数としてカウント
                        // $affitown_data[ 0 ][ 'imp' ] = $affitown_data_imp[ 0 ][ 'imp' ];

                        // $calculated                      = json_decode( 
                        //                                         json_encode( 
                        //                                             json_decode( 
                        //                                                 $this->dailySearchService
                        //                                                     ->cpa( $affitown_data[ 0 ][ 'cv' ], $affitown_data[ 0 ][ 'price' ], 7 ) 
                        //                                                 ) ), True );
                        // $affitown_data[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        // $affitown_data[ 0 ][ 'cost' ] = $calculated[ 'cost' ];


                        //echo "<pre>";
                        //var_dump( $affitown_data );
                        //var_dump( $affitown_site );
                        //echo "</pre>";

                        /*
                        サイトデータ・日次データ保存
                        */
                        // $this->dailySearchService->save_site( json_encode( $affitown_site ) );
                        // $this->dailySearchService->save_daily( json_encode( $affitown_data ) );
                        
                        //var_dump($crawler_for_site);
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'プレスコ',
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            
            }
            
        } );
        
    }
}