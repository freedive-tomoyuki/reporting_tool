<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte;
use JonnyW\PhantomJs\Client;
use Symfony\Component\DomCrawler\Crawler;

use App\Dailydata;
use App\Product;
use GuzzleHttp;


class DailydataController extends Controller
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
/*
        $profile = Product::where('asp_id', '1')->get();

        $profile_array = $profile->ToArray();

        $product =  $profile_array[0]['product'];
        $login_key = $profile_array[0]['login_key'];
        $login_value =  $profile_array[0]['login_value'];
        $password_key =  $profile_array[0]['password_key'];
        $password_value =  $profile_array[0]['password_value'];


        $client = Client::getInstance();
        $client->getEngine()->setPath('../bin/phantomjs');


        $request = $client->getMessageFactory()->createRequest();
        $response = $client->getMessageFactory()->createResponse();

        //$client->send($request, $response);
        //echo $response->getContent();
        
        //$url = 'https://www.afi-b.com/';
        $purl = 'https://adv.a8.net/a8v2/ecQuickReportAction.do?reportType=21&insId=s00000010979002';


        $data = array(
            $login_key => $login_value,
            $password_key => $password_value,
            'moa'] => '/a8',
        );

        $request->setMethod('POST');
        $request->addHeader('X-CSRF-TOKEN', csrf_token());
        $request->addHeader('cookie', header('cookie'));
        $request->setUrl($purl);
        $request->setRequestData($data);
        //$request->setCaptureDimensions($width, $height, $top, $left);

        $crawler = $client->send($request, $response);
        
        $crawler = new Crawler();
        $crawler->addHTMLContent($response->getContent(), "UTF-8"); 

        $html = $crawler->filter('div')->html();
        var_dump($crawler->html());*/

