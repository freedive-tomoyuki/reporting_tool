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

//header('Content-Type: text/html; charset=utf-8');

class PrescoController extends Controller
{
    //public $result ;

    public function presco( $id , $pass , $product, $sponsor = null ) //OK
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

        //$result = 0;
        // Chromeドライバーのインスタンス呼び出し
        $client = new Client( new Chrome( $options ) );
        $result = 0;

        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , $product, &$result)
        {
            
            $asp_info = Asp::where('id','=',1)->get()->toArray();
            //echo $asp_info;
                try{
                    $crawler_test = $browser->visit( $asp_info[0]['login_url'] )
                        ->type( $asp_info[0]['login_key'], $id )
                        ->type( $asp_info[0]['password_key'], $pass )
                        ->click( $asp_info[0]['login_selector'] )
                        ->visit( $asp_info[0]['lp1_url'] . $product )
                        ->crawler()->getUri();

                    if (strpos($crawler_test,'home') !== false ){
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
        });
        //var_dump($result);
        return $result;
    }
}