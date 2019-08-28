<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte;
use JonnyW\PhantomJs\Client;
use Symfony\Component\DomCrawler\Crawler;

class Test extends Controller
{
    //Test a8
    public function index() {
/*        $request = PhantomJs::get('https://www.a8.net/');
        $response = PhantomJs::send($request);
        if($response->getStatus() === 200) {
            echo $response->getContent();
        }*/

        /*$client = Client::getInstance();
        $client->getEngine()->setPath('../bin/phantomjs');

        $request = $client->getMessageFactory()->createRequest('https://www.a8.net/', 'GET');
        $response = $client->getMessageFactory()->createResponse();

        
       $client->send($request, $response);
        echo $response->getContent();
        
        $url = 'https://www.a8.net/';

        $request->setUrl($url);

        $client->send($request, $response);
        
        $crawler = new Crawler($response->getContent());
        //$text = $crawler->filter('div')->text();
        $html = $crawler->filter('div')->html();
        var_dump($html);*/

        /*if($response->getStatus() === 200) {

        // Dump the requested page content
            echo $response->getContent();

        }*/

        //$crawler = new Crawler($response->getContent());
        //$text = $crawler->filter('div')->text();
        //var_dump($crawler); 


        $client = Goutte::request('GET','https://www.a8.net/');

        $loginform = $client->filter('#headerRight > form')->form();

        //ログインIDとパスワードを入力します
        $loginform['eclogin'] = 'auhikari';
        $loginform['ecpasswd'] = 'qmfk4Ivr';
        $loginform['moa'] = '/a8';

        //ログインフォームにログイン
        $crawler = Goutte::submit($loginform);
        //ログイン後のタイトル表示
        echo $crawler->filter('title')->text();
        $xpaths = [
            '//*[@id="element"]/tbody/tr[1]/td[1]',
            '//*[@id="element"]/tbody/tr[1]/td[2]',
            '//*[@id="element"]/tbody/tr[1]/td[3]'
        ];
        // 「ここをクリック」をクリックしてリンク先に遷移
        //$head = Goutte::request('HEAD', 'http://example.com/health-check/');
        $link = Goutte::request('GET',"https://adv.a8.net/a8v2/ecQuickReportAction.do?reportType=21&insId=s00000010979002");
        $link = str_replace( 'src="/' , 'src="https://adv.a8.net/' , $link -> html() );
        $link = str_replace( 'href="/' , 'href="https://adv.a8.net/' , $link );
        
        echo $link;
        /*foreach($xpaths as $xpath ){
            $link->filterXpath($xpath)->each(function ($node) {
                echo $node->text() . "\n";
                echo $node->html() . "\n";
            });*/
            //echo $head->html();
            //echo $link->html();
        //}


    }
    public function afb() {
/*
        $afb_client = Goutte::setHeader('User-Agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19');

        $afb_client = Goutte::request('GET','https://www.afi-b.com/general/client/howto');

        $afb_client = str_replace( 'src="/' , 'src="https://www.afi-b.com/' , $afb_client -> html() );
        $afb_client = str_replace( 'href="/' , 'href="https://www.afi-b.com/' , $afb_client );
        

        echo $afb_client;
*/

        $client = Client::getInstance();
        $client->getEngine()->setPath('../bin/phantomjs');

        $request = $client->getMessageFactory()->createRequest('https://www.afi-b.com/', 'GET');
        $response = $client->getMessageFactory()->createResponse();

        
       $client->send($request, $response);
        echo $response->getContent();
        
        $url = 'https://www.afi-b.com/';

        $request->setUrl($url);

        $crawler = $client->send($request, $response);
        
        //$crawler = new Crawler($response->getContent());
        //$text = $crawler->filter('div')->text();
        //$html = $crawler->filter('div')->html();
        //$crawler = str_replace( 'src="/' , 'src="https://www.afi-b.com/' , $crawler -> html() );
        //$crawler = str_replace( 'href="/' , 'href="https://www.afi-b.com/' , $crawler );
        
        $crawler = $crawler->filterXpath('//*[@id="pageTitle"]/aside[2]/g-header-loginform/div[1]/form')->form();

        //ログインIDとパスワードを入力します
/*
        $afb_loginform['login_name'] = 'broadwimax';
        $afb_loginform['password'] = 'c1xVXpjw';

        $afb_crawler = Goutte::submit($afb_loginform);
*/
        var_dump($crawler);


        //$afb_loginform = $afb_client->filterXpath('//*[@id="pageTitle"]/aside[2]/g-header-loginform/div[1]/form')->form();
        /*$afb_loginform = $afb_client->filter('.m-form__wrap > form')->form();

        //ログインIDとパスワードを入力します
        $afb_loginform['login_name'] = 'broadwimax';
        $afb_loginform['password'] = 'c1xVXpjw';

        //ログインフォームにログイン
        $afb_crawler = Goutte::submit($afb_loginform);
        //ログイン後のタイトル表示
        echo $afb_crawler->filter('title')->text();*/
        /*$xpaths = [
            '//*[@id="element"]/tbody/tr[1]/td[1]',
            '//*[@id="element"]/tbody/tr[1]/td[2]',
            '//*[@id="element"]/tbody/tr[1]/td[3]'
        ];
        // 「ここをクリック」をクリックしてリンク先に遷移
        $link = Goutte::request('GET',"https://adv.a8.net/a8v2/ecQuickReportAction.do?reportType=21&insId=s00000010979002");
        foreach($xpaths as $xpath ){
            $link->filterXpath($xpath)->each(function ($node) {
                echo $node->text() . "\n";
                //echo $node->html() . "\n";
            });
        }*/
    }
   public function at() {

        $afb_client = Goutte::request('GET','https://www.accesstrade.ne.jp/');

        //$afb_loginform = $afb_client->filterXpath('//*[@id="pageTitle"]/aside[2]/g-header-loginform/div[1]/form')->form();
        $afb_loginform = $afb_client->filter('#login_m > form')->form();

        //ログインIDとパスワードを入力します
        $afb_loginform['userName'] = 'ac-si';
        $afb_loginform['password'] = '50dosuvu3v9';

        //ログインフォームにログイン
        $afb_crawler = Goutte::submit($afb_loginform);
        //ログイン後のタイトル表示
        echo $afb_crawler->filter('title')->text();
    }
   public function vc() {

        $vc_client = Goutte::request('GET','https://mer.valuecommerce.ne.jp/?type=4');
        //echo $vc_client->filter('#login_mer')->text();
        //$afb_loginform = $afb_client->filterXpath('//*[@id="pageTitle"]/aside[2]/g-header-loginform/div[1]/form')->form();
        $vc_loginform = $vc_client->filter('#login_mer')->form();

        //ログインIDとパスワードを入力します
        $vc_loginform['login_form[email_address]'] = 'imai@surprizz.co.jp';
        $vc_loginform['login_form[password]'] = 'Wimax12345';

        //ログインフォームにログイン
        $vc_crawler = Goutte::submit($vc_loginform);

        $cookies = Goutte::getCookieJar()->all();
        file_put_contents("./cookie/cookiefile", serialize($cookies));

        $cookies = unserialize(file_get_contents("./cookie/cookiefile"));
        if (! empty($cookies)) Goutte::getCookieJar()->updateFromSetCookie($cookies);

        $vc_client = Goutte::request('GET','https://mer.valuecommerce.ne.jp/home/');

        //保存してたクッキーをセットする
        //Goutte::getCookieJar()->updateFromSetCookie($cookies);

        //ログイン後のタイトル表示
        echo $vc_crawler->filter('title')->text();
        echo $vc_crawler->html();

    }
}
