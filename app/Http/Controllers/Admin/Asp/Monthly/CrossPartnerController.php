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
//header('Content-Type: text/html; charset=utf-8');

class CrossPartnerController extends MonthlyCrawlerController
{
  
    public function crosspartner( $product_base_id ) //OK
    {

        Browser::macro('crawler', function () {
        return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
        });
        
        $options = [
        '--window-size=1920,1080',
        '--start-maximized',
        '--headless',
        '--disable-gpu',
        '--no-sandbox'
        
        ];
        
        //案件の大本IDからASP別のプロダクトIDを取得
        $product_id = $this->monthlySearchService->BasetoProduct( 10, $product_base_id );

        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
        	        //var_dump($product_infos);
            
                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                        $start = date( 'Ym', strtotime( 'last day of '. date( 'Y-m', strtotime( '-2 month' ) )) );
                        $end   = date( 'Ym', strtotime( 'last day of previous month' ) );
                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                    else {
                         $start = date( 'Ym', strtotime( 'last day of previous month') );
                         $end   = date( 'Ym', strtotime( '-1 day' ) );
                    }
                    
                    foreach ( $product_infos as $product_info ) {

                        $crawler = $browser
                        ->visit( $product_info->asp->login_url )
                        ->keys( $product_info->asp->login_key, $product_info->login_value )
                        ->keys( $product_info->asp->password_key, $product_info->password_value )
                        ->click( $product_info->asp->login_selector )
                        ->visit( $product_info->asp->lp1_url )
                        ->visit('http://crosspartners.net/agent/clients/su/'.$product_info->asp_sponsor_id)
                        ->visit('http://crosspartners.net/master/result_reports/index/is_monthly:1')
                        ->visit('http://crosspartners.net/master/result_reports/ajax_paging/is_monthly:1/start:'.$start.'/end:'.$end.'/ad_id:'.$product_info->asp_product_id.'/sort:start/direction:asc?_=1564540874455')
                        ->crawler();

                        $selector_this   = array(
                                'approval' => 'table.highlight > tbody > tr:nth-child(2) > td:nth-child(11)',
                                //'approval_price' => 'table.highlight > tbody > tr:nth-child(2) > td:nth-child(12)'
                        );
                        $selector_before   = array(
                                'approval' => 'table.highlight > tbody > tr:nth-child(1) > td:nth-child(11)',
                                //'approval_price' => 'table.highlight > tbody > tr:nth-child(1) > td:nth-child(12)'
                        );

                        //今月・先月用のデータ取得selector
                        
                        $crosspartner_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                        {
                            
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;

                            $unit_price = $product_info->price;
                            
                            $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            if(count($node->filter( $selector_this['approval'] ))){
                                $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval'] )->text() ) );
                            }else{ throw new \Exception( $selector_this['approval'].'要素が存在しません。'); }

                            $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                            }
                            else {
                                $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                            }
                            if(count($node->filter( $selector_before['approval'] ))){
                                $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval']  )->text() ) );
                            }else{ throw new \Exception( $selector_before['approval'].'要素が存在しません。'); }

                            $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;

                            return $data;
                        } );

                        /*
                          $crawler サイト用　をフィルタリング
                        */
                        $count      = 0;
                        
                        for ( $i = 0; $i < 2; $i++ ) {
                        	$iPlus       = 1;

                            if ( $i == 0 ) { //今月
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                                    $searchMonth = date( 'Ym', strtotime( '-1 day' ) );
                                    $date   = date( 'Y-m-d', strtotime( '-1 day' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else {
                                    $searchMonth = date( 'Ym' );
                                    $date   = date( 'Y-m-d', strtotime( '-1 day' ) );
                                }
                            } //$x == 0
                            else { //先月
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) { //1日のクロールの場合
                                    $searchMonth = date( 'Ym', strtotime( '-2 month' ) );
                                    $date   = date( 'Y-m-t', strtotime( '-2 month' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else {
                                    $searchMonth = date( 'Ym', strtotime( 'last day of previous month' ) );
                                    $date   = date( 'Y-m-t', strtotime( 'last day of previous month' ) );
                                }
                            }
                            
                            
                            $crawler_for_site = $browser
                                                ->visit("http://crosspartners.net/master/result_reports/index/is_partners:1")
                                                ->visit("http://crosspartners.net/master/result_reports/ajax_paging/is_partners:1/start:".$searchMonth."/end:".$searchMonth."/user_site_id:/ad_id:".$product_info->asp_product_id."?_=1564541544441" )
                                                ->crawler();

                            while ( trim( preg_replace( '/[\n\r\t ]+/', ' ', str_replace( "\xc2\xa0", " ", $crawler_for_site->filter( 'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(1)' )->count() ) ) ) ) {
                                $crosspartner_site[$count]['product'] = $product_info->id;

                                $selector_for_site = array(
                                        'media_id'  =>'table.highlight > tbody > tr:nth-child('.$iPlus.')',
                                        'approval'       =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(8)',
                                        //'approval_price'     =>'table.highlight > tbody > tr:nth-child('.$iPlus.') > td:nth-child(13)',
                                );

                                foreach($selector_for_site as $key => $value){
                                    
                                    if(count($crawler_for_site->filter( $value ))){
                                        if($key == 'media_id' ){
                                            $member_id_array = array( );
                                            $member_id_source = $crawler_for_site->filter($value)->each(function (Crawler $c) {
                                                return $c->attr('id');
                                            });
                                            preg_match( '/member_id:(\d+)/', $member_id_source[0], $member_id_array );
                                            $crosspartner_site[$count][$key] = $member_id_array[ 1 ];
                                        }else{
                                            $crosspartner_site[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                        }
                                    }else{ throw new \Exception( $value.'要素が存在しません。'); }
                                }
                                $crosspartner_site[ $count ][ 'approval_price' ] = $crosspartner_site[ $count ][ 'approval' ] * $product_info->price;
                                $crosspartner_site[ $count ][ 'date' ] = $date ;

                                $count++;
                                $iPlus++;

                            }
                            
                        }

                       /* echo "<pre>";
                        var_dump($crosspartner_data);
                        var_dump($crosspartner_site);
                        echo "</pre>";*/
                        /*
                        サイトデータ・日次データ保存
                        */
                        $this->monthlySearchService->save_site( json_encode( $crosspartner_site ) );
                        $this->monthlySearchService->save_monthly( json_encode( $crosspartner_data ) );
                    
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
            }        
        } );
        
    }
}