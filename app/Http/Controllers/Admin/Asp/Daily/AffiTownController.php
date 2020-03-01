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

class AffiTownController extends DailyCrawlerController
{
    
    /**
    AffTown
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
        $products = json_decode($this->dailySearchService->BasetoProduct( 7, $product_base_id ),true);
        
        /*
        Chromeドライバーのインスタンス呼び出し
        */
        $client = new Client( new Chrome( $options ) );
        foreach($products as $p ){
            
            $product_id = $p['id'];
            $product_name = $p['product'];

            /*
            Chromeドライバー実行
            　引数
            　　$product_id:案件ID
            */
            $client->browse( function( Browser $browser ) use (&$crawler, $product_id, $product_name)
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
                        $ck_date = date( 'Ym', strtotime( '-1 day' ) );

                        echo 'show before product_infos loop';
                        var_dump($product_infos);
                        
                        foreach ( $product_infos as $product_info ) {
                            
                            /*
                            クロール：ログイン＝＞[日別売上検索]より検索
                            */
                            \Log::info($product_info->asp_product_id);
                            \Log::info($s_date);
                            \Log::info($e_date);
                        


                            $check_before_crawler = $browser->visit( $product_info->asp->login_url )
                                                ->type( $product_info->asp->login_key, $product_info->login_value )
                                                ->type( $product_info->asp->password_key, $product_info->password_value )
                                                ->click( $product_info->asp->login_selector )
                                                ->visit( "https://affi.town/adserver/report/mc/monthly.af?fromDate=" . $ck_date . "&toDate=" . $ck_date )
                                                ->crawler();
                                                
                                                $click_count = $check_before_crawler->filter('#all_display > table > tbody > tr > td:nth-child(2)')->text();
                                                $cv_count = $check_before_crawler->filter('#all_display > table > tbody > tr > td:nth-child(3)')->text();
                                                \Log::info($click_count);
                                                \Log::info($cv_count);

                                                
                            if($click_count == 0 && $cv_count == 0){
                                \Log::info('全部０');         
                                $affitown_data[ 0 ][ 'asp' ]     = $product_info->asp_id;
                                $affitown_data[ 0 ][ 'product' ] = $product_info->id;
                                $affitown_data[ 0 ][ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                                $affitown_data[ 0 ][ 'active' ] = 0;
                                $affitown_data[ 0 ][ 'partnership' ] = 0;
                                $affitown_data[ 0 ][ 'imp' ] = 0;
                                $affitown_data[ 0 ][ 'click' ] = 0;
                                $affitown_data[ 0 ][ 'cv' ] = 0;
                                $affitown_data[ 0 ][ 'price' ] = 0;
                                $affitown_data[ 0 ][ 'cost' ] = 0;
                                $affitown_data[ 0 ][ 'cpa' ] = 0;

                            }else{
                                \Log::info('スクレイピング');         

                                $crawler = $browser->visit( "https://affi.town/adserver/merchant/report/dailysales.af" )
                                                    ->visit( "https://affi.town/adserver/merchant/report/dailysales.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&since=" . $s_date . "&until=" . $e_date )
                                                    ->type( '#all_display > p > input[type=search]', '合計' )
                                                    ->crawler();
                                //echo $crawler->html();

                                $crawler2 = $browser->visit( "https://affi.town/adserver/report/mc/impression.af" )
                                                    ->visit( "https://affi.town/adserver/report/mc/impression.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&fromDate=" . $s_date . "&toDate=" . $e_date )
                                                    ->type( '#all_display > p > input[type=search]', '合計' )
                                                    ->crawler();
                                //echo $crawler2->html();
                                //https://affi.town/adserver/report/mc/impression.af?advertiseId=4316&mediaId=&since=2019-07-01&until=2019-07-27
                                /*
                                selector 設定
                                */
                                $selector1 = array(
                                    'click' => '#all_display > table > tbody > tr.visible.striped > td:nth-child(5)',
                                    'cv' => '#all_display > table > tbody > tr.visible.striped > td:nth-child(6)',
                                    'price' => '#all_display > table > tbody > tr.visible.striped > td:nth-child(7)' 
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
                                        'price'     => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(6)' 
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
                                    // $unit_price = $product_info->price;
                                    // $affitown_site[ $i ][ 'price' ] = $unit_price * $affitown_site[ $i ][ 'cv' ];

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
                            
                                //日別のデータが０ではない時にサイトにレコードを挿入
                                $this->dailySearchService->save_site( json_encode( $affitown_site ) );
                            
                            }

                            $this->dailySearchService->save_daily( json_encode( $affitown_data ) );
                            
                            
                            //echo "<pre>";
                            //var_dump( $affitown_data );
                            //var_dump( $affitown_site );
                            //echo "</pre>";

                            /*
                            サイトデータ・日次データ保存
                            */
                            
                            
                            //var_dump($crawler_for_site);
                        } //$product_infos as $product_info
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'アフィタウン',
                                'type' => 'Daily',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                
                }
                
            } );
        }
    }
}