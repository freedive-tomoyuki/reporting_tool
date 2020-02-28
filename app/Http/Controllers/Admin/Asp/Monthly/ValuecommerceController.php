<?php

namespace App\Http\Controllers\Admin\Asp\Monthly;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Http\Controllers\Admin\MonthlyCrawlerController;
use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
use App\Mail\Alert;
use Mail;
use DB;

class ValuecommerceController extends MonthlyCrawlerController
{
    
    public function valuecommerce( $product_base_id ) //OK
    {
        /**
        
        ブラウザ立ち上げ
        
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
        $products = json_decode( $this->monthlySearchService->BasetoProduct( 3, $product_base_id),true);
        
        $client = new Client( new Chrome( $options ) );
        
        foreach($products as $p ){
            
            $product_id = $p['id'];   

            $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
            {
                try{
                        //$product_infos = \App\Product::all()->where('id',$product_id);
                        $product_infos = \App\Product::all()->where( 'id', $product_id );
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $s_Y = date( "Y", strtotime( "-2 month" ) );
                            $s_M = date( "n", strtotime( "-2 month" ) );
                            $e_Y   = date( "Y", strtotime( "-1 month" ) );
                            $e_M   = date( "n", strtotime( "-1 month" ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $s_Y = date( "Y", strtotime( "-1 month" ) );
                            $s_M = date( "n", strtotime( "-1 month" ) );
                            $e_Y   = date( "Y" );
                            $e_M   = date( "n" );
                        }
                        foreach ( $product_infos as $product_info ) {
                            
                            //実装：ログイン
                            $crawler = $browser->visit( $product_info->asp->login_url )
                                                ->type( $product_info->asp->login_key, $product_info->login_value )
                                                ->type( $product_info->asp->password_key, $product_info->password_value )
                                                ->click( $product_info->asp->login_selector ) 
                                                ->visit('https://mer.valuecommerce.ne.jp/switch/'.$product_info->asp_sponsor_id.'/')
                            
                            //実装：初期ページ
                                                ->visit( "https://mer.valuecommerce.ne.jp/report/sales_performance/" )
                                                ->select( '#condition_fromYear', $s_Y )
                                                ->select( '#condition_fromMonth', $s_M )
                                                ->select( '#condition_toYear', $e_Y )
                                                ->select( '#condition_toMonth', $e_M )
                                                ->click( '#show_statistics' )
                                                ->crawler();
                            //var_dump($crawler);
                            
                            //先月・今月のセレクタ
                            $selector_this   = array(
                                'approval' => '#reportCompare > tbody > tr:nth-child(2) > td:nth-child(11)',
                                'approval_price' => '#reportCompare > tbody > tr:nth-child(2) > td:nth-child(20)' 
                            );
                            $selector_before = array(
                                'approval' => '#reportCompare > tbody > tr:nth-child(1) > td:nth-child(11)',
                                'approval_price' => '#reportCompare > tbody > tr:nth-child(1) > td:nth-child(20)' 
                            );
                            //echo $crawler->html();
                            
                            //セレクターからフィルタリング
                            $valuecommerce_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                            {
                                $data              = array();
                                $data[ 'asp' ]     = $product_info->asp_id;
                                $data[ 'product' ] = $product_info->id;
                                
                                // $unit_price = $product_info->price;

                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                
                                if(count($node->filter( $selector_this['approval'] ))){
                                    $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter($selector_this['approval'] )->text() ) );
                                }else{ throw new \Exception($selector_this['approval'] .'要素が存在しません。');}

                                if(count($node->filter( $selector_this['approval_price'] ))){
                                    $data[ 'approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_this['approval_price'] )->text() ) )
                                                                    ,3);
                                }else{ throw new \Exception($selector_this['approval_price'].'要素が存在しません。'); }
                                
                                // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;
                                    
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                }else {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                }
                                if(count($node->filter( $selector_before['approval']  ))){
                                    $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval'] )->text() ) );
                                }else{ throw new \Exception($selector_before['approval'].'要素が存在しません。');}
                                
                                if(count($node->filter( $selector_before['approval_price'] ))){
                                    $data[ 'last_approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_before['approval_price'] )->text() ) )
                                                                    ,3);
                                }else{ throw new \Exception($selector_before['approval_price'].'要素が存在しません。'); }

                                // $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;
                                
                                return $data;
                            } );
                            
                            // var_dump( $valuecommerce_data );
                            //１ページ目クロール
                            //$pagination_page = $product_info->asp->lp2_url;
                            //$crawler_for_site = $browser->visit($pagination_page)->crawler();
                            
                            //   サイト取得用クロール
                            
                            //$x = 0; 
                            //$addtion = 0 ;
                            $count = 0;
                            /**
                            *    今月：$x = 0
                            *    先月：$x = 1
                            */
                            for ( $x = 0; $x < 2; $x++ ) {
                                
                                //サイト数取得用にクロール
                                //デフォルトでは、今月分のクロールを実行
                                if ( $x == 0 ) {
                                    //$crawler_for_site = $browser->visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/')
                                    //->crawler();
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $y = date( 'Y', strtotime( '-1 month' ) ); //先月
                                        $n = date( 'n', strtotime( '-1 month' ) ); //先月
                                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                    else {
                                        $y = date( 'Y' ); //今月
                                        $n = date( 'n' ); //今月
                                    }
                                    //先月分のクロール
                                } //$x == 0
                                else {
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $y = date( 'Y', strtotime( '-2 month' ) ); //先々月
                                        $n = date( 'n', strtotime( '-2 month' ) ); //先々月
                                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                    else {
                                        $y = date( 'Y', strtotime( '-1 month' ) ); //先月
                                        $n = date( 'n', strtotime( '-1 month' ) ); //先月
                                    }
                                }
                                
                                $crawler_for_site = $browser->visit( 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D=' . $y . '&condition%5BfromMonth%5D=' . $n . '&condition%5BtoYear%5D=' . $y . '&condition%5BtoMonth%5D=' . $n . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=1' )->crawler();
                                //echo $crawler_for_site->html();
                                
                                //　アクティブサイト数（https://mer.valuecommerce.ne.jp/affiliate_analysis/） 
                                if(count($crawler_for_site->filter( "#cusomize_wrap > span" ))){
                                    $active = explode( "/", $crawler_for_site->filter( "#cusomize_wrap > span" )->text() );
                                }else{
                                    throw new \Exception('#cusomize_wrap > span要素が存在しません。');
                                }
                                // echo "active件数→" . $active[ 1 ] . "←active件数";
                                
                                //ページ数を計算　＝　アクティブサイト数 / ４０
                                $count_page = ( $active[ 1 ] > 40 ) ? ceil( $active[ 1 ] / 40 ) : 1;
                                // echo "count_page件数→" . $count_page . "←count_page件数";

                                
                                /**
                                 *      １ページ　クロール
                                 */
                                for ( $page = 0; $page < $count_page; $page++ ) {
                                    
                                    $target_page = $page + 1;
                                    
                                    $crawler_for_site = $browser->visit( 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D=' . $y . '&condition%5BfromMonth%5D=' . $n . '&condition%5BtoYear%5D=' . $y . '&condition%5BtoMonth%5D=' . $n . '&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page=' . $target_page )->crawler();
                                    
                                    //最終ページのみ件数でカウント
                                    $crawler_count    = ( $target_page == $count_page ) ? $active[ 1 ] - ( $page * 40 ) : 40;
                                    //echo $crawler_count;
                                    
                                    //echo $target_page . "ページ目のcrawler_count＞＞" . $crawler_count . "</br>";
                                    /**
                                    *１行ごと　クロール
                                    */
                                    for ( $i = 1; $i <= $crawler_count; $i++ ) {
                                        //1ページMAXの件数は４０件
                                        $valuecommerce_site[ $count ][ 'product' ] = $product_info->id;
                                        
                                        $selector_for_site = array(
                                            'media_id'      => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2)',
                                            'site_name'     => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(3) > a',
                                            'approval'      => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(10)',
                                            'approval_price' => '#all > div.tablerline > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(21)' 
                                        );
                                        
                                        foreach ( $selector_for_site as $key => $value ) {
                                            
                                            if ( $x == 0 ) {
                                                $valuecommerce_site[ $count ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                            } //$x == 0
                                            else {
                                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                                    $valuecommerce_site[ $count ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                                } 
                                                else {
                                                    $valuecommerce_site[ $count ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                                }
                                            }
                                            if(count($crawler_for_site->filter( $value ))){
                                                if ( $key == 'site_name' ) {
                                                    
                                                    $valuecommerce_site[ $count ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                                    
                                                }
                                                elseif ( $key == 'approval' ) {
                                                    
                                                    $approval_array = array();
                                                    $approval       = trim( $crawler_for_site->filter( $value )->text() );
                                                    preg_match( '/(\d+)/', $approval, $approval_array );
                                                    $valuecommerce_site[ $count ][ $key ] = $approval_array[ 1 ];
                                                    
                                                } 
                                                elseif ( $key == 'approval_price' ) {
                                                
                                                    $valuecommerce_site[ $count ][ $key ] = $this->monthlySearchService->calc_approval_price( 
                                                                                    trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) )
                                                                                ,3);
                                                } 
                                                else {
                                                    
                                                    $valuecommerce_site[ $count ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                                    
                                                }
                                            }else{
                                                throw new \Exception($value.'要素が存在しません。');
                                            }
                                        } 
                                        
                                        // $valuecommerce_site[ $count ][ 'approval_price' ] = $valuecommerce_site[ $count ][ 'approval' ] * $product_info->price;
                                        $count++;
                                        
                                    }
                                } 
                            } 

                            $this->monthlySearchService->save_monthly( json_encode( $valuecommerce_data ) );
                            $this->monthlySearchService->save_site( json_encode( $valuecommerce_site ) );
                            
                        } //$product_infos as $product_info
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_id,
                                'asp' => 'ValueCommerce',
                                'type' => 'Monthly',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                }
            } );
        }
    }
    
}