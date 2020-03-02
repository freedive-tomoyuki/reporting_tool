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
        $products =  json_decode($this->monthlySearchService->BasetoProduct( 4, $product_base_id ),true);
        /*
        Chromeドライバーのインスタンス呼び出し
        */
        $client     = new Client( new Chrome( $options ) );
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
                                ->click( '#adv_id_monthly_chzn_o_'.$product_info->product_order ) //案件選択
                                ->click( '#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(1)' )
                                ->click( '#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(2)' )
                                ->click( '#report_form_1 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(3)' )
                                ->click( '#report_form_1 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input' )
                                ->crawler();
                                //echo $crawler->html();
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
                            $afb_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                            {
                                
                                $data              = array( );
                                $data[ 'asp' ]     = $product_info->asp_id;
                                $data[ 'product' ] = $product_info->id;
                                
                                // $unit_price = $product_info->price;
                                
                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                
                                if(count($node->filter( $selector_this['approval'] ))){
                                    $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_this['approval'] )->text() ) );
                                }else{ throw new \Exception($selector_this['approval'].'要素が存在しません。'); }

                                if(count($node->filter( $selector_this['approval_price'] ))){
                                    $data[ 'approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                    trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_this['approval_price'] )->text() ) )
                                                                ,4);
                                }else{ throw new \Exception($selector_this['approval_price'].'要素が存在しません。'); }

                                // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( '-2 month' ) );
                                }else {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                }

                                if(count($node->filter( $selector_before['approval'] ))){
                                    $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval']  )->text() ) );
                                }else{ throw new \Exception($selector_before['approval'].'要素が存在しません。'); }

                                if(count($node->filter( $selector_before['approval_price'] ))){
                                    $data[ 'last_approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_before['approval_price'] )->text() ) )
                                                                    ,4);
                                }else{ throw new \Exception($selector_before['approval_price'].'要素が存在しません。'); }

                                // $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;

                                return $data;
                            } );
                            
                            //サイト取得用クロール
                            $y       = 0;
                            $afb_site = array( );
                            
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
                                    $afb_site[ $y ][ 'product' ] = $product_info->id;
                                    
                                    if ( $x == 0 ) {
                                        
                                        $afb_site[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                        
                                    } //$x == 0
                                    else {
                                        
                                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                            $afb_site[ $y ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                        else {
                                            $afb_site[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
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
                                        if(count($crawler_for_site->filter( $value ))){
                                            if ( $key == 'media_id' ) {
                                                
                                                $media_id = array( );
                                                $sid      = trim( $crawler_for_site->filter( $value )->attr( 'title' ) );
                                                preg_match( '/SID：(\d+)/', $sid, $media_id );
                                                $afb_site[ $y ][ $key ] = $media_id[ 1 ];
                                                
                                            } 
                                            elseif ( $key == 'site_name' ) {
                                                
                                                $afb_site[ $y ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                                
                                            }
                                            elseif ( $key == 'approval_price' ) {
                                                
                                                $afb_site[ $y ][ $key ] = $this->monthlySearchService->calc_approval_price( 
                                                                                trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) )
                                                                            ,4);
                                            } 
                                            else {
                                                
                                                $afb_site[ $y ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                                
                                            }
                                        }else{
                                            throw new \Exception($value.'要素が存在しません。');
                                        }
                                    } // endforeach 
                                    
                                    // $afb_site[ $y ][ 'approval_price' ] = $afb_site[ $y ][ 'approval' ] * $product_info->price;

                                    $y++;
                                    $i++;
                                } // endfor
                            } //$x = 0; $x < 2; $x++
                            
                            $this->monthlySearchService->save_monthly( json_encode( $afb_data ) );
                            $this->monthlySearchService->save_site( json_encode( $afb_site ) );
                            
                        } //$product_infos as $product_info
                }
                catch(\Exception $e){
                    $sendData = [
                                'message' => $e->getMessage(),
                                'datetime' => date('Y-m-d H:i:s'),
                                'product_id' => $product_name,
                                'asp' => 'Afb',
                                'type' => 'Monthly',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                }        
            } );
        }
    }
    
    
}