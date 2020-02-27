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

class MoshimoController extends MonthlyCrawlerController
{

    public function moshimo( $product_base_id ) //OK
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
        $product_id = $this->monthlySearchService->BasetoProduct( 13, $product_base_id );

        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        
        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );

                    foreach ( $product_infos as $product_info ) {
                        // $crawler サイト用　をフィルタリング
                        
                        $count           = 0;
                        //０：今月分のデータ取得　１：先月のデータ取得
                        
                        for ( $y = 0; $y < 2; $y++ ) {
                            //検索用の日付取得
                            if ( $y == 0 ) {
                                $s_date = date( 'Y/m/01', strtotime( '-1 day' ) );
                                $e_date = date( 'Y/m/d', strtotime( '-1 day' ) );
                            } 
                            else {
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $s_date = date( 'Y/m/01', strtotime( '-2 month' ) );
                                    $e_date = date( 'Y/m/t', strtotime( '-2 month' ) );
                                }
                                else {
                                    $s_date = date( 'Y/m/01', strtotime( 'first day of previous month' ) );
                                    $e_date = date( 'Y/m/t', strtotime( 'last day of previous month' ) );
                                }
                            }
                            //スクレイピング実施
                            $i =  1; //行番号
                            $url = "https://secure.moshimo.com/af/merchant/report/kpi/site?promotion_id=" . $product_info->asp_product_id . "&from_date=" . $s_date . "&to_date=" . $e_date ;
                            $crawler = $browser->visit( $url )->crawler();
                            echo "検索クロールクリア";

                            //初期値＋固定値設置（月次データ）
                            $moshimo_data[0][ 'asp' ]     = $product_info->asp_id;
                            $moshimo_data[0][ 'product' ] = $product_info->id;
                            $moshimo_data[0][ 'date' ]       = date( 'Y/m/d', strtotime( '-1 day' ) );
                            
                            echo '<pre>';
                            var_dump( $crawler);
                            echo '</pre>';

                            if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                $moshimo_data[0][ 'last_date' ] = date( 'Y/m/t', strtotime( '-2 month' ) );
                            }
                            else {
                                $moshimo_data[0][ 'last_date' ] = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                            }

                            //切り口：サイト別の表をスクレイピング
                            while ( $crawler->filter( '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-approve > div > p:nth-child(1)' )->count() > 0 ) {
                                //echo $i;
                                echo "ループクロール中(".$i.")";
                                
                                $moshimo_site[ $count ][ 'product' ] = $product_info->id;
                                $moshimo_site[ $count ][ 'asp' ]   = $product_info->asp_id;
                                if ( $x == 0 ) {
                                    $moshimo_site[ $count ][ 'date' ] = date( 'Y/m/d', strtotime( '-1 day' ) );
                                } //$x == 0
                                else { //2周目
                                    if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                        $moshimo_site[ $count ][ 'date' ] = date( 'Y/m/t', strtotime( '-2 month' ) );
                                    } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                    else {
                                        $moshimo_site[ $count ][ 'date' ] = date( 'Y/m/d', strtotime( 'last day of previous month' ) );
                                    }
                                }
                                $selector   = array(
                                    'approval'          => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-approve > div > p:nth-child(1)', 
                                    'approval_price'    => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-approve > div > p:nth-child(2)',
                                    'media_id'          => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-name > div > p:nth-child(1)',
                                    'site_name'         => '#report > div.result > table > tbody > tr:nth-child('.$i.') > td.value-name > div > p:nth-child(1) > a',
                                );

                                foreach ( $selector_for_site as $key => $value ) {
                                    echo "Filterループクロール中(".$key.")";
    
                                    if(count($crawler->filter( $value ))){
                                        if ( $key == 'site_name' ) {
                                            $moshimo_site[ $count ][ $key ] = trim( $crawler->filter( $value )->text() );
                                        }elseif($key == 'media_id' ){
                                            $member_id_array = array( );
                                            $member_id =  trim( $crawler->filter( $value )->text()) ;
                                            echo "メディアID";
                                            preg_match( '/(\d+)/', $member_id, $member_id_array );
                                            var_dump($member_id_array);
                                            $moshimo_site[$count][$key] = $member_id_array[ 1 ];

                                        }elseif($key == 'approval_price'){
                                            $moshimo_site[ $count ][ $key ] = $this->monthlySearchService->calc_approval_price( 
                                                                                trim( preg_replace( '/[^0-9]/', '', $crawler->filter( $value )->text() ) )
                                                                            ,13);
                                            if( $y == 0 ){
                                                $moshimo_data[0][ 'approval_price' ] += ( is_numeric($moshimo_site[ $count ][ $key ]))? $moshimo_site[ $count ][ $key ] : 0;
                                            }else{
                                                $moshimo_data[0][ 'last_approval_price' ] += ( is_numeric($moshimo_site[ $count ][ $key ]))? $moshimo_site[ $count ][ $key ] : 0;
                                            }
                                        }
                                        else {
                                            $moshimo_site[ $count ][ $key ] =  trim( preg_replace( '/[^0-9]/', '', $crawler->filter( $value )->text() ) );
                                                                    
                                            if($key == 'approval' &&  $y == 0){
                                                $moshimo_data[0][ 'approval' ]   += ( is_numeric($moshimo_site[ $count ][ $key ]))? $moshimo_site[ $count ][ $key ] : 0;
                                            }elseif($key == 'approval' &&  $y == 1){
                                                $moshimo_data[0][ 'last_approval' ] += ( is_numeric($moshimo_site[ $count ][ $key ]))? $moshimo_site[ $count ][ $key ] : 0;
                                            }
                                            
                                        }
                                    }else{
                                        throw new \Exception($value.'要素が存在しません。');
                                    }
                                }
                                
                                $i++;
                                $count++;
                            }
                        }
                        echo "<pre>";
                        var_dump($moshimo_data);
                        var_dump($moshimo_site);
                        echo "</pre>";
                        /*
                        サイトデータ・日次データ保存
                        */
                        // $this->monthlySearchService->save_site( json_encode( $moshimo_site ) );
                        // $this->monthlySearchService->save_monthly( json_encode( $moshimo_data ) );
                    
                    }
            }
            catch(\Exception $e){
                $sendData = [
                            'message' => $e->getMessage(),
                            'datetime' => date('Y-m-d H:i:s'),
                            'product_id' => $product_id,
                            'asp' => 'もしも',
                            'type' => 'Monthly',
                            ];
                            //echo $e->getMessage();
                Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
            }        
        } );
        
    }
}