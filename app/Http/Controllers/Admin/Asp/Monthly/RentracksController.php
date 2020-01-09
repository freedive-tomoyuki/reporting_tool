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

class RentracksController extends MonthlyCrawlerController
{
    
    public function rentracks( $product_base_id ) //OK
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
        $product_id = $this->monthlySearchService->BasetoProduct( 5, $product_base_id );
        
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
                    //X月1日集計のとき、開始＝前月1日、終了＝前月末日
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                        $s_Y = date( 'Y', strtotime( 'first day of previous month' ) );
                        $s_M = date( 'n', strtotime( 'first day of previous month' ) );
                        $s_D = date( 'j', strtotime( 'first day of previous month' ) );
                        $e_Y = date( 'Y', strtotime( 'last day of previous month' ) );
                        $e_M = date( 'n', strtotime( 'last day of previous month' ) );
                        $e_D = date( 'j', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else { //開始＝当月1日、終了＝当月前日
                        $s_Y = date( 'Y' );
                        $s_M = date( 'n' );
                        $s_D = 1;
                        $e_Y = date( 'Y', strtotime( '-1 day' ) );
                        $e_M = date( 'n', strtotime( '-1 day' ) );
                        $e_D = date( 'j', strtotime( '-1 day' ) );
                    }
                    
                    foreach ( $product_infos as $product_info ) {
                        /*
                        クロール：ログイン＝＞アクセス統計分析より検索
                        https://manage.rentracks.jp/sponsor/detail_access
                        */
                        $crawler = $browser
                        ->visit( $product_info->asp->login_url )
                        ->type( $product_info->asp->login_key, $product_info->login_value )
                        ->type( $product_info->asp->password_key, $product_info->password_value )
                        ->click( $product_info->asp->login_selector )
                        ->visit( 'https://manage.rentracks.jp/sponsor/top' )
                        ->select( '#idDropdownlist1', $product_info->asp_product_id )
                        ->click( '#idButton1' )->crawler();
                        //echo $crawler->html();
                        /*
                        selector 設定
                        */
                        if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            $row_this   = 3;
                            $row_before = 2;
                        } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                        else {
                            $row_this   = 4;
                            $row_before = 3;
                        }
                        
                        $selector_this   = array(
                             'approval' => '#main > table > tbody > tr:nth-child(8) > td:nth-child(' . $row_this . ')',
                            'approval_price' => '#main > table > tbody > tr.total > td:nth-child(' . $row_this . ')' 
                        );
                        $selector_before = array(
                             'approval' => '#main > table > tbody > tr:nth-child(8) > td:nth-child(' . $row_before . ')',
                            'approval_price' => '#main > table > tbody > tr.total > td:nth-child(' . $row_before . ')' 
                        );
                        
                        /*
                        $crawler　をフィルタリング
                        */
                        $rentrack_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                        {
                            
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            
                            $unit_price = $product_info->price;
                            
                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            // $data[ $key ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                            $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval'] )->text() ) );
                            $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;
                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            else {
                                $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                            $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval']  )->text() ) );
                            $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;

                            // foreach ( $selector_this as $key => $value ) {

                            //     if($key == 'approval_price'){
                            //         $data[ $key ]   = $this->monthlySearchService->calc_approval_price(
                            //             trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ), 5);
                            //     }else{
                            //         $data[ $key ]   = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                            //     }

                            //     $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            // } //$selector_this as $key => $value
                            // foreach ( $selector_before as $key => $value ) {

                            //     if($key == 'approval_price'){
                            //         $data[ 'last_' . $key ] = $this->monthlySearchService->calc_approval_price(
                            //             trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) ), 5);
                            //     }else{
                            //         $data[ 'last_' . $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                            //     }
                                
                            //     //$data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                            //     if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                            //         $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            //     } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                            //     else {
                            //         $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            //     }
                                
                            // } //$selector_before as $key => $value
                            return $data;
                            
                        } );
                        //var_dump( $rentrack_data );
                        /*
                        サイト抽出　
                        */
                        $rentrack_site = array( );
                        
                        //$x = 0; 
                        $y = 1;
                        // x = 0:今月
                        // x = 1:前月
                        
                        for ( $x = 0; $x < 2; $x++ ) {
                            
                            if ( $x == 0 ) { //今月分
                                //X月1日集計のとき、開始＝前月1日、終了＝前月末日
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $s_Y = date( 'Y', strtotime( '-1 month' ) );
                                    $s_M = date( 'n', strtotime( '-1 month' ) );
                                    $s_D = 1;
                                    $e_Y = date( 'Y', strtotime( '-1 month' ) );
                                    $e_M = date( 'n', strtotime( '-1 month' ) );
                                    $e_D = date( 'j', strtotime( '-1 month' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else { //開始＝当月1日、終了＝当月前日
                                    $s_Y = date( 'Y', strtotime( '-1 day' ) );
                                    $s_M = date( 'n', strtotime( '-1 day' ) );
                                    $s_D = 1;
                                    $e_Y = date( 'Y', strtotime( '-1 day' ) );
                                    $e_M = date( 'n', strtotime( '-1 day' ) );
                                    $e_D = date( 'j', strtotime( '-1 day' ) );
                                }
                                
                            } //$x == 0
                            else { //先月分
                                //X月1日集計のとき、開始＝2ヶ月前1日、終了＝2ヶ月前末日
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $s_Y = date( 'Y', strtotime( '-2 month' ) );
                                    $s_M = date( 'n', strtotime( '-2 month' ) );
                                    $s_D = 1;
                                    $e_Y = date( 'Y', strtotime( '-2 month' ) );
                                    $e_M = date( 'n', strtotime( '-2 month' ) );
                                    $e_D = date( 't', strtotime( '-2 month' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else { //X月1日集計のとき、開始＝当月1日、終了＝当月前日
                                    $s_Y = date( 'Y', strtotime( '-1 month' ) );
                                    $s_M = date( 'n', strtotime( '-1 month' ) );
                                    $s_D = 1;
                                    $e_Y = date( 'Y', strtotime( '-1 month' ) );
                                    $e_M = date( 'n', strtotime( '-1 month' ) );
                                    $e_D = date( 't', strtotime( '-1 month' ) );
                                }
                                //$start = date('Y-m-01',strtotime('-1 month'));
                                //$end = date('Y-m-d', strtotime('last day of previous month'));
                                
                            }
                            $crawler_for_site = $browser->visit( "https://manage.rentracks.jp/sponsor/detail_partner" )->select( '#idDropdownlist1', $product_info->asp_product_id )->select( '#idGogoYear', $s_Y )->select( '#idGogoMonth', $s_M )->select( '#idGogoDay', $s_D )->select( '#idDoneYear', $e_Y )->select( '#idDoneMonth', $e_M )->select( '#idDoneDay', $e_D )->select( '#idPageSize', '300' ) //表示件数指定
                                ->click( '#idButton1' ) //検索実行
                                ->crawler();
                            //echo $crawler_for_site->html();
                            
                            $active_partner = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( '#main > div.hitbox > em' )->text() ) );
                            //echo $crawler_for_site->html();
                            
                            for ( $i = 1; $active_partner >= $i; $i++ ) {
                                
                                $rentrack_site[ $y ][ 'product' ] = $product_info->id;
                                //1周目
                                if ( $x == 0 ) {
                                    $rentrack_site[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                                } //$x == 0
                                else { //2周目
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $rentrack_site[ $y ][ 'date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                    else {
                                        $rentrack_site[ $y ][ 'date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                    }
                                }
                                
                                
                                //echo $rentrack_site[$y]['date'];
                                $iPlus                  = $i + 1;
                                //月内の場合は、「承認」先月のものに関しては、「請求済」からデータを取得
                                $approval_selector      = ( $x == 0 ) ? '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c13' : '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c14';
                                // $approvalprice_selector = ( $x == 0 ) ? '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c18' : '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c19';
                                
                                $selector_for_site = array(
                                    'media_id' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c03',
                                    'site_name' => '#main > table > tbody > tr:nth-child(' . $iPlus . ') > td.c04',
                                    'approval' => $approval_selector,
                                    // 'approval_price' => $approvalprice_selector 
                                    
                                );
                                
                                foreach ( $selector_for_site as $key => $value ) {
                                    if ( $key == 'site_name' ) {
                                        
                                        $rentrack_site[ $y ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                                        
                                    } //$key == 'site_name'
                                    // elseif($key == 'approval_price'){

                                    //     $rentrack_site[ $y ][ $key ] = $this->monthlySearchService->calc_approval_price(
                                    //         trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) ), 5);
                                        
                                    // }
                                    else {
                                        
                                        $rentrack_site[ $y ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                                    }
                                    
                                }
                                $rentrack_site[ $y ][ 'approval_price' ] = $rentrack_site[ $y ][ 'approval' ] * $product_info->price;

                                $y++;
                            }

                        } 
                        /*
                        サイトデータ・月次データ保存
                        */
                        $this->monthlySearchService->save_site( json_encode( $rentrack_site ) );
                        $this->monthlySearchService->save_monthly( json_encode( $rentrack_data ) );
                        
                        //var_dump($crawler_for_site);
                    } //$product_infos as $product_info
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'type' => 'Monthly',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                            throw $e;
            }        
        } );
        
    }
    
}