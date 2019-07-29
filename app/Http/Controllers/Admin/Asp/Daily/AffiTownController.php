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
        $product_id = $this->BasetoProduct( 7, $product_base_id );
        
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
            
            foreach ( $product_infos as $product_info ) {
                // /var_dump($product_info->asp);
                /*
                クロール：ログイン＝＞[日別売上検索]より検索
                */
                
                $crawler = $browser->visit( $product_info->asp->login_url )->type( $product_info->asp->login_key, $product_info->login_value )->type( $product_info->asp->password_key, $product_info->password_value )->click( $product_info->asp->login_selector )->visit( "https://affi.town/adserver/merchant/report/dailysales.af" )->visit( "https://affi.town/adserver/merchant/report/dailysales.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&since=" . $s_date . "&until=" . $e_date )->type( '#all_display > p > input[type=search]', '合計' )->crawler();
                //echo $crawler->html();

                $crawler2 = $browser->visit( "https://affi.town/adserver/report/mc/impression.af" )->visit( "https://affi.town/adserver/merchant/report/impression.af?advertiseId=" . $product_info->asp_product_id . "&mediaId=&fromDate=" . $s_date . "&toDate=" . $e_date )->type( '#all_display > p > input[type=search]', '合計' )->crawler();
                echo $crawler2->html();
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
                     'imp' => '#all_display > table > tbody:nth-child(2) > tr.visible.striped > td:nth-child(4)',
                );
                
                /*
                $crawler　をフィルタリング
                */
                $affitown_data = $crawler->each( function( Crawler $node ) use ($selector1, $product_info)
                {
                    
                    $data              = array( );
                    $data[ 'asp' ]     = $product_info->asp_id;
                    $data[ 'product' ] = $product_info->id;
                    //$data['imp'] = 0;
                    $data[ 'date' ]    = date( 'Y-m-d', strtotime( '-1 day' ) );
                    
                    foreach ( $selector1 as $key => $value ) {
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector1 as $key => $value
                    return $data;
                    
                } );
                /*
                $crawler(Imp)　をフィルタリング
                */
                $affitown_data_imp = $crawler2->each( function( Crawler $node ) use ($selector2, $product_info)
                {
                    
                    $data              = array( );
                    
                    foreach ( $selector2 as $key => $value ) {
                        $data[ $key ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $value )->text() ) );
                    } //$selector1 as $key => $value
                    return $data;
                    
                } );
                var_dump( $affitown_data_imp );
                /*
                サイト抽出　
                */
                
                
                $crawler_for_count_site = $browser->visit( "https://affi.town/adserver/merchant/join.af?joinApprove=2" )->crawler();
                
                $site_count = 1;
                
                while ( $crawler_for_count_site->filter( '#form_link_approval > table > tbody > tr:nth-child(' . $site_count . ') > td:nth-child(2)' )->count() == 1 ) {
                    $site_count++;
                } //$crawler_for_count_site->filter( '#form_link_approval > table > tbody > tr:nth-child(' . $site_count . ') > td:nth-child(2)' )->count() == 1
                $site_count--;
                
                //echo "カウントここ" . $site_count . "カウントここ";
                
                $crawler_for_site = $browser->visit( "https://affi.town/adserver/report/mc/site.af?advertiseId=" . $product_info->asp_product_id . "&fromDate=" . $s_date . "&toDate=" . $e_date )->type( '#all_display > p > input[type=search]', '合計' )->crawler();
                //for( $i = 1 ; 20 >= $i ; $i++ ){
                $i                = 1;
                //$selector_end = ;
                
                while ( trim( $crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a' )->text() ) != "合計" ) {
                    
                    $affitown_site[ $i ][ 'product' ] = $product_info->id;
                    $affitown_site[ $i ][ 'imp' ]     = 0;
                    
                    $selector_for_site = array(
                         'media_id' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td.underline',
                        'site_name' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a',
                        'click' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(4) > p',
                        'cv' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(5) > p',
                        'price' => '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(6) > p' 
                    );
                    
                    foreach ( $selector_for_site as $key => $value ) {
                        if ( $key == 'site_name' ) {
                            
                            $affitown_site[ $i ][ $key ] = trim( $crawler_for_site->filter( $value )->text() );
                            
                        } //$key == 'site_name'
                        else {
                            
                            $affitown_site[ $i ][ $key ] = trim( preg_replace( '/[^0-9]/', '', $crawler_for_site->filter( $value )->text() ) );
                        }
                        
                    } //$selector_for_site as $key => $value
                    $calData                       = json_decode( json_encode( json_decode( $this->cpa( $affitown_site[ $i ][ 'cv' ], $affitown_site[ $i ][ 'price' ], 7 ) ) ), True );
                    $affitown_site[ $i ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                    $affitown_site[ $i ][ 'cost' ] = $calData[ 'cost' ];
                    $affitown_site[ $i ][ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                    
                    $i++;
                } //trim( $crawler_for_site->filter( '#all_display > table > tbody > tr:nth-child(' . $i . ') > td:nth-child(2) > a' )->text() ) != "合計"
                
                $affitown_data[ 0 ][ 'partnership' ] = $site_count;
                
                $affitown_data[ 0 ][ 'active' ] = $i;
                
                $affitown_data[ 0 ][ 'imp' ] = $affitown_data_imp[ 0 ][ 'imp' ];
                
                $calData                      = json_decode( json_encode( json_decode( $this->cpa( $affitown_data[ 0 ][ 'cv' ], $affitown_data[ 0 ][ 'price' ], 7 ) ) ), True );
                $affitown_data[ 0 ][ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                $affitown_data[ 0 ][ 'cost' ] = $calData[ 'cost' ];
                
                //echo "<pre>";
                var_dump( $affitown_data );
                //var_dump( $affitown_site );
                //echo "</pre>";
                
                
                /*
                サイトデータ・日次データ保存
                */
                //$this->save_site( json_encode( $affitown_site ) );
                //$this->save_daily( json_encode( $affitown_data ) );
                
                //var_dump($crawler_for_site);
            } //$product_infos as $product_info
            
        } );
        
    }
}