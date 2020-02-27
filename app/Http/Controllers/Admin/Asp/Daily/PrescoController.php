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
    public function presco( $product_base_id ) //OK
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
        $product_id = json_decode($this->dailySearchService->BasetoProduct( 14, $product_base_id ),true);
        
        /*
        Chromeドライバーのインスタンス呼び出し
        */
        $client = new Client( new Chrome( $options ) );
        foreach($products as $p ){
            
            $product_id = $p['id'];
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
                            $crawler = $browser->visit( $product_info->asp->login_url )
                                                ->type( $product_info->asp->login_key, $product_info->login_value )
                                                ->type( $product_info->asp->password_key, $product_info->password_value )
                                                ->click( $product_info->asp->login_selector )
                                                ->visit( $product_info->asp->lp1_url )
                                                ->visit( "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on" )
                                                ->crawler();
                            //echo $crawler->html();
                            //アクティブ
                            $crawler2 = $browser->visit(  "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on"  )->crawler();
                            
                            $crawler3 = $browser->visit(  "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=1&_searchJoinType=on"  )->crawler();
                            //echo $crawler2->html();
                            
                            \Log::info("https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=0&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=0&_searchJoinType=on" );
                            //\Log::info($crawler);

                            /*
                            selector 設定
                            */
                            $selector1 = array(
                                'click'     => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(2) > div > div',
                                'cv'        => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(3) > div > div',
                                'approval_price' => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(6)',
                                'price'     => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(14)' 
                            );
                            
                            /*
                            selector アクティブ数/提携数 設定
                            */

                            $active_partnership_selector = '#reportTable_info > div > span.num';
                            /*
                            $crawler　をフィルタリング
                            */
                            $presco_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                            {
                                
                                $data              = array( );
                                $data[ 'asp' ]     = $product_info->asp_id;
                                $data[ 'product' ] = $product_info->id;
                                $data[ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                                $data[ 'imp' ] = 0;

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
                            $crawler(Active)　をフィルタリング
                            */
                            if(count($crawler2->filter( $active_partnership_selector ))){
                                $presco_data[ 0 ][ 'active' ] = trim( preg_replace( '/[^0-9]/', '', $crawler2->filter( $active_partnership_selector )->text() ) );
                            }else{
                                throw new \Exception('アクティブ数の要素が存在しません。');
                            }
                            if(count($crawler3->filter( $active_partnership_selector ))){
                                $presco_data[ 0 ][ 'partnership' ] = trim( preg_replace( '/[^0-9]/', '', $crawler3->filter( $active_partnership_selector )->text() ) );
                            }else{
                                throw new \Exception('提携数の要素が存在しません。');
                            }

                            var_dump( $presco_data );
                            \Log::info($presco_data);
                            /*
                            サイト抽出　
                            */

                            // count == 0 →　アクティブ数
                            // count == 1 →　提携数
                            
                            $cnt_site = $presco_data[ 0 ][ 'partnership' ];
                            \Log::info($cnt_site);
                            // $active_partnership_selector = '#reportTable_info > div > span';
                            // $presco_data[$count] = $crawler_for_count_site->filter( $active_partnership_selector )->text();
                            $i = 1;
                            //サイトのスクレイピングは２回目以降
                            $crowle_url_for_site = "https://presco.ai/merchant/report/search?searchPeriodType=2&searchDateType=2&searchDateTimeFrom=" . $s_date . "&searchDateTimeTo=" . $e_date . "&searchItemType=2&searchLargeGenreId=&searchMediumGenreId=&searchSmallGenreId=&searchProgramId=" . $product_info->asp_product_id . "&searchProgramUrlId=&searchPartnerSiteId=&searchPartnerSitePageId=&searchJoinType=1&_searchJoinType=on";
                            
                            if( $cnt_site > 10 ){
                                $crawler_for_site = $browser->visit( $crowle_url_for_site )->select('reportTable_length', '100')->crawler();
                            }else{
                                $crawler_for_site = $browser->visit( $crowle_url_for_site )->crawler();
                            }

                            // サイト一覧の「合計」以外の前列を1列目から最終列まで一行一行スクレイピング
                                while ( $crawler_for_site->filter( '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(5) > div > div' )->count() > 0  ) {
                                    echo 'point'.$i;
                                    
                                    $presco_site[ $i ][ 'product' ] = $product_info->id;
                                    $presco_site[ $i ][ 'asp' ]   = $product_info->asp_id;
                                    $presco_site[ $i ][ 'imp' ]     = 0;
                                    
                                    $selector_for_site = array(
                                        'media_id'  => '#reportTable_wrapper > div.m-table--list-container > div > div > div.DTFC_LeftWrapper > div.DTFC_LeftBodyWrapper > div > table > tbody > tr:nth-child(' . $i . ') > td.sorting_1.sorting_2 > div > div',
                                        'site_name' => '#reportTable_wrapper > div.m-table--list-container > div > div > div.DTFC_LeftWrapper > div.DTFC_LeftBodyWrapper > div > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > div > div',
                                        'click'     => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(5) > div > div',
                                        'cv'        => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(6) > div > div',
                                        'price'     => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(17)' ,
                                        'approval_price'     => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(9)' ,

                                    );
                                    
                                    foreach ( $selector_for_site as $key => $value ) {
                                        if(count($crawler_for_site->filter( $value ))){
                                            if ( $key == 'site_name' ) {
                                                $presco_site[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                            }
                                            else {
                                                $presco_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                            }
                                        }else{
                                            throw new \Exception($value.'要素が存在しません。');
                                        }
                                    }
                                    // $unit_price = $product_info->price;
                                    // $presco_site[ $i ][ 'price' ] = $unit_price * $presco_site[ $i ][ 'cv' ];

                                    $calculated                       = json_decode( 
                                                                            json_encode( 
                                                                                json_decode( 
                                                                                    $this->dailySearchService
                                                                                        ->cpa( $presco_site[ $i ][ 'cv' ], $presco_site[ $i ][ 'price' ], 14 ) 
                                                                                ) 
                                                                            ), True );
                                    $presco_site[ $i ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                                    $presco_site[ $i ][ 'cost' ] = $calculated[ 'cost' ];
                                    $presco_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                    
                                    $i++;
                                    
                                }

                            $calculated                      = json_decode(
                                                                    json_encode(
                                                                        json_decode(
                                                                            $this->dailySearchService
                                                                                ->cpa( $presco_data[ 0 ][ 'cv' ], $presco_data[ 0 ][ 'price' ], 14 ) 
                                                                            ) ), True );
                            $presco_data[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                            $presco_data[ 0 ][ 'cost' ] = $calculated[ 'cost' ];

                            // echo 'point100';
                            // echo "<pre>";
                            // var_dump( $presco_data );
                            // var_dump( $presco_site );
                            // echo "</pre>";

                            /*
                            サイトデータ・日次データ保存
                            */
                            $this->dailySearchService->save_site( json_encode( $presco_site ) );
                            $this->dailySearchService->save_daily( json_encode( $presco_data ) );
                            
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
}