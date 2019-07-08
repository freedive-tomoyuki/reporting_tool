<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\Http\Controllers\EstimateController;

use Laravel\Dusk\Browser;
use Symfony\Component\DomCrawler\Crawler;

//use Revolution\Salvager\Facades\Salvager;

use Revolution\Salvager\Client;
use Revolution\Salvager\Drivers\Chrome;

use App\Dailydata;
use App\Product;
use App\Dailysite;
use App\ProductBase;
use App\Monthlydata;
use App\Monthlysite;
use App\Schedule;
//header('Content-Type: text/html; charset=utf-8');

class CheckController extends Controller
{

    public function index()
    {


        $product_bases = ProductBase::all();
        echo date('Y-m-d', strtotime('-1 day'));
        return view('cralwerdaily',compact('product_bases'));
    }
    /**
      ID　パスワードの確認用
    */
    public function check()
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
              $product_id = 13;

              $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {

              $product_infos = \App\Product::all()->where('id',$product_id);
                
              foreach ($product_infos as $product_info){

                  //$crawler_1 = $browser->getInternalResponse()->getStatus();
                  $crawler_2 = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )
                                  ->click($product_info->asp->login_selector) 
                                  ->crawler();
                  echo '<pre>';
                  echo $crawler_2->html();
                  echo '</pre>';
                }
            });
    }

  }

