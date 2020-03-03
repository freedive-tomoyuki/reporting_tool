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
        $products =  json_decode($this->monthlySearchService->BasetoProduct( 1, $product_base_id ),true);
        
        $client = new Client( new Chrome( $options ) );
        
        foreach($products as $p ){
            
            $product_id = $p['id'];
            $product_name = $p['product'];

            $client->browse( function( Browser $browser ) use (&$crawler, $product_id, $product_name)
            {
                try{
                        $product_infos = \App\Product::all()->where( 'id', $product_id );
                        
                        foreach ( $product_infos as $product_info ) {
                            
                            $crawler = $browser
                            ->visit( $product_info->asp->login_url )
                            ->type( $product_info->asp->login_key, $product_info->login_value )
                            ->type( $product_info->asp->password_key, $product_info->password_value )
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
                                
                                $unit_price = $product_info->price;
                                
                                $data[ 'date' ] = date( 'Y-m-d', strtotime( '-1 day' ) );

                                if(count($node->filter( $selector_this['approval'] ))){
                                    $data[ 'approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_this['approval'] )->text() ) );
                                }else{  $data[ 'approval' ] = 0;}//throw new \Exception(  $selector_this['approval'].'要素が存在しません。'); }

                                if(count($node->filter( $selector_this['approval_price'] ))){
                                    $data[ 'approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_this['approval_price'] )->text() ) )
                                                                    ,1);
                                }else{  $data[ 'approval_price' ] = 0;}//throw new \Exception($selector_this['approval_price'].'要素が存在しません。'); }

                                // $data[ 'approval_price' ] = $data[ 'approval' ] * $unit_price;

                                if ( date( 'Y/m/d' ) == date( 'Y/m/01' ) ) {
                                    $data[ 'last_date' ] = date( 'Y-m-t', strtotime( '-2 month' ) );
                                }
                                else {
                                    $data[ 'last_date' ] = date( 'Y-m-d', strtotime( 'last day of previous month' ) );
                                }
                                if(count($node->filter( $selector_before['approval']))){
                                    $data[ 'last_approval' ] = trim( preg_replace( '/[^0-9]/', '', $node->filter( $selector_before['approval']  )->text() ) );
                                }else{  $data[ 'last_approval' ] = 0;}//throw new \Exception(  $selector_before['approval'].'要素が存在しません。'); }

                                if(count($node->filter( $selector_before['approval_price'] ))){
                                    $data[ 'last_approval_price' ] = $this->monthlySearchService->calc_approval_price( 
                                                                        trim( preg_replace( '/[^0-9]/', '', $node->filter(  $selector_before['approval_price'] )->text() ) )
                                                                    ,1);
                                }else{  $data[ 'last_approval_price' ] = 0;}//throw new \Exception($selector_before['approval_price'].'要素が存在しません。'); }

                                // $data[ 'last_approval_price' ] = $data[ 'last_approval' ] * $unit_price;

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
                                'product_id' => $product_name,
                                'asp' => 'A8',
                                'type' => 'Monthly',
                                ];
                                //echo $e->getMessage();
                    Mail::to('t.sato@freedive.co.jp')->send(new Alert($sendData));
                }        
            } );
        }
    }
    
}