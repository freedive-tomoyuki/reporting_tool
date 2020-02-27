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

class MoshimoController extends DailyCrawlerController
{
    
    /**
    * Moshimo
    */
    public function moshimo( $product_base_id ) //OK
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
        $product_id = $this->dailySearchService->BasetoProduct( 13, $product_base_id );
        
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
                        $crawler = $browser->visit( $product_info->asp->login_url )
                                            ->type( $product_info->asp->login_key, $product_info->login_value )
                                            ->type( $product_info->asp->password_key, $product_info->password_value )
                                            ->click( $product_info->asp->login_selector )
                                            ->visit( "https://secure.moshimo.com/af/merchant/index" )
                                            ->crawler();
                        //echo $crawler->html();
                        echo "クロールクリア";
                        // $crawler2 = $browser->visit( "https://affi.town/adserver/report/mc/impression.af" )
                        //                     ->visit( "https://affi.town/adserver/report/mc/impression.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&fromDate=" . $s_date . "&toDate=" . $e_date )
                        //                     ->type( '#all_display > p > input[type=search]', '合計' )
                        //                     ->crawler();
                        //echo $crawler2->html();
                        //https://affi.town/adserver/report/mc/impression.af?advertiseId=4316&mediaId=&since=2019-07-01&until=2019-07-27
                        /*
                        selector 設定
                        */
                        // $selector1 = array(
                        //     'imp' => '#report > div.result > table > tbody > tr:nth-child(1) > td.value-pv > div > p',
                        //     'click' => '#report > div.result > table > tbody > tr:nth-child(1) > td.value-click > div > p:nth-child(1)',
                        //     'cv' => '#report > div.result > table > tbody > tr:nth-child(1) > td.value-result > div > p:nth-child(1)',
                        //     'price' => '#report > div.result > table > tbody > tr:nth-child(1) > td.value-result > div > p:nth-child(2)' 
                        // );
                        
                        /*
                        selector Imp 設定
                        // */
                        // $selector2 = array(
                        //      'imp' => '#all_display > table > tbody:nth-child(2) > tr.visible.striped > td:nth-child(5)',
                        // );
                        
                        /*
                        $crawler　をフィルタリング
                        */
                        // $affitown_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                        // {
                            
                        //     $data              = array( );
                        //     $data[ 'asp' ]     = $product_info->asp_id;
                        //     $data[ 'product' ] = $product_info->id;
                        //     //$data['imp'] = 0;
                        //     $data[ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                        //     foreach ( $selector1 as $key => $value ) {
                        //         if(count($node->filter( $value ))){
                        //             $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        //         }else{
                        //             throw new \Exception($value.'要素が存在しません。');
                        //         }
                        //     } //$selector1 as $key => $value
                        //     return $data;
                            
                        // } );
                        //var_dump( $affitown_data );

                        /*
                        $crawler(Imp)　をフィルタリング
                        */
                        // $affitown_data_imp = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                        // {
                            
                        //     $data              = array( );
                            
                        //     foreach ( $selector2 as $key => $value ) {
                        //         if(count($node->filter( $value ))){
                        //             $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        //         }else{
                        //             throw new \Exception($value.'要素が存在しません。');
                        //         }
                        //     } //$selector1 as $key => $value
                        //     return $data;
                            
                        // } );
                        //var_dump( $affitown_data_imp );
                        
                        /*
                        サイト抽出　
                        */
                        $i =  1;
                        $url = "https://secure.moshimo.com/af/merchant/report/kpi/site?promotion_id=" . $product_info->asp_product_id . "&from_date=" . $s_date . "&to_date=" . $e_date ;
                        $crawler = $browser->visit( $url )->crawler();
                        
                            
                        $moshimo_data[0][ 'asp' ]     = $product_info->asp_id;
                        $moshimo_data[0][ 'product' ] = $product_info->id;
                        $moshimo_data[0][ 'date' ]       = date( 'Y-m-d', strtotime( '-1 day' ) );
                        echo "２クロールクリア";

