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


class AfbController extends Controller
{
    
    public function afb( $id , $pass , $product = null, $sponsor = null) //OK
    {
        /*
        ChromeDriverのオプション設定
        */
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

        // Chromeドライバーのインスタンス呼び出し
        //$client = new Client( new Chrome( $options ) );
        $client = new Client( new Chrome( $options ) );
        $result = 0;

        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , &$result)
        //$client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , $product, &$result)
        {

            $asp_info = Asp::where('id','=',4)->get()->toArray();
            try{
                $crawler = $browser->visit( 'https://www.afi-b.com' )
                        ->type( $asp_info[0]['login_key'], $id )
                        ->type( $asp_info[0]['password_key'], $pass )
                        ->click( $asp_info[0]['login_selector'] )
                        ->crawler()->getUri();

                        if (strpos($crawler,'main') !== false ){
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