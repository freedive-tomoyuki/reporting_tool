<?php
namespace App\Http\Controllers\Admin\Asp\Check;

use Illuminate\Http\Request;
use Laravel\Dusk\Browser;
use App\Http\Controllers\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Asp;
use App\Product;
use App\ProductBase;

class AccessTradeController extends Controller
{
    
    public function accesstrade( $id , $pass , $product = null, $sponsor = null) //OK
    {
        
        Browser::macro('crawler', function () {
        return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
        });
        
        $options = [
                '--window-size=1920,3000',
                '--start-maximized',
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--lang=ja_JP',
        ];


        //$product_id = $this->BasetoProduct( 2, $product_base_id );
        $client = new Client( new Chrome( $options ) );
        $result = 0;

        $client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , &$result)
        //$client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , $product, &$result)
        {
            
            $asp_info = Asp::where('id','=', 2)->get()->toArray();
            
            try{
                    $crawler = $browser->visit( $asp_info[0]['login_url'] )
                            ->type( $asp_info[0]['login_key'], $id )
                            ->type( $asp_info[0]['password_key'], $pass )
                            ->click( $asp_info[0]['login_selector'] )
                            ->crawler()->getUri();

                        if (strpos($crawler,'account') !== false ){
                            $result = 1;
                            //var_dump($result);
                        }else{
                            $result = 0;
                            //var_dump($result);
                        }
                        return $result;

            }catch ( Exception $ex ) {
                return $result = 0;
            }
            //var_dump($crawler);
        });
        //var_dump($result);
        return $result;
    }
}