<?php

namespace App\Http\Controllers\Admin\Asp\Daily;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\DailyCrawlerController;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\DailyDiff;
use App\DailySiteDiff;
//header('Content-Type: text/html; charset=utf-8');

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
        $product_id = $this->BasetoProduct( 3, $product_base_id );
        
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            
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
                
                $selector_crawler = array(
                    'imp' => $product_info->asp->daily_imp_selector,
                    'click' => $product_info->asp->daily_click_selector,
                    'cv' => $product_info->asp->daily_cv_selector,
                    'partnership' => $product_info->asp->daily_partnership_selector,
                    'price' => $product_info->asp->daily_price_selector 
                );
                
                
                
                $vcdata = $crawler->each( function( Crawler $node ) use ($selector_crawler, $product_info)
                {
                    $data = array( );
                    //echo $node->html();
                    foreach ( $selector_crawler as $key => $value ) {
                        $data[ $key ] = array( );
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector_crawler as $key => $value
                    //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 1); 
                    //CPAとASPフィー込みの価格を計算
                    $calData = json_decode( json_encode( json_decode( $this->cpa( $data[ 'cv' ], $data[ 'price' ], 3 ) ) ), True );
                    
                    $data[ 'cpa' ]     = $calData[ 'cpa' ]; //CPA
                    $data[ 'cost' ]    = $calData[ 'cost' ]; //獲得単価
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
                $vcdata[ 0 ][ 'active' ] = $active[ 1 ]; //trim(preg_replace('/[^0-9]/', '', $active_data[0]));
                
                //echo "active件数→".$active[1]."←active件数";
                
                for ( $page = 0; $page < $count_page; $page++ ) {
                    
                    $target_page = $page + 1;
                    
                    $crawler_for_site = $browser->visit( 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D=' . $s_Y . '&condition%5BfromMonth%5D=' . $s_M . '&condition%5BtoYear%5D=' . $s_Y . '&condition%5BtoMonth%5D=' . $s_M . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=' . $target_page )->crawler();
                    
                    //最終ページのみ件数でカウント
                    $crawler_count = ( $target_page == $count_page ) ? $active[ 1 ] - ( $page * 40 ) : 40;
                    
                    //echo $target_page."ページ目のcrawler_count＞＞".$crawler_count."</br>" ;
                    
                    for ( $i = 1; $i <= $crawler_count; $i++ ) {
                        
                        $count = ( $page * 40 ) + $i;
                        
                        $data[ $count ][ 'product' ] = $product_info->id;
                        
                        if ( $crawler_for_site->filter( '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->count() != 0 ) {
                            //echo $target_page."ページの i＞＞".$i."番目</br>" ;
                            
                            $selector_for_site = array(
                                 'media_id' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)',
                                'site_name' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(3) > a',
                                'imp' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(7)',
                                'click' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(8)',
                                'cv' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(19)',
                                'price' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(21)' 
                            );
                            
                            foreach ( $selector_for_site as $key => $value ) {
                                
                                if ( $key == 'site_name' ) {
                                    
                                    $data[ $count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                    
                                } //$key == 'site_name'
                                else {
                                    
                                    $data[ $count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                    
                                }
                            } //$selector_for_site as $key => $value
                            
                            //CPAとASPフィーの考慮した数値を算出
                            $calData = json_decode( json_encode( json_decode( $this->cpa( $data[ $count ][ 'cv' ], $data[ $count ][ 'price' ], 3 ) ) ), True );
                            
                            //各サイトのデータ保存
                            $data[ $count ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                            $data[ $count ][ 'cost' ] = $calData[ 'cost' ]; //獲得単価
                            $data[ $count ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                        } //$crawler_for_site->filter( '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)' )->count() != 0
                    } //$i = 1; $i <= $crawler_count; $i++
                    
                } //$page = 0; $page < $count_page; $page++
                //var_dump($data);
                //クロールデータの保存
                //$client->quit();
                $this->save_daily( json_encode( $vcdata ) );
                $this->save_site( json_encode( $data ) );
                
            } //$product_infos as $product_info
        } );
    }
}