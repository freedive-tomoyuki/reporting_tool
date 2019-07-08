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
use DB;

class AfbController extends MonthlyCrawlerController
{
    
    public function afb( $product_base_id ) //OK
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
        $product_id = $this->BasetoProduct( 4, $product_base_id );
        /*
        Chromeドライバーのインスタンス呼び出し
        */
        $client     = new Client( new Chrome( $options ) );
        /*
        Chromeドライバー実行
        　引数
        　　$product_id:案件ID
        */
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            
            $product_infos = \App\Product::all()->where( 'id', $product_id );
            /*
            日付　取得
            */
            //X月1日集計のとき、開始＝前月1日、終了＝前月末日
            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                $start = date( "m", strtotime( "-2 month" ) );
                $end   = date( "m", strtotime( "-1 month" ) );
            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
            else { //開始＝当月1日、終了＝当月前日
                $start = date( "m", strtotime( "-1 month" ) );
                $end   = date( "m" );
            }
            
            foreach ( $product_infos as $product_info ) {
                //実装：ログイン
                $crawler         = $browser->visit( "https://www.afi-b.com/" )
                    ->type( $product_info->asp->login_key, $product_info->login_value )
                    ->type( $product_info->asp->password_key, $product_info->password_value )
                    ->click( $product_info->asp->login_selector )
                    ->visit( "https://client.afi-b.com/client/b/cl/report/?r=monthly" )
                    ->select( '#form_start_month', $start ) //レポート期間
                    ->select( '#form_end_month', $end ) //レポート期間
                    ->click( '#adv_id_monthly_chzn > a' ) //案件選択
                    ->click( '#adv_id_monthly_chzn_o_1' ) //案件選択
                    ->click( '#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(1)' )
                    ->click( '#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(2)' )
                    ->click( '#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(3)' )
                    ->click( '#report_form_1 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input' )
                    ->crawler();
                    echo $crawler->html();
                //var_dump( $crawler);
                //先月・今月のセレクタ
                $selector_this   = array(
                     'approval' => '#reportTable > tbody > tr:nth-child(2) > td:nth-child(10) > p',
                    'approval_price' => '#reportTable > tbody > tr:nth-child(2) > td:nth-child(13) > p' 
                );
                $selector_before = array(
                     'approval' => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(10) > p',
                    'approval_price' => '#reportTable > tbody > tr:nth-child(1) > td:nth-child(13) > p' 
                );
                //セレクターからフィルタリング
                $afbdata = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    
                    foreach ( $selector_this as $key => $value ) {
                        $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );

                        if($key == 'approval_price'){
                            $data[ $key ]   = $this->calc_approval_price(
                                trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ) ,4);
                        }else{
                            $data[ $key ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        }

                    } //$selector_this as $key => $value
                    foreach ( $selector_before as $key => $value ) {
                        
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $data[ 'last_date' ] = date( 'Y-m-d', strtotime( '-2 month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                        }
                        if($key == 'approval_price'){
                            $data[ 'last_' . $key ] = $this->calc_approval_price(
                                trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ),4);
                        }else{
                            $data[ 'last_' . $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                        }
                    } //$selector_before as $key => $value
                    return $data;
                } );
                
                //サイト取得用クロール
                $y       = 0;
                $afbsite = array( );
                
                // x = 0:今月
                // x = 1:前月
                for ( $x = 0; $x < 2; $x++ ) {
                    
                    $i = 1;
                    
                    if ( $x == 0 ) { //今月
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                            $start = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                            $end   = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else { //1日以外のクロールの場合
                            $start = date( 'Y/m/01' );
                            $end   = date( 'Y/m/d', strtotime( '-1 day' ) );
                        }
                    } //$x == 0
                    else { //先月
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                            $start = date( 'Y/m/01', strtotime( '-2 month' ) );
                            $end   = date( 'Y/m/t', strtotime( '-2 month' ) );
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else { //1日以外のクロールの場合
                            $start = date( 'Y/m/d', strtotime( 'first day of previous month' ) );
                            $end   = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                        }
                    }
                    
                    $crawler_for_site = $browser->visit( 'https://client.afi-b.com/client/b/cl/report/?r=site' )

                    //レポート期間（今月）
                        ->type( '#report_form_4 > div > table > tbody > tr:nth-child(4) > td > ul > li:nth-child(1) > input', $start )
                        ->type( '#report_form_4 > div > table > tbody > tr:nth-child(4) > td > ul > li:nth-child(3) > input', $end )
                    //案件選択
                        ->click( '#adv_id_pssite_chzn > a' )
                        ->click( '#adv_id_pssite_chzn_o_1' )
                    //表示するデバイスを絞る
                        ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(1)' ) //表示するデバイスを絞る
                        ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(2)' ) //表示するデバイスを絞る
                        ->click( '#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(3)' ) //表示するデバイスを絞る
                        ->click( '#report_form_4 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input' )->crawler();
                    $crawler_for_site->html();
                    
                    //サイト一覧　１ページ分のクロール
                    while ( $crawler_for_site->filter( '#reportTable > tbody > tr:nth-child(' . $i . ') > td.maxw150' )->count() > 0 ) {
                        $afbsite[ $y ][ 'product' ] = $product_info->id;
                        
                        if ( $x == 0 ) {
                            
                            $afbsite[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                        } //$x == 0
                        else {
                            
                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $afbsite[ $y ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            else {
                                $afbsite[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                            
                        }
                        
                        $selector_for_site = array(
                             'media_id' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td.maxw150',
                            'site_name' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td.maxw150 > p > a',
                            'approval' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(13) > p',
                            'approval_price' => '#reportTable > tbody > tr:nth-child(' . $i . ') > td:nth-child(16) > p' 
                        );
                        // サイト一覧　１行ずつクロール
                        foreach ( $selector_for_site as $key => $value ) {
                            
                            if ( $key == 'media_id' ) {
                                
                                $media_id = array( );
                                $sid      = trim( $crawler_for_site->filter( $value )->attr( 'title' ) );
                                preg_match( '/SID：(\d+)/', $sid, $media_id );
                                $afbsite[ $y ][ $key ] = $media_id[ 1 ];
                                
                            } //$key == 'media_id'
                            elseif ( $key == 'site_name' ) {
                                
                                $afbsite[ $y ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                
                            } //$key == 'site_name'
                            elseif($key == 'approval_price'){

                                $afbsite[ $y ][ $key ] = 
                                    $this->calc_approval_price(
                                        trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) )
                                    , 4);
                            }
                            else {
                                
                                $afbsite[ $y ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                
                            }
                            
                        } // endforeach 
                        $y++;
                        $i++;
                    } // endfor
                } //$x = 0; $x < 2; $x++
                
                $this->save_monthly( json_encode( $afbdata ) );
                $this->save_site( json_encode( $afbsite ) );
                
            } //$product_infos as $product_info
        } );
    }
    
    
}