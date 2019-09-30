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

class CrossPartnerController extends Controller
{
/**
　再現性のある数値を生成 サイトIDとして適用
*/
    public function siteCreate($siteName,$seed){
      $siteId='';
      //echo $siteName;
      mt_srand($seed, MT_RAND_MT19937);
      foreach(str_split($siteName) as $char) {
            $char_array[] = ord($char) + mt_rand(0, 255) ;
      }
      //var_dump($char_array);
      $siteId = mb_substr(implode($char_array),0,100);
      //echo $siteId;

      return $siteId;
    }    
    public function crosspartner( $id , $pass , $product = null, $sponsor = null) //OK
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
            
            $asp_info = Asp::where('id','=',10)->get()->toArray();
 
                try{
                    $crawler =
                        $browser->visit( $asp_info[0]['login_url'] )
                        ->keys( $asp_info[0]['login_key'], $id )
                        ->keys( $asp_info[0]['password_key'], $pass )
                        ->click( $asp_info[0]['login_selector'] )
                        ->visit( $asp_info[0]['lp1_url'] )
                        ->crawler()->getUri();

                    if (strpos($crawler,'tops') !== false ){
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