                        // サイト一覧の「合計」以外の前列を1列目から最終列まで一行一行スクレイピング
                        while ( $crawler->filter( '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-name > div > p:nth-child(1) > a' )->count() > 0 ) {
                            //echo $i;
                            echo "ループクロール中(".$i.")";
                            
                            $moshimo_site[ $i ][ 'product' ] = $product_info->id;
                            $moshimo_site[ $i ][ 'asp' ]   = $product_info->asp_id;
                            // $affitown_site[ $i ][ 'imp' ]     = 0;
                            
                            $selector_for_site = array(
                                'media_id'  => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-name > div > p:nth-child(1)',
                                'site_name' => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-name > div > p:nth-child(1) > a',
                                'imp'     => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-pv > div > p',
                                'click'     => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-click > div > p:nth-child(1)',
                                'cv'        => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-result > div > p:nth-child(1)',
                                'price'        => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-result > div > p:nth-child(2)',
                            );
                            
                            foreach ( $selector_for_site as $key => $value ) {
                                echo "Filterループクロール中(".$key.")";

                                if(count($crawler->filter( $value ))){
                                    if ( $key == 'site_name' ) {
                                        $moshimo_site[ $i ][ $key ] = trim( $crawler->filter( $value )->text() );
                                    }elseif($key == 'media_id' ){
                                        $member_id_array = array( );
                                        $member_id_source = $crawler->filter($value)->each(function (Crawler $c) {
                                            return $c->attr('id');
                                        });
                                        preg_match( '/(\d+)/', $member_id_source[0], $member_id_array );
                                        $moshimo_site[$i][$key] = $member_id_array[ 1 ];
                                    }
                                    
                                    else {
                                        $moshimo_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler->filter( $value )->text() ) );
                                        $moshimo_data[0][ 'imp' ]   += ($key == 'imp' && is_numeric($moshimo_site[ $i ][ $key ]))? $moshimo_site[ $i ][ $key ] : 0;
                                        $moshimo_data[0][ 'click' ] += ($key == 'click' && is_numeric($moshimo_site[ $i ][ $key ]))? $moshimo_site[ $i ][ $key ] : 0;
                                        $moshimo_data[0][ 'cv' ]    += ($key == 'cv' && is_numeric($moshimo_site[ $i ][ $key ]))? $moshimo_site[ $i ][ $key ] : 0;
                                        $moshimo_data[0][ 'price' ] += ($key == 'imp' && is_numeric($moshimo_site[ $i ][ $key ]))? $moshimo_site[ $i ][ $key ] : 0;
                                    }
                                }else{
                                    throw new \Exception($value.'要素が存在しません。');
                                }
                            }
                            echo "Filterループクロール済";
                            $calculated                       = json_decode( 
                                                                    json_encode( 
                                                                        json_decode( 
                                                                            $this->dailySearchService
                                                                                   ->cpa( $moshimo_site[ $i ][ 'cv' ], $moshimo_site[ $i ][ 'price' ], 7 ) 
                                                                        ) 
                                                                    ), True );
                            $moshimo_site[ $i ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                            $moshimo_site[ $i ][ 'cost' ] = $calculated[ 'cost' ];
                            $moshimo_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            $i++;
                            
                        } 
                        //var_dump($affitown_site);
                        
                        // $moshimo_data[ 0 ][ 'partnership' ] = $site_count;
                        // $moshimo_data[ 0 ][ 'active' ] = $i; //一覧をクロールした行数をサイト数としてカウント

                        // $calculated                      = json_decode( 
                        //                                         json_encode( 
                        //                                             json_decode( 
                        //                                                 $this->dailySearchService
                        //                                                     ->cpa( $moshimo_data[ 0 ][ 'cv' ], $moshimo_data[ 0 ][ 'price' ], 13 ) 
                        //                                                 ) ), True );
                        // $moshimo_data[ 0 ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                        // $moshimo_data[ 0 ][ 'cost' ] = $calculated[ 'cost' ];


                        //echo "<pre>";
                        var_dump( $moshimo_data );
                        var_dump( $moshimo_site );
                        //echo "</pre>";

                        /*
                        サイトデータ・日次データ保存
                        // */
                        // $this->dailySearchService->save_site( json_encode( $moshimo_site ) );
                        // $this->dailySearchService->save_daily( json_encode( $moshimo_data ) );
                        
                        //var_dump($crawler_for_site);
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'もしも',
                            'type' => 'Daily',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            
            }
            
        } );
        
    }
}