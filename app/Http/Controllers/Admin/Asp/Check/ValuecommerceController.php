<?php

namespace App\Http\Controllers\Admin\Asp\Check;

use Laravel\Dusk\Browser;
use App\Http\Controllers\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Asp;


class ValuecommerceController extends Controller
{
    
    public function valuecommerce( $id , $pass , $product = null, $sponsor = null) //OK
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
        {
            $asp_info = Asp::where('id','=',3)->get()->toArray();
 
            try{
                    $crawler = $browser->visit( $asp_info[0]['login_url'] )
                        ->type( $asp_info[0]['login_key'], $id )
                        ->type( $asp_info[0]['password_key'], $pass )
                        ->click( $asp_info[0]['login_selector'] )
                        ->visit( 'https://mer.valuecommerce.ne.jp/report/simple_summary/' )
                        ->crawler()->getUri();
                        //echo $crawler ;

                    if (strpos($crawler,'report') !== false ){
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