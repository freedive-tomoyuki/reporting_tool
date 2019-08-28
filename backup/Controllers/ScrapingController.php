<?php
namespace App\Http\Controllers;
 
 use Illuminate\Http\Request;
 use App\Http\Controllers\Controller;
 
 // Goutte ライブラリの読み込み
 use Goutte\Client;
 
 class ScrapingController extends Controller
 {
 
     /**
      * Display a listing of the resource.
      *
      * @return \Illuminate\Http\Response
      */
     public function index()
     {
         // Google 検索 URL フォーマット
         $url_format = 'https://www.google.co.jp/search?q=%query%&num=%num%';
 
         // キーワード
         $keyword = 'カレンダー 人気';
 
         // キーワードのURLエンコード、SERPs の取得件数をセット
         $replace = [urlencode($keyword), 100];
         $search = ['%query%', '%num%'];
         
         // 実際にアクセスする URL
         $url = str_replace($search, $replace, $url_format);
 
         // Goutte ライブラリの事前準備
         $client = new Client();
 
         // Https 関連でエラーが発生する場合があるので、チェックしないように設定
         $guzzleClient = new \GuzzleHttp\Client(['verify' => false]);
        $client->setClient($guzzleClient);

        $result = [];

        // 検索結果の取得
        $crawler = $client->request('GET', $url);
        //var_dump($crawler->html());
        $crawler->filter('div.g')->each(function($node) use(&$result) {
            if (count($node->filter('a')) !== 0 && count($node->filter('h3')) !== 0) {
                $href = $node->filter('a')->attr('href');
                if (preg_match('/url\?/', $href)) {
                    $info = [];
                    $info['title'] = $node->filter('h3')->text();

                    preg_match('(https?://[-_.!~*\'()a-zA-Z0-9;/?:@=+$,%#]+)', $href, $match);
                    $info['url'] = urldecode($match[0]);
                    $result[] = $info;
                    
                }
            }
        });
    dd($result);
    }
    

}