//========================================================


        $profile = Product::where('asp_id', '1')->get();

        $profile_array = $profile->ToArray();

        $product =  $profile_array[0]['id'];
        $login_key = $profile_array[0]['login_key'];
        $login_value =  $profile_array[0]['login_value'];
        $password_key =  $profile_array[0]['password_key'];
        $password_value =  $profile_array[0]['password_value'];

        $client = Goutte::request('GET','https://www.a8.net/');

        $loginform = $client->filter('#headerRight > form')->form();

        //ログインIDとパスワードを入力します
        $loginform[$login_key] = $login_value;
        $loginform[$password_key] = $password_value;

        $loginform['moa'] = '/a8';

        //ログインフォームにログイン
        $crawler = Goutte::submit($loginform);
        //ログイン後のタイトル表示
        echo $crawler->filter('title')->text();

        // 「ここをクリック」をクリックしてリンク先に遷移
        //$head = Goutte::request('HEAD', 'http://example.com/health-check/');
        $link = Goutte::request('GET',"https://adv.a8.net/a8v2/ecQuickReportAction.do?reportType=21&insId=s00000010979002");
        
        $xpaths = array (
        	'month' => '//*[@id="element"]/tbody/tr[1]/td[1]',
            'imp' => '//*[@id="element"]/tbody/tr[1]/td[5]',
            'click' => '//*[@id="element"]/tbody/tr[1]/td[6]',
            'cv' => '//*[@id="element"]/tbody/tr[1]/td[10]',
            'active' => '//*[@id="element"]/tbody/tr[1]/td[2]',
            'partnership' =>'//*[@id="element"]/tbody/tr[1]/td[3]',
        );
        

		echo date('Y/m');
		foreach($xpaths as $key => $value){
			$data[$key] = array();
            $data[$key] = trim($link->filterXpath($value)->text());
            if($key == 'month'){
				if( trim($data['month']) != date('Y/m')){
					break;
				}
			}

        }
        $link2 = Goutte::request('GET',"https://adv.a8.net/a8v2/ecQuickReportAction.do?reportType=21&insId=s00000010979001");
        //$link = str_replace( 'src="/' , 'src="https://adv.a8.net/' , $link -> html() );
        //$link = str_replace( 'href="/' , 'href="https://adv.a8.net/' , $link );
        
        #element > tbody > tr:nth-child(1) > td:nth-child(2)
        var_dump($data);

        //echo $link;
		/*$xpaths = [
            '//*[@id="element"]/tbody/tr[1]/td[2]',
            '//*[@id="element"]/tbody/tr[1]/td[3]',
            '//*[@id="element"]/tbody/tr[1]/td[4]'
        ];
		foreach($xpaths as $xpath ){
            $link->filterXpath($xpath)->each(function ($node) {
                echo $node->text() . "\n";
                //echo $node->html() . "\n";
            });
        }*/
        
            $crv = (intval($data['cv']) != 0)? intval($data['click']) / intval($data['cv']) : 0 ;
            
            $ctv = (intval($data['click']) != 0)?intval($data['imp']) / intval($data['click']) : 0 ;
        
        Dailydata::create(
            [
            'imp' => $data['imp'],
            'click' => $data['click'],
            'cv' => $data['cv'],
            'cvr' => ceil($crv),
            'ctr' => ceil($ctv),
            'active' => $data['active'],
            'partnership' => $data['partnership'],
            'asp_id' => 1,
            'product_id' => $product
            ]
        );

         //Dailydata::create(['name' => 'Flight 10'])
    }


    public function afb() {
/*
        $afb_client = Goutte::setHeader('User-Agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19');

        $afb_client = Goutte::request('GET','https://www.afi-b.com/general/client/howto');

        $afb_client = str_replace( 'src="/' , 'src="https://www.afi-b.com/' , $afb_client -> html() );
        $afb_client = str_replace( 'href="/' , 'href="https://www.afi-b.com/' , $afb_client );
        

        echo $afb_client;*/


        $client = Client::getInstance();
        $client->getEngine()->setPath('../bin/phantomjs');
        $client->getEngine()->addOption('--load-images=true');
        $client->getEngine()->addOption('--ignore-ssl-errors=true');
        $client->getEngine()->addOption("--web-security=no");
        $client->getEngine()->addOption('--ssl-protocol=tlsv1');
        $client->getEngine()->addOption('--cookies-file=' . "./cookie/cookiefile.txt");

        $request = $client->getMessageFactory()->createRequest();
        $response = $client->getMessageFactory()->createResponse();

	    //$client->send($request, $response);
        //echo $response->getContent();
        
        $url_top = 'https://www.afi-b.com/';
        $url_land = 'https://client.afi-b.com/client/b/cl/main';
        $url_next = 'https://client.afi-b.com/client/b/cl/report/?r=monthly';


	    $data = array(
	    'login_name' => 'broadwimax',
	    'password' => 'c1xVXpjw',
        'login_division' => 'client',
	    );

	    $request->setMethod('POST');
        $request->addHeader('X-CSRF-TOKEN', csrf_token());
        $cookie = $request->addCookie('cookie','test','/home','https://www.afi-b.com/');
        $request->addHeader('Cookies',$cookie);
	    $request->setUrl($url_land);
	    $request->setRequestData($data);
	    //$request->setCaptureDimensions($width, $height, $top, $left);
        //$request->("https://client.afi-b.com/client/b/cl/report/?r=monthly");
        $crawler = $client->send($request, $response);
        
        var_dump($crawler);

        /*
         $search_data = array(
            'adv_id' => '5275',
            'input_partner_site' => '', 
            'start_year' => '2019',
            'start_month' => '03',
            'end_year' => '2019',
            'end_month' => '03',
            'span_monthly' => 'span',
            's_report_type' => 'nomal_sm',
            'device[]' => '_pc',
            'device[]' => '_sp',
            'device[]' => '_tab',
            'graph_display' => 1,
            'graph_type_set' => 1,
        );

        $request->setMethod('POST');
        $request->addHeader('X-CSRF-TOKEN', csrf_token());
        $request->addHeader('cookie', header('cookie'));
        $request->setUrl($url_next);
        $request->setRequestData($search_data);

        $crawler = new Crawler();
        $crawler->addHTMLContent($response->getContent(), "UTF-8"); 

        $html = $crawler->filter('div')->html();
        */
        //var_dump($crawler->html());


        /*Dailydata::create(
            [
            'imp' => ,
            'click' => ,
            'cv' => ,
            'cvr' => ,
            'ctr' => ,
            'active' => ,
            'partnership' => ,
            'asp_id' => ,
            'product_id' => ,
            'product_id' => ,
            ]
        );*/

        //$request->setMethod('GET');
        //$request->setUrl($purl);

        //$crawler = $client->send($request, $response);

        /*$cookies = Goutte::getCookieJar()->all();
        file_put_contents("./cookie/cookiefile", serialize($cookies));

        $cookies = unserialize(file_get_contents("./cookie/cookiefile"));
        if (! empty($cookies)) Goutte::getCookieJar()->updateFromSetCookie($cookies);
        $client = Goutte::request('GET','https://client.afi-b.com/client/b/cl/main');*/
        //if ( $response -> getStatus () === 200 ) {
        //var_dump($crawler);
        //}
        //$crawler = Goutte::request('GET',$crawler);

        //var_dump($crawler);


        //$crawler = str_replace( 'src="/' , 'src="https://www.afi-b.com/' , $crawler -> html() );
        //$crawler = str_replace( 'href="/' , 'href="https://www.afi-b.com/' , $crawler );
        
        //$crawler = $crawler->filter('form')->html();

        //ログインIDとパスワードを入力します
/*
        $afb_loginform['login_name'] = 'broadwimax';
        $afb_loginform['password'] = 'c1xVXpjw';

        $afb_crawler = Goutte::submit($afb_loginform);
*/
        //var_dump($crawler);


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
        }
        */
    }
   public function at() {

        $client = Client::getInstance();
        $client->getEngine()->setPath('../bin/phantomjs');
        $client->getEngine()->addOption('--load-images=true');
        $client->getEngine()->addOption('--ignore-ssl-errors=true');
        $client->getEngine()->addOption('--cookies-file=' . "./cookie/cookiefile.txt");


        $request = $client->getMessageFactory()->createRequest();
        $response = $client->getMessageFactory()->createResponse();

        //$client->send($request, $response);
        //echo $response->getContent();
        
        //$url = 'https://www.afi-b.com/';
        $purl = 'https://merchant.accesstrade.net/mapi/alternate/login/pass';


        $data = array(
        'userName' => 'ac-si',
        'password' => '50dosuvu3v9',
        );

        $request->setMethod('POST');
        $request->addHeader('X-CSRF-TOKEN', csrf_token());
        //$request->addCookie('cookie','test','/home','https://www.afi-b.com/');

        //$request->addHeader('cookie', header('cookie'));
        $request->setUrl($purl);
        $request->setRequestData($data);
        //$request->setCaptureDimensions($width, $height, $top, $left);

        $crawler = $client->send($request, $response);

        var_dump($crawler);

/*        $afb_client = Goutte::request('GET','https://www.accesstrade.ne.jp/');

        //$afb_loginform = $afb_client->filterXpath('//*[@id="pageTitle"]/aside[2]/g-header-loginform/div[1]/form')->form();

        $afb_loginform = $afb_client->filter('#login_m > form')->form();

        //ログインIDとパスワードを入力します
        $afb_loginform['userName'] = 'ac-si';
        $afb_loginform['password'] = '50dosuvu3v9';

        //ログインフォームにログイン
        $afb_client = Goutte::submit($afb_loginform);
        //ログイン後のタイトル表示
        echo $afb_client->html();*/
        //echo $afb_crawler->filter('title')->text();

    }

    public function vc() {


        $client = Client::getInstance();
        

        $client->getEngine()->setPath('../bin/phantomjs');
        //$client->getEngine()->addOption('--config=//phantomjs-config.json');
        $client->getEngine()->addOption('--ignore-ssl-errors=true');

        $client->getEngine()->addOption('--cookies-file=' . "./cookie/cookiefile.txt");

        $request = $client->getMessageFactory()->createRequest();
        $response = $client->getMessageFactory()->createResponse();

        
        //$client->send($request, $response);
        //echo $response->getContent();
        
        $url = 'https://mer.valuecommerce.ne.jp/login/';
        //$purl = 'https://mer.valuecommerce.ne.jp/home';

        $data = array(
        'login_form[email_address]' => 'imai@surprizz.co.jp',
        'login_form[password]' => 'Wimax12345',
        'login_form[_token]' => '3441271acbf504ba6ec0ab3c37c2aaf2af9fefc0',
        'login_form[requestUri]' => 'https://mer.valuecommerce.ne.jp/home/',
        );

        $request->setMethod('POST');
        $request->addHeader('X-CSRF-TOKEN', csrf_token());
        $request->addHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36');
        //$request->addHeader("cookie['redirectURL']", '/home');
        //var_dump($request->getHeaders()) ;

        $request->addHeader('cookie', $request->getHeaders());
        //$request->addCookie('path','/home');

        $request->setUrl($url);
        $request->setRequestData($data);
        //$request->setCaptureDimensions($width, $height, $top, $left);

        $crawler = $client->send($request, $response);

        var_dump($crawler);

        //$crawler = new Goutte\Client();


/*        $vc_client = $crawler->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19');
*/
        //$vc_client = new Goutte();
        /*$guzzleClient = new GuzzleHttp\Client(array(
            'timeout' => 60,
            'location' => '/home',
        ));

        $vc_client->setClient($guzzleClient);
*/

/*        $vc_client = New Goutte();
        
        $vc_crawler = $vc_client->request('GET','https://mer.valuecommerce.ne.jp/?type=4');

        //$afb_loginform = $afb_client->filterXpath('//*[@id="pageTitle"]/aside[2]/g-header-loginform/div[1]/form')->form();

        $vc_loginform = $vc_crawler->filter('#login_mer')->form();

        //ログインIDとパスワードを入力します
        $vc_loginform['login_form[email_address]'] = 'imai@surprizz.co.jp';
        $vc_loginform['login_form[password]'] = 'Wimax12345';

        //ログインフォームにログイン
        $vc_crawler = $vc_client->submit($vc_loginform);

        $cookie = $vc_client->getCookieJar()->all();
        
        file_put_contents("./cookie/cookiefile", serialize($cookie));
        
        //$vc_instance = New Goutte\Client();

        $cookie = unserialize(file_get_contents("./cookie/cookiefile"));

        if (!empty($cookie)){

            $vc_crawler = $vc_client->getCookieJar()->updateFromSetCookie($cookie);
        
        }
        
        $vc_crawler->request('GET','https://mer.valuecommerce.ne.jp/home');

        //ログイン後のタイトル表示
        var_dump($vc_client);
*/

          //cookie取り出し
/*          $cookie = unserialize(file_get_contents("./cookie/cookiefile"));
          if (!empty($cookie)){
            $client->getCookieJar()->updateFromSetCookie($cookie);
          }

          //アクセス
          $crawler = $client->request('GET', 'http://hogege.com/');

          //cookie保存
          $cookie = $client->getCookieJar()->all();
          file_put_contents("./cookie/cookiefile", serialize($cookie));
*/


    }
}
