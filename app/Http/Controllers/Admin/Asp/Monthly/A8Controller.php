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
use DB;
//header('Content-Type: text/html; charset=utf-8');

class A8Controller extends MonthlyCrawlerController
{
    
    public function a8( $product_base_id )
    {
        
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
        $product_id = $this->monthlySearchService->BasetoProduct( 1, $product_base_id );
        
        $client = new Client( new Chrome( $options ) );
        
        $client->browse( function( Browser $browser ) use (&$crawler, $product_id)
        {
            try{
                    $product_infos = \App\Product::all()->where( 'id', $product_id );
                    
                    foreach ( $product_infos as $product_info ) {
                        
                        $crawler = $browser
                        ->visit( $product_info->asp->login_url )
                        ->type( $product_info->login_key, $product_info->login_value )
                        ->type( $product_info->password_key, $product_info->password_value )
                        ->click( $product_info->asp->login_selector )
                        ->visit( $product_info->asp->lp2_url )
                        ->select( '#reportOutAction > table > tbody > tr:nth-child(2) > td > select', '21' )
                        ->radio( 'insId', $product_info->asp_product_id )
                        ->click( '#reportOutAction > input[type="image"]:nth-child(3)' )
                        ->crawler();
                        
                        
                        $selector_this   = array(
                             'approval' => '#element > tbody > tr:nth-child(1) > td:nth-child(10)',
                            'approval_price' => '#element > tbody > tr:nth-child(1) > td:nth-child(13)' 
                        );
                        $selector_before = array(
                             'approval' => '#element > tbody > tr:nth-child(1) > td:nth-child(10)',
                            'approval_price' => '#element > tbody > tr:nth-child(1) > td:nth-child(13)' 
                        );
                        
                        $a8_data = $crawler->each( function( Crawler $node ) use ($selector_this, $selector_before, $product_info)
                        {
                            $data              = array( );
                            $data[ 'asp' ]     = $product_info->asp_id;
                            $data[ 'product' ] = $product_info->id;
                            
                            foreach ( $selector_this as $key => $value ) {

                                if($key == 'approval_price'){
                                    $data[ $key ]   = $this->monthlySearchService->calc_approval_price(trim( $node->filter( $value )->text() ), 1);
                                }else{
                                    $data[ $key ]   = trim( $node->filter( $value )->text() );
                                }
                                
                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );
                            
                            } //$selector_this as $key => $value
                            
                            foreach ( $selector_before as $key => $value ) {
                            
                                if($key == 'approval_price'){
                                    //$data[ $key ]   = $this->monthlySearchService->calc_approval_price(trim( $node->filter( $value )->text() ));
                                    $data[ 'last_' . $key ] = $this->monthlySearchService->calc_approval_price(trim( $node->filter( $value )->text() ), 1);
                                }else{
                                    $data[ 'last_' . $key ] = trim( $node->filter( $value )->text() );
                                }

                                //$data['last_date'] = date('Y-m-d', strtotime('last day of previous month'));
                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( '-2 month' ) );
                                } //date( 'Y/m/d' ) == date( 'Y/m/01' )
                                else {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                }
                            } //$selector_before as $key => $value
                            return $data;
                            
                        } );
                        
                        //var_dump( $a8_data );
                        
                        $this->monthlySearchService->save_monthly( json_encode( $a8_data ) );
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