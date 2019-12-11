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
        $product_id = $this->dailySearchService->BasetoProduct( 3, $product_base_id );
        
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    //$product_infos = \App\Product::all()->where('id',$product_id);
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
                        
                        $crawler = $browser->visit( $product_info->asp->login_url )->type( $product_info->login_key, $product_info->login_value )->type( $product_info->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( $product_info->asp->lp1_url )->crawler();
                        //echo $crawler->html();
                        
                        if(date( 'Y/m/d' ) == date( 'Y/m/01' )){
                            $selector_crawler = array(
                                'imp'       => '#report > tbody > tr:nth-child(4) > td:nth-child(5)',
                                'click'     => '#report > tbody > tr:nth-child(4) > td:nth-child(8)',
                                'cv'        => '#report > tbody > tr:nth-child(4) > td:nth-child(11)',
                                'partnership' => '#report > tbody > tr:nth-child(4) > td:nth-child(2)',
                            //    'price'     => '#report > tbody > tr:nth-child(4) > td:nth-child(14)', 
                            );
                        }else{
                            $selector_crawler = array(
                                'imp'       => $product_info->asp->daily_imp_selector,
                                'click'     => $product_info->asp->daily_click_selector,
                                'cv'        => $product_info->asp->daily_cv_selector,
                                'partnership' => $product_info->asp->daily_partnership_selector,
                            //    'price'     => $product_info->asp->daily_price_selector,
                            );
                        }
                        
                        
                        
                        $valuecommerce_data = $crawler->each( function( Crawler $node ) use ($selector_crawler, $product_info)
                        {
                            $data = array( );
                            //echo $node->html();
                            foreach ( $selector_crawler as $key => $value ) {
                                $data[ $key ] = array( );
                                $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                            } 
                            //$selector_crawler as $key => $value
                            //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 1);

                            $unit_price = $product_info->price;
                            $data[ 'price' ] = $data[ 'cv' ] * $unit_price;
                            
                            //CPAとASPフィー込みの価格を計算
                            $calculated = json_decode( 
                                        json_encode( 
                                            json_decode( 
                                                $this->dailySearchService
                                                    ->cpa( $data[ 'cv' ], $data[ 'price' ], 3 ) 
                                            ) 
                                        ), True );

                            $data[ 'cpa' ]     = $calculated[ 'cpa' ]; //CPA
                            $data[ 'cost' ]    = $calculated[ 'cost' ]; //獲得単価
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            $data[ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                            return $data;
                        } );
                        //$crawler->closeAll();

                        $c_url = 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D=' . $s_Y . '&condition%5BfromMonth%5D=' . $s_M . '&condition%5BtoYear%5D=' . $s_Y . '&condition%5BtoMonth%5D=' . $s_M . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=1';
                        
                        $crawler_for_site = $browser->visit( $c_url )->crawler();
                        
                        $count_selector = "#cusomize_wrap > span";
                        $active         = explode( "/", $crawler_for_site->filter( $count_selector )->text() );
                        $count_page     = ( $active[ 1 ] > 40 ) ? ceil( $active[ 1 ] / 40 ) : 1;
                        
                        //アクティブ数　格納
                        $valuecommerce_data[ 0 ][ 'active' ] = $active[ 1 ]; //trim(preg_replace('/[^0-9]/', '', $active_data[0]));
                        
                        //echo "active件数→".$active[1]."←active件数";
                        
                        for ( $page = 0; $page < $count_page; $page++ ) {
                            
                            $target_page = $page + 1;
                            
                            $crawler_for_site = $browser->visit( 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D=' . $s_Y . '&condition%5BfromMonth%5D=' . $s_M . '&condition%5BtoYear%5D=' . $s_Y . '&condition%5BtoMonth%5D=' . $s_M . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=' . $target_page )->crawler();
                            
                            //最終ページのみ件数でカウント
                            $crawler_count = ( $target_page == $count_page ) ? $active[ 1 ] - ( $page * 40 ) : 40;
                            
                            //echo $target_page."ページ目のcrawler_count＞＞".$crawler_count."</br>" ;
                            
                            for ( $i = 1; $i <= $crawler_count; $i++ ) {
                                
                                $count = ( $page * 40 ) + $i;
                                
                                $valuecommerce_site[ $count ][ 'product' ] = $product_info->id;
                                
                                if ( $crawler_for_site->filter( '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->count() != 0 ) {
                                    //echo $target_page."ページの i＞＞".$i."番目</br>" ;
                                    
                                    $selector_for_site = array(
                                        'media_id'  => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)',
                                        'site_name' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(3) > a',
                                        'imp'       => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(7)',
                                        'click'     => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(8)',
                                        'cv'        => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(19)',
                                        //'price' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(21)' 
                                    );
                                    
                                    foreach ( $selector_for_site as $key => $value ) {
                                        
                                        if ( $key == 'site_name' ){
                                            
                                            $valuecommerce_site[ $count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                            
                                        } else {
                                            
                                            $valuecommerce_site[ $count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                            
                                        }
                                    }
                                    
                                    $unit_price = $product_info->price;
                                    $valuecommerce_site[ $count ][ 'price' ] = $unit_price * $valuecommerce_site[ $count ][ 'cv' ];

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

                        } 

                        //クロールデータの保存
                        //$client->quit();
                        $this->dailySearchService->save_daily( json_encode( $valuecommerce_data ) );
                        $this->dailySearchService->save_site( json_encode( $valuecommerce_site ) );
                        
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