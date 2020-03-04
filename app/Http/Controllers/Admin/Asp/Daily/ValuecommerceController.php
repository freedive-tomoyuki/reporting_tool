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

class ValuecommerceController extends DailyCrawlerController
{
    
    public function valuecommerce( $product_base_id )
    {
        
        Browser::macro( 'crawler', function( )
        {
              return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
        } );
        
        $options = [
        '--window-size=1920,1080',
        '--start-maximized',
        '--headless',
        '--lang=ja_JP',
        '--disable-gpu',
        ];
        $products = json_decode($this->dailySearchService->BasetoProduct( 3, $product_base_id ),true);
        
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );

        foreach($products as $p ){
            
            $product_id = $p['id'];
            $product_name = $p['product'];
            //Chromeドライバー実行
            $client->browse( function( Browser $browser ) use (&$crawler, $product_id, $product_name)
            {
                try{
                        //$product_infos = \App\Product::all()->where('id',$product_id);
                        $product_infos = \App\Product::all()->where( 'id', $product_id );
                        
                        //クロール実行が1日のとき
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $s_year = date( 'Y', strtotime( '-1 day' ) );
                            $s_month = date( 'n', strtotime( '-1 day' ) );
                            $s_date = date( 'Y-m-d', strtotime( 'first day of previous month' ) );
                            $e_date = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $s_year = date( 'Y' );
                            $s_month = date( 'n' );
                            $s_date = date( 'Y-m-01' );
                            $e_date = date( 'Y-m-d', strtotime( '-1 day' ) );
                        }
                        
                        foreach ( $product_infos as $product_info ) {
                            
                            $crawler = $browser->visit( $product_info->asp->login_url )
                                                ->type( $product_info->asp->login_key, $product_info->login_value )
                                                ->type( $product_info->asp->password_key, $product_info->password_value )
                                                ->click( $product_info->asp->login_selector )
                                                ->visit('https://mer.valuecommerce.ne.jp/switch/'.$product_info->asp_sponsor_id.'/')
                                                ->visit( 'https://mer.valuecommerce.ne.jp/report/network_statistics' )
                                                ->select( '#condition_fromDate', $s_date )
                                                ->select( '#condition_toDate', $e_date )
                                                ->click( '#show_statistics' )
                                                ->crawler();

                                $selector_crawler = array(
                                    'imp'       => '#report > tfoot > tr > td.impressions',
                                    'click'     => '#report > tfoot > tr > td:nth-child(6)',
                                    'cv'        => '#report > tfoot > tr > td:nth-child(9)',
                                    'partnership' => '#report > tbody > tr:last-child > td:nth-child(2)',
                                    // 'price'     => $product_info->asp->daily_price_selector,
                                );
                                \Log::info($s_date);
                                \Log::info($e_date);
                                \Log::info($crawler->html());
                            var_dump($crawler->html());
                            // echo "point1";
                            // if(date( 'Y/m/d' ) == date( 'Y/m/01' )){

                            //     $crawler->visit( $product_info->asp->lp1_url )->crawler();

                            //     $selector_crawler = array(
                            //         'imp'       => '#report > tbody > tr:nth-child(4) > td:nth-child(5)',
                            //         'click'     => '#report > tbody > tr:nth-child(4) > td:nth-child(8)',
                            //         'cv'        => '#report > tbody > tr:nth-child(4) > td:nth-child(11)',
                            //         'partnership' => '#report > tbody > tr:nth-child(4) > td:nth-child(2)',
                            //         'price'     => '#report > tbody > tr:nth-child(4) > td:nth-child(14)', 
                            //     );
                            // }else{
                            //     $crawler->visit( 'https://mer.valuecommerce.ne.jp/report/network_statistics' )
                                                
                            //                     ->select( '#condition_fromDate', $s_date )
                            //                     ->select( '#condition_toDate', $e_date )
                            //                     ->click( '#show_statistics' )
                            //                     ->crawler();

                            //     $selector_crawler = array(
                            //         'imp'       => '#report > tfoot > tr > td.impressions',
                            //         'click'     => '#report > tfoot > tr > td:nth-child(6)',
                            //         'cv'        => '#report > tfoot > tr > td:nth-child(9)',
                            //         'partnership' => '#report > tbody > tr:last-child > td:nth-child(2)',
                            //         'price'     => $product_info->asp->daily_price_selector,
                            //     );
                            // }
                            
                            
                            echo "point2";
                            $valuecommerce_data = $crawler->each( function( Crawler $node ) use ($selector_crawler, $product_info)
                            {
                                $data = array( );
                                $data[ 'asp' ]     = $product_info->asp_id;
                                $data[ 'product' ] = $product_info->id;
                                $data[ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                                $data[ 'price' ] = 0;
                                //echo $node->html();
                                foreach ( $selector_crawler as $key => $value ) {
                                    $data[ $key ] = array( );
                                    if(count($node->filter( $value ))){
                                        if($key == 'cv'){
                                            preg_match( '/\d+(/', trim( $node->filter( $value )->text()) , $cv_array );
                                            $data[ $key ] =  preg_replace( '/[^0-9]/', '', $cv_array[ 0 ]);
                                        }else{
                                            $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                                        }
                                    
                                    }else{
                                        $data[ $key ] = 0;
                                        // throw new \Exception($value.'要素が存在しません。');
                                    }
                                } 
                                //$selector_crawler as $key => $value
                                //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 1);
                                
                                //承認された金額しか抽出できないため、0円
                                // $unit_price = $product_info->price;
                                // $data[ 'price' ] = $data[ 'cv' ] * $unit_price;
                                echo "point3";
                                //CPAとASPフィー込みの価格を計算
                                // $calculated = json_decode( 
                                //             json_encode( 
                                //                 json_decode( 
                                //                     $this->dailySearchService
                                //                         ->cpa( $data[ 'cv' ], $data[ 'price' ], 3 ) 
                                //                 ) 
                                //             ), True );
                                
                                // $data[ 'cpa' ]     = $calculated[ 'cpa' ]; //CPA
                                // $data[ 'cost' ]    = $calculated[ 'cost' ]; //獲得単価

                                return $data;
                            } );
                            
                            $calculated = json_decode( 
                                json_encode( 
                                    json_decode( 
                                        $this->dailySearchService
                                            ->cpa( $valuecommerce_data[0][ 'cv' ], $valuecommerce_data[0][ 'price' ], 3 ) 
                                    ) 
                                ), True );
                                
                            $valuecommerce_data[0][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                            $valuecommerce_data[0][ 'cost' ] = $calculated[ 'cost' ]; //獲得単価

                            //$crawler->closeAll();
                            echo "point4";
                            $c_url = 'https://mer.valuecommerce.ne.jp/affiliate_analysis/';
                            //?condition%5BfromYear%5D=' . $s_year . '&condition%5BfromMonth%5D=' . $s_month . '&condition%5BtoYear%5D=' . $s_year . '&condition%5BtoMonth%5D=' . $s_month . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=1';
                            
                            \Log::info($c_url);
                            $crawler_for_site = $browser
                                    ->visit( $c_url )
                                    ->select( '#condition_fromYear', $s_year )
                                    ->select( '#condition_fromMonth', $s_month )
                                    ->select( '#condition_toYear', $s_year )
                                    ->select( '#condition_toMonth', $s_month )
                                    ->click( '#show_statistics' )
                                    ->crawler();
                                                
                            $count_selector = "#cusomize_wrap > span";
                            echo "point5";
                            if(count($crawler_for_site->filter( $count_selector ))){
                                $active         = explode( "/", $crawler_for_site->filter( $count_selector )->text() );
                            }else{
                                $active         = 0;
                                // throw new \Exception($count_selector.'要素が存在しません。');
                            }
                            if((int)$active[ 1 ] <= 0){ throw new \Exception('アクティブパートナーが存在しませんでした。'); }
                            
                            $count_page     = ( (int)$active[ 1 ] > 40 ) ? ceil( (int)$active[ 1 ] / 40 ) : 1;
                            
                            //アクティブ数　格納
                            $valuecommerce_data[ 0 ][ 'active' ] = $active[ 1 ]; //trim(preg_replace('/[^0-9]/', '', $active_data[0]));
                            
                            //echo "active件数→".$active[1]."←active件数";
                            
                            echo "point6";
                            for ( $page = 0; $page < $count_page; $page++ ) {
                                
                                $target_page = (int)$page + 1;
                                echo $s_year ;
                                echo $s_month ;
                                echo $s_year ;
                                echo $s_month ;

                                $crawler_for_site = $browser//->visit( 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D=' . $s_year . '&condition%5BfromMonth%5D=' . $s_month . '&condition%5BtoYear%5D=' . $s_year . '&condition%5BtoMonth%5D=' . $s_month . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=' . $target_page )->crawler();
                                                        ->visit( $c_url )
                                                        ->select( '#condition_fromYear', $s_year )
                                                        ->select( '#condition_fromMonth', $s_month )
                                                        ->select( '#condition_toYear', $s_year )
                                                        ->select( '#condition_toMonth', $s_month )
                                                        ->click( '#show_statistics' )
                                                        ->visit( 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=' . $target_page )
                                                        ->crawler();
                                //最終ページのみ件数でカウント
                                $crawler_count = ( $target_page == $count_page ) ? (int)$active[ 1 ] - ( (int)$page * 40 ) : 40;
                                echo 'カウント';
                                echo $crawler_count;
                                //echo $target_page."ページ目のcrawler_count＞＞".$crawler_count."</br>" ;
                                
                                echo "point7";
                                if($crawler_count > 0){
                                        for ( $i = 1; $i <= $crawler_count; $i++ ) {
                                            
                                            $count = ( (int)$page * 40 ) + $i;
                                            
                                            $valuecommerce_site[ $count ][ 'product' ] = $product_info->id;
                                            $valuecommerce_site[ $count ][ 'asp' ]     = $product_info->asp_id;

                                            if ( $crawler_for_site->filter( '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->count() != 0 ) {
                                                //echo $target_page."ページの i＞＞".$i."番目</br>" ;
                                                
                                                $selector_for_site = array(
                                                    'media_id'  => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)',
                                                    'site_name' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(3) > a',
                                                    'imp'       => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(7)',
                                                    'click'     => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(8)',
                                                    'cv'        => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(19)',
                                                    'price'     => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(21)' 
                                                );
                                                echo "point8";
                                                foreach ( $selector_for_site as $key => $value ) {
                                                    if(count($crawler_for_site->filter( $value ))){
                                                        if ( $key == 'media_id' || $key == 'site_name' ){
                                                            
                                                            $valuecommerce_site[ $count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                                            
                                                        } else {
                                                            
                                                            $valuecommerce_site[ $count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                                            
                                                        }
                                                    }else{
                                                        throw new \Exception($value.'要素が存在しません。');
                                                    }
                                                }
                                                
                                                // $unit_price = $product_info->price;
                                                // $valuecommerce_site[ $count ][ 'price' ] = $unit_price * $valuecommerce_site[ $count ][ 'cv' ];
                                                echo "point9";
                                                echo $valuecommerce_site[ $count ][ 'cv' ];
                                                echo '<br>';
                                                echo $valuecommerce_site[ $count ][ 'price' ];
                                                //CPAとASPフィーの考慮した数値を算出
                                                $calculated = json_decode(
                                                                json_encode(
                                                                    json_decode(
                                                                        $this->dailySearchService
                                                                            ->cpa( $valuecommerce_site[ $count ][ 'cv' ], $valuecommerce_site[ $count ][ 'price' ], 3 )
                                                                    )
                                                                ), True );
                                                
                                                //各サイトのデータ保存
                                                $valuecommerce_site[ $count ][ 'cpa' ]  = $calculated[ 'cpa' ]; //CPA
                                                $valuecommerce_site[ $count ][ 'cost' ] = $calculated[ 'cost' ]; //獲得単価
                                                $valuecommerce_site[ $count ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                                
                                            }

                                        }
                                        $this->dailySearchService->save_site( json_encode( $valuecommerce_site ) );
                                }

                            } 
                            echo "point10";
                            //クロールデータの保存
                            //$client->quit();
                            $this->dailySearchService->save_daily( json_encode( $valuecommerce_data ) );
                            
                            
                        } 
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'ValueCommerce',
                                'type' => 'Daily',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                
                }
            } );
        }
    }
}