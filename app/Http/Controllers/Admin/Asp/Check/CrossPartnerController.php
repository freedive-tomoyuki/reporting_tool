<?php

namespace App\Http\Controllers\Admin\Asp\Check;

use Laravel\Dusk\Browser;
use App\Http\Controllers\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Asp;

//header('Content-Type: text/html; charset=utf-8');

class CrossPartnerController extends Controller
{
   
    public function crosspartner( $id , $pass , $product = null, $sponsor = null) //OK
    {
        /*
        ChromeDriverのオプション設定
        */
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

        // Chromeドライバーのインスタンス呼び出し
        //$client = new Client( new Chrome( $options ) );
        $client = new Client( new Chrome( $options ) );
        $result = 0;

        //Chromeドライバー実行
        $client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , $product , $sponsor, &$result)
        //$client->browse( function( Browser $browser ) use (&$crawler, $id , $pass , $product, &$result)
        {
            
            $asp_info = Asp::where('id','=',10)->get()->toArray();
 
                try{
                    // $logout = $browser->visit('http://crosspartners.net/agent/logins/logout')->crawler();
                    // \Log::debug($logout->html());

                    $crawler =
                        $browser
                        // ->visit( $asp_info[0]['login_url'] )
                        // ->keys( $asp_info[0]['login_key'], $id )
                        // ->keys( $asp_info[0]['password_key'], $pass )
                        // ->click( $asp_info[0]['login_selector'] )
                        // //->visit( $asp_info[0]['lp1_url'] )
                        // ->crawler()->getUri();

                        ->visit( $$asp_info[0]['login_url'] )
                        ->keys( $asp_info[0]['login_key'], $id )
                        ->keys( $asp_info[0]['password_key'], $pass )
                        ->click(  $asp_info[0]['login_selector'] )
                        ->visit( $asp_info[0]['lp1_url'])
                        ->visit('http://crosspartners.net/agent/clients/su/'.$sponsor)
                        ->crawler()->getUri();
                        \Log::debug($product);
                        \Log::debug($sponsor);
                        \Log::debug($asp_info[0]['login_key']);
                        \Log::debug($asp_info[0]['password_key']);
                        \Log::debug($asp_info[0]['login_selector']);

                        // $crawler = $crawler->getUri();
                         \Log::debug($crawler);
                    if (strpos($crawler,'tops') !== false ){
                        $result = 1;
                        //var_dump($result);
                    }else{
                        $result = 0;
                        //var_dump($result);
                    }
                    //最後にログアウト
                    // $browser->visit('http://crosspartners.net/agent/logins/logout');

                    return $crawler;

                }catch ( Exception $ex ) {
                    return $crawler = 0;
                }
            //var_dump($crawler);
        });
        //var_dump($result);
        \Log::debug($result);
        return $result;
    }
}