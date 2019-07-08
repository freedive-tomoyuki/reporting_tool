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
use App\DailyDiff;
use App\DailySiteDiff;
//header('Content-Type: text/html; charset=utf-8');

class DailyCrawlerController extends Controller
{

    public function index()
    {


        $product_bases = ProductBase::all();
        echo date('Y-m-d', strtotime('-1 day'));
        return view('cralwerdaily',compact('product_bases'));
    }
    public function show_test()
    {
      $datas = \App\Product::all()->where('id','6');
      //var_dump($datas->asp) ;
      foreach($datas as $data)
      {

          //データにnullの値があるためif文入れる（本来はいらない）
          //if(!empty($data->asp->cv)) 
              //部署名取得
              echo $data->asp->login_url;
              //echo $data->asp->imp." ".$data->asp->click."<br>";
      }
    }
    public function save_daily($data){
        $data_array = json_decode(json_encode(json_decode($data)), True );
/*
        echo gettype($data_array);
        var_dump($data_array);
        
        var_dump($data_array[0]['cv']);
        var_dump($data_array[0]['click']);
        var_dump($data_array[0]['imp']);
*/
        $cv = intval($data_array[0]['cv']); 
        $click = intval($data_array[0]['click']);
        $imp = intval($data_array[0]['imp']);

        $crv = ($cv != 0)? ( $cv / $click ) * 100 : 0 ;
        $ctv = ($click != 0)? ( $click / $imp ) * 100 : 0 ;
        $ratio = (date("d")/date("t"));
        $estimate_cv = ceil(($cv)/ $ratio);

        Dailydata::create(
            [
            'imp' => $imp,
            'click' => $click,
            'cv' => $cv,
            'estimate_cv' => $estimate_cv,
            'cvr' => round($crv,2),
            'ctr' => round($ctv,2),
            'active' => $data_array[0]['active'],
            'partnership' => $data_array[0]['partnership'],
            'asp_id' => $data_array[0]['asp'],
            'product_id' => $data_array[0]['product'],
            'price' => $data_array[0]['price'],
            'cost' => $data_array[0]['cost'],
            'cpa' => $data_array[0]['cpa'],
            'date' => $data_array[0]['date']
            ]
        );
        Monthlydata::create(
            [
            'imp' => $imp,
            'click' => $click,
            'cv' => $cv,
            'cvr' => round($crv,2),
            'ctr' => round($ctv,2),
            'active' => $data_array[0]['active'],
            'partnership' => $data_array[0]['partnership'],
            'asp_id' => $data_array[0]['asp'],
            'product_id' => $data_array[0]['product'],
            'price' => $data_array[0]['price'],
            'cost' => $data_array[0]['cost'],
            'cpa' => $data_array[0]['cpa'],
            'date' => $data_array[0]['date']
            ]
        );


    }
    public function save_site($data){
        $month = date('m');
        $date = date('d');
        
        $data_array = json_decode(json_encode(json_decode($data)), True );

        //echo gettype($data_array);

        //var_dump($data_array);

        //for($i=0 ; $i <= count($data_array[0]) ; $i++){
        foreach($data_array as $data ){


            $cv = (intval($data['cv'])) ? intval($data['cv']) : 0 ; 
            $click = (intval($data['click'])) ? intval($data['click']) : 0 ;
            $imp = (intval($data['imp'])) ? intval($data['imp']) : 0 ;

            $cvr = ($cv == 0 || $click ==0 )? 0 : ( $cv / $click ) * 100 ;
            $ctr = ($click == 0|| $imp ==0 )? 0 : ( $click / $imp ) * 100 ;
            $ratio = (date("d")/date("t"));
            $estimate_cv = ceil(($cv)/ $ratio);

            Dailysite::create(
                [
                  'media_id' => $data['media_id'],
                  'site_name' => $data['site_name'],
                  'imp' => $imp,
                  'click' => $click,
                  'cv' => $cv,
                  'estimate_cv' => $estimate_cv,
                  'cvr' => round($cvr, 2),
                  'ctr' => round($ctr, 2),
                  'product_id' => $data['product'],
                  'price' => $data['price'],
                  'cost' => $data['cost'],
                  'cpa' => $data['cpa'],
                  'date' => $data['date']
                  
                ]
            );
            Monthlysite::create(
                [
                  'media_id' => $data['media_id'],
                  'site_name' => $data['site_name'],
                  'imp' => $imp,
                  'click' => $click,
                  'cv' => $cv,
                  'cvr' => round($cvr, 2),
                  'ctr' => round($ctr, 2),
                  'product_id' => $data['product'],
                  'price' => $data['price'],
                  'cost' => $data['cost'],
                  'cpa' => $data['cpa'],
                  'date' => $data['date']
                ]
            );
        }

    }
    public function cpa($cv ,$price ,$asp){
      $calData = array();
/*
  A8の場合の算出
*/
      if( $asp == 1 ){
        $asp_fee = ($price * 1.2 * 1.08) * 1.08 ;

      }
/*
  それ以外のASPの場合の算出
*/
      else{
        $asp_fee = ($price * 1.3 * 1.08) ;
      }
        $total = $asp_fee * 1.2 ;

        $calData['cpa'] = round(($total == 0 || $cv == 0 )? 0 : $total / $cv);
        $calData['cost'] = $total;
        
      return json_encode($calData);
    }
    public function BasetoProduct($asp_id, $product_base_id){
        $converter = Product::select();
        $converter->where('product_base_id', $product_base_id);
        $converter->where('asp_id', $asp_id );
        $converter = $converter->get()->toArray();
              //var_dump($a8_product[0]["id"]);
        return $converter[0]["id"];
    } 
    public function a8($product_base_id){//OK

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
              $product_id = $this->BasetoProduct(1, $product_base_id);

              $client = new Client(new Chrome($options));

              $client->browse(function (Browser $browser) use (&$crawler,$product_id) {

                $product_infos = \App\Product::all()->where('id',$product_id);
                
                foreach ($product_infos as $product_info){

                  $crawler_1 = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )
                                  ->click($product_info->asp->login_selector) 
                                  ->visit($product_info->asp->lp1_url.$product_info->asp_product_id) 
                                  ->crawler();

                  $crawler_2 = $browser->visit($product_info->asp->lp2_url)
                                  ->select('#reportOutAction > table > tbody > tr:nth-child(2) > td > select','23')
                                  ->radio('insId',$product_info->asp_product_id)
                                  ->click('#reportOutAction > input[type="image"]:nth-child(3)')
                                  ->crawler();

                                $selector_1 = array (
                                    'active' => $product_info->asp->daily_active_selector ,
                                    'partnership' => $product_info->asp->daily_partnership_selector,
                                    
                                );
                                $selector_2 = array (
                                    'imp' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(2)',
                                    'click' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(3)',
                                    'price' => '#ReportList > tbody > tr:nth-child(1) > td:nth-child(12)',
                                    'cv' => $product_info->asp->daily_cv_selector,
                                );

                                $a8data_1 = $crawler_1->each(function (Crawler $node)use ( $selector_1 ,$product_info){
                                  $data = array();
                                  $data['asp'] = $product_info->asp_id;
                                  $data['product'] = $product_info->id;

                                  foreach($selector_1 as $key => $value){
                                      $data[$key] = trim($node->filter($value)->text());
                                  }
                                  
                                  return $data;

                                });
                                
                                $a8data_2 = $crawler_2->each(function (Crawler $node)use ( $selector_2 ){

                                  foreach($selector_2 as $key => $value){
                                      $data[$key] = trim($node->filter($value)->text());
                                  }
                                  return $data;

                                });

                                  $a8data_1[0]['cv'] = trim(preg_replace('/[^0-9]/', '', $a8data_2[0]["cv"]));
                                  $a8data_1[0]['click'] = trim(preg_replace('/[^0-9]/', '', $a8data_2[0]["click"]));
                                  $a8data_1[0]['imp'] = trim(preg_replace('/[^0-9]/', '', $a8data_2[0]["imp"]));
                                  $a8data_1[0]['price'] = trim(preg_replace('/[^0-9]/', '', $a8data_2[0]["price"]));

                                  $calData = json_decode(
                                                  json_encode(
                                                    json_decode($this->cpa($a8data_1[0]['cv'] ,$a8data_1[0]['price'] , 1))
                                                  ), True
                                            );

                                  $a8data_1[0]['cpa']= $calData['cpa']; //CPA
                                  $a8data_1[0]['cost']= $calData['cost']; //獲得単価
                                  $a8data_1[0]['date'] = date('Y-m-d', strtotime('-1 day'));

                                  $crawler_for_site = $browser->visit($product_info->asp->lp2_url)
                                                  ->select('#reportOutAction > table > tbody > tr:nth-child(2) > td > select','11')
                                                  ->radio('insId',$product_info->asp_product_id)
                                                  ->click('#reportOutAction > input[type="image"]:nth-child(3)')
                                                  ->crawler();

                                  $count_selector = '#contents1clm > form:nth-child(6) > span.pagebanner';
                                  $count_data = intval(
                                                    trim(
                                                      preg_replace(
                                                        '/[^0-9]/', '', substr($crawler_for_site->filter($count_selector)->text(), 0, 7)
                                                      )
                                                    )
                                                );

                                  echo 'count_data＞'.$count_data;
                                  //$count_first = ($count_data > 500)? 500 : $count_data ;
                                  $page_count = ceil($count_data/500) ;
                                  echo 'page_count'.$page_count;

                              for($page=0 ; $page<$page_count ;$page++){
                                
                                $target_page = $page+1;
                                
                                $url = 'https://adv.a8.net/a8v2/ecAsRankingReportAction.do?reportType=11&insId='.$product_info->asp_product_id.'&asmstId=&termType=1&d-2037996-p='.$target_page.'&multiSelectFlg=0';

                                  echo $url;

                                  $crawler_for_site = $browser-> visit($url) -> crawler();

                                  $count_deff = intval($count_data)-(500*$page);

                                  $count_deff = (intval($count_deff) > 500)? 500 : intval($count_deff);

                                  echo "サイト数＞".$count_data;
                                  echo $page."ページのサイト数＞".$count_deff;

                                  for( $i = 1 ; $i <= $count_deff ; $i++){
                                    
                                    $count = $i+(500*$page);

                                    $selector_for_site = array(
                                        'media_id'=> '#ReportList > tbody > tr:nth-child('.$i.') > td:nth-child(2) > a',
                                        'site_name'=> '#ReportList > tbody > tr:nth-child('.$i.') > td:nth-child(4)',
                                        'imp'=> '#ReportList > tbody > tr:nth-child('.$i.') > td:nth-child(5)',
                                        'click'=> '#ReportList > tbody > tr:nth-child('.$i.') > td:nth-child(6)',
                                        'cv'=> '#ReportList > tbody > tr:nth-child('.$i.') > td:nth-child(10)',
                                        'price'=> '#ReportList > tbody > tr:nth-child('.$i.') > td:nth-child(13)',
                                    );

                                    foreach($selector_for_site as $key => $value){
                                            //$data[$count][$key] = trim($crawler_for_site->filter($value)->text());
                                            $data[$count][$key] = trim($crawler_for_site->filter($value)->text());
                                    }
                                    //$data[1]['cpa']= $this->cpa($data[1]['cv'] ,$data[1]['price'] , 1); 

                                    $calData = json_decode(
                                                    json_encode(
                                                      json_decode($this->cpa($data[$count]['cv'] ,$data[$count]['price'] , 1))
                                                    ), True
                                              );
                                  
                                    //$data[$count]['product'] = $product_info->id;
                                    $data[$count]['product'] = $product_info->id;
                                    $data[$count]['date'] = date('Y-m-d', strtotime('-1 day'));

                                    $data[$count]['cpa']= $calData['cpa']; //CPA
                                    $data[$count]['cost']= $calData['cost']; //獲得単価

                                    echo '<pre>';
                                    echo $i;
                                    var_dump($data);
                                    echo '</pre>';
                                    
                                    /**
                                      １サイトずつサイト情報の登録を実行
                                    */
                                    //$this->save_site(json_encode($data));

                                  };
                                  

                                  //$page = $page + 1;
                              }
                                var_dump($data);
                                var_dump($a8data_1);
                                /**
                                  １サイトずつサイト情報の登録を実行
                                */
                                $this->save_site(json_encode($data));
                                $this->save_daily(json_encode($a8data_1));
                                

                }
            });

    }
   public function accesstrade($product_base_id){//OK

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
            
            $product_id = $this->BasetoProduct(2, $product_base_id);

            $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {

              $product_infos = \App\Product::all()->where('id',$product_id);
                
                foreach ($product_infos as $product_info){
                  // /var_dump($product_info->asp);
                  $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
                                  ->visit($product_info->asp->lp1_url.$product_info->asp_product_id )
                                  ->crawler();


                                $selector = array (
                                    //'month' => '//*[@id="element"]/tbody/tr[1]/td[1]',
                                      'imp' => $product_info->asp->daily_imp_selector  ,
                                      'click' => $product_info->asp->daily_click_selector,
                                      'cv' => $product_info->asp->daily_cv_selector,
                                      'partnership' => $product_info->asp->daily_partnership_selector,
                                      'active' => $product_info->asp->daily_active_selector ,
                                      'price' => $product_info->asp->daily_price_selector ,

                                );
                              
                                var_dump($crawler);
                                //$crawler->each(function (Crawler $node) use ( $selector ){
                                
                                $atdata = $crawler->each(function (Crawler $node)use ( $selector ,$product_info){
                                
                                      $data = array();
                                      $data['asp'] = $product_info->asp_id;
                                      $data['product'] = $product_info->id;
                                     
                                      $data['date'] = date('Y-m-d', strtotime('-1 day'));

                                      foreach($selector as $key => $value){
                                        
                                          if($key == 'active'){
                                            $active = explode("/", $node->filter($value)->text());
                                            $data[$key] = trim($active[0]);

                                          }elseif($key == 'partnership'){

                                            $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                          }else{

                                            $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                          }
                                          echo $data[$key];

                                      }
                                      //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 2);

                                      $calData = json_decode(
                                                    json_encode(
                                                      json_decode($this->cpa($data['cv'] ,$data['price'] , 2))
                                                    ), True
                                              );

                                      $data['cpa']= $calData['cpa']; //CPA
                                      $data['cost']= $calData['cost']; //獲得単価

                                      //var_dump($data);
                                  return $data;

                                });

                                $array_site = array();
                                $crawler_for_site = $browser->visit("https://merchant.accesstrade.net/mapi/program/".$product_info->asp_product_id."/report/partner/monthly/occurred?targetFrom=".date('Y-m-01')."&targetTo=".date('Y-m-d', strtotime('-1 day'))."&pointbackSiteFlagList=0,1")->crawler();


                                $array_site=$crawler_for_site->text();

                                $array_site = json_decode($array_site,true);
                                
                                $array_sites = $array_site["report"];

                                $x = 0; 

                                foreach($array_sites as $site){
                                  $data[$x]['product'] = $product_info->id ;
                                  $data[$x]['media_id'] = $site["partnerSiteId"] ;
                                  $data[$x]['site_name'] = $site["partnerSiteName"] ;
                                  $data[$x]['imp'] = $site["impressionCount"] ;
                                  $data[$x]['click'] = $site["clickCount"] ;
                                  $data[$x]['cv'] = $site["actionCount"] ;
                                  $data[$x]['price'] = $site["occurredTotalReward"] ;

                                  //$data[$x]['cpa']= $this->cpa($site['occurredTotalReward'] ,$site["actionCount"] , 1)
                                  $calData = json_decode(
                                                    json_encode(
                                                      json_decode(
                                                        $this->cpa( $site["actionCount"] ,$site['occurredTotalReward'], 1)
                                                      )
                                                    ), True
                                              );
                                  $data[$x]['cpa']= $calData['cpa']; //CPA
                                  $data[$x]['cost']= $calData['cost']; //獲得単価
                                  
                                  $data[$x]['date'] = date('Y-m-d', strtotime('-1 day'));
                                  
                                  $x++;
                                
                                }
                                var_dump($data);
                                   
                            $this->save_site(json_encode($data));
                            $this->save_daily(json_encode($atdata));
                }
                                
            });

    }
   public function valuecommerce($product_base_id){

            Browser::macro('crawler', function () {
                return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
            });

            $options = [
                '--window-size=1920,1080',
                '--start-maximized',
                '--headless',
                '--lang=ja_JP',
                '--disable-gpu',
            ];
            $product_id = $this->BasetoProduct(3, $product_base_id);

            $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {
            
              //$product_infos = \App\Product::all()->where('id',$product_id);
              $product_infos = \App\Product::all()->where('id',$product_id);

              foreach ($product_infos as $product_info){

              $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
                                  ->visit($product_info->asp->lp1_url )
                                  ->crawler();
                      
                              $crawler2 = $browser->visit($product_info->asp->lp2_url)
                                  ->type("condition[fromDate]" , date('Y/m/d', strtotime('-1 day')))
                                  ->type("condition[toDate]" , date('Y/m/d', strtotime('-1 day')) )
                                  ->click("#show_statistics")
                                  ->crawler();
                      
                              $selector_crawler = array (
                                    'imp' => $product_info->asp->daily_imp_selector  ,
                                    'click' => $product_info->asp->daily_click_selector,
                                    'cv' => $product_info->asp->daily_cv_selector,
                                    'partnership' => $product_info->asp->daily_partnership_selector,
                                    'price' => $product_info->asp->daily_price_selector,
                              );

                              $selector_crawler2 = array (
                                    'active' => "#report > tbody > tr > td:nth-child(4)"
                                    //$product_info->asp->daily_active_selector ,
                              );



                              $vcdata = $crawler->each(function (Crawler $node) use ( $selector_crawler ,$product_info){
                                $data = array();
                                
                                      $data['asp'] = $product_info->asp_id;
                                      $data['product'] = $product_info->id;
                                    
                                      $data['date'] = date('Y-m-d', strtotime('-1 day'));

                                //echo $node->html();
                                foreach($selector_crawler as $key => $value){
                                      $data[$key] = array();
                                      $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                      //echo $node->filter($value)->text();
                                      //echo "\n";
                                }
                                //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 1); 

                                  $calData = json_decode(
                                                    json_encode(
                                                      json_decode($this->cpa($data['cv'] ,$data['price'] , 3))
                                                    ), True
                                              );
                                  $data['cpa']= $calData['cpa']; //CPA
                                  $data['cost']= $calData['cost']; //獲得単価


                               return $data;
                              });
                              $active_data = $crawler2->each(function (Crawler $node) use ( $selector_crawler2 ){
                                foreach($selector_crawler2 as $key => $value){
                                      $active = trim(preg_replace('/[^0-9]/', '',$node->filter($value)->text()));
                                      //echo $active[1];
                                }
                                return $active;
                              });
                              //アクティブ数　格納
                              $vcdata[0]['active'] = trim(preg_replace('/[^0-9]/', '', $active_data[0]));

                              //１ページ目クロール
                              $pagination_page = $product_info->asp->lp2_url;
                              $crawler_for_site = $browser->visit($pagination_page)->crawler();
                              
                        
                              $crawler_for_site = $browser -> visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/')-> crawler();

                              $count_selector = "#cusomize_wrap > span";

                              $active = explode("/", $crawler_for_site->filter($count_selector)->text());

                              $count_page = ($active[1]>40)? ceil($active[1]/40) : 1 ;

                              echo "active件数→".$active[1]."←active件数";
                              
                              for($page = 0 ; $page < $count_page ; $page++){
                                    
                                    $target_page = $page+1;
                                    
                                    $crawler_for_site = 
                                            $browser  //-> visit($product_info->asp->lp2_url)
                                                      //-> click('#all > ul:nth-child(2) > li.next')
                                                      -> visit('https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D='.date("Y").'&condition%5BfromMonth%5D='.date("n").'&condition%5BtoYear%5D='.date("Y").'&condition%5BtoMonth%5D='.date("n").'&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page='.$target_page)
                                                      -> crawler();
                                                      echo 'https://mer.valuecommerce.ne.jp/affiliate_analysis/?condition%5BfromYear%5D='.date("Y").'&condition%5BfromMonth%5D='.date("n").'&condition%5BtoYear%5D='.date("Y").'&condition%5BtoMonth%5D='.date("n").'&condition%5BactiveFlag%5D=Y&allPage=1&notOmksPage=1&omksPage=1&pageType=all&page='.$target_page;



                                  //最終ページのみ件数でカウント
                                  $crawler_count = ( $target_page == $count_page )? $active[1]-($page * 40) : 40 ;
                                  
                                  echo $target_page."ページ目のcrawler_count＞＞".$crawler_count."</br>" ;

                                  for($i=1 ; $i <= $crawler_count ; $i++){
                                    
                                    $count = ($page * 40) + $i;
                                    
                                    echo "count→".$count."←count";

                                    $data[$count]['product'] = $product_info->id;

                                    if($crawler_for_site->filter('#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(2)')->count() != 0){
                                      //echo $target_page."ページの i＞＞".$i."番目</br>" ;

                                        $selector_for_site = array(
                                              'media_id'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(2)',
                                              'site_name'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(3) > a',
                                              'imp'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(7)',
                                              'click'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(8)',
                                              'cv'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(19)',
                                              'price'=>'#all > div.tablerline > table > tbody > tr:nth-child('.$i.') > td:nth-child(21)',

                                        );
                                    
                                        foreach($selector_for_site as $key => $value){
                                          
                                          if( $key == 'site_name' ){
                                          
                                            $data[$count][$key] = trim($crawler_for_site->filter($value)->text());
                                          
                                          }else{
                                          
                                            $data[$count][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                          
                                          }
                                        }
                                        //$data[$count]['cpa']= $this->cpa($data[$count]['cv'] ,$data[$count]['price'] , 1);

                                        $calData = json_decode(
                                                    json_encode(
                                                      json_decode($this->cpa($data[$count]['cv'] ,$data[$count]['price'] , 3))
                                                    ), True
                                              );
                                        $data[$count]['cpa']= $calData['cpa']; //CPA
                                        $data[$count]['cost']= $calData['cost']; //獲得単価
                                        
                                        $data[$count]['date'] = date('Y-m-d', strtotime('-1 day'));

                                      }
                                  }

                              }
                              var_dump($data);
                              $this->save_daily(json_encode($vcdata));
                              $this->save_site(json_encode($data));

              }
            });
    }
   public function afb($product_base_id){//OK

            Browser::macro('crawler', function () {
                return new Crawler($this->driver->getPageSource() ?? '', $this->driver->getCurrentURL() ?? '');
            });

            $options = [
                '--window-size=1920,1200',
                '--start-maximized',
                '--headless',
                //'--disable-gpu',
                //'--no-sandbox'
            ];

            $product_id = $this->BasetoProduct(4, $product_base_id);

            $client = new Client(new Chrome($options));

            $client->browse(function (Browser $browser) use (&$crawler,$product_id) {
              
              $product_infos = \App\Product::all()->where('id', $product_id);
                
              foreach ($product_infos as $product_info){

                  $crawler = $browser->visit($product_info->asp->login_url)
                                  
                                  ->type($product_info->login_key , $product_info->login_value)
                                  ->type($product_info->password_key , $product_info->password_value )
                                  ->click($product_info->asp->login_selector)
                                  
                                  //->visit($product_info->asp->lp1_url )
                                  ->visit("https://client.afi-b.com/client/b/cl/report/?r=daily")
                                  //->radio('span_monthly','0mon')

                                  ->type('#form_start_date',date('Y/m/01'))//
                                  ->type('#form_end_date',date('Y/m/d', strtotime('-1 day')))//前日分のデータ取得
                                  ->click('#adv_id_daily_chzn > a')
                                  ->click('#adv_id_daily_chzn_o_1')
                                  ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(1)')
                                  ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(2)')
                                  ->click('#report_form_2 > div > table > tbody > tr:nth-child(5) > td > p > label:nth-child(3)')
                                  ->click('#report_form_2 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')

                                  ->crawler();

                                  $crawler2 = $browser
                                  ->visit('https://client.afi-b.com/client/b/cl/main')
                                  ->crawler();
                              
                                $crawler3 = $browser
                                  ->visit($product_info->asp->lp3_url)
                                  //->radio('span','tm')
                                  ->click('#site_tab_bth')
                                  ->driver->executeScript('window.scrollTo(0, 400);')
                                  ->click('#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(1)')
                                  ->click('#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(2)')
                                  ->click('#report_form_4 > div > table > tbody > tr:nth-child(6) > td > p > label:nth-child(3)')
                                  ->click('#report_form_4 > div > div.btn_area.mt20 > ul.btn_list_01 > li > input')
                                  ->crawler();

                                
                                  
                              $selector_crawler = array (
                                    'imp' => '#reportTable > tfoot > tr > td:nth-child(3) > p',
                                    //$product_info->asp->daily_imp_selector  ,
                                    'click' => '#reportTable > tfoot > tr > td:nth-child(4) > p',
                                    //$product_info->asp->daily_click_selector,
                                    'cv' => '#reportTable > tfoot > tr > td:nth-child(7) > p',
                                    'price' => '#reportTable > tfoot > tr > td:nth-child(10) > p',
                                    //$product_info->asp->daily_cv_selector,
                                    #reportTable > tfoot > tr > td:nth-child(3) > p
                                    #reportTable > tfoot > tr > td:nth-child(4) > p
                              );
                              $selector_crawler2 = array (
                                    'partnership' => '#main > div.wrap > div.section33 > div.section_inner.positionr.positionr > table > tbody > tr:nth-child(13) > td:nth-child(2)'
                              );
                              
                              $selector_crawler3 = array (
                                  'active' => $product_info->asp->daily_active_selector ,
                              );

                              $afbdata = $crawler->each(function (Crawler $node) use ( $selector_crawler ,$product_info){
                                
                                $data = array();
                                $data['asp'] = $product_info->asp_id;
                                $data['product'] = $product_info->id;
                                
                                $data['date'] = date('Y-m-d', strtotime('-1 day'));

                                foreach($selector_crawler as $key => $value){

                                  $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));

                                }
                                //$data['cpa']= $this->cpa($data['cv'] ,$data['price'] , 3);
                                $calData = json_decode(
                                              json_encode(
                                                json_decode($this->cpa($data['cv'] ,$data['price'] , 4))
                                              ), True
                                );
                                $data['cpa']= $calData['cpa']; //CPA
                                $data['cost']= $calData['cost']; //獲得単価

                                return $data;

                              });
                              $partnership = $crawler2->each(function (Crawler $node)use ( $selector_crawler2  ) {
                                //echo $node->html();
                                //echo $node->html();

                                foreach($selector_crawler2 as $key => $value){
                                      //$data[$key] = array();
                                      //$data[$key] = $node1->filter($value)->text();
                                  $partnership = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                  
                                  //echo preg_replace('/[^0-9]/', '', $partnership);
                                  //echo "\n";
                                }
                                return $partnership;
                                //var_dump($data);
                              });
                              $active = $crawler3->each(function (Crawler $node) use ( $selector_crawler3 ) {
                                  foreach($selector_crawler3 as $key => $value){
                                      //$data[$key] = array();
                                      //$data[$key] = $node1->filter($value)->text();
                                      $active = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                      //echo preg_replace('/[^0-9]/', '', $active);
                                      //echo "\n";
                                  }
                                  return $active;
                                //echo $node->html();
                              
                              });
                              //$afbsite = $crawler3->each(function (Crawler $node) use ( $active ) {
                                
                                

                                $count_data = $active[0];
                                $afbsite = array();
                                //echo $count_data;

                                for( $i = 1 ; $count_data >= $i ; $i++ ){
                                    $afbsite[$i]['product'] = $product_info->id;

                                    $selector_for_site = array(
                                      'media_id'=>'#reportTable > tbody > tr:nth-child('.$i.') > td.maxw150',
                                      'site_name'=>'#reportTable > tbody > tr:nth-child('.$i.') > td.maxw150 > p > a',
                                      'imp'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(5) > p',
                                      'click'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(6) > p',
                                      'cv'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(9) > p',
                                      'ctr'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(7) > p',
                                      'cvr'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(10) > p',
                                      'price'=>'#reportTable > tbody > tr:nth-child('.$i.') > td:nth-child(12) > p',
                                    );

                                  foreach($selector_for_site as $key => $value){
                                      
                                      if( $key == 'media_id' ){
                                        //$data = trim($node->filter($value)->attr('title'));
                                        $media_id = array();
                                        $sid = trim($crawler3->filter($value)->attr('title'));
                                        preg_match('/SID：(\d+)/', $sid , $media_id);

                                        $afbsite[$i][$key] = $media_id[1];

                                      }elseif( $key == 'site_name' ){
                                      
                                        $afbsite[$i][$key] = trim($crawler3->filter($value)->text());
                                      
                                      }else{
                                      
                                        $afbsite[$i][$key] = trim(preg_replace('/[^0-9]/', '', $crawler3->filter($value)->text()));
                                      
                                      }
                                     
                                  }
                                  //$afbsite[$i]['cpa']= $this->cpa($afbsite[$i]['cv'] ,$afbsite[$i]['price'] , 4);
                                  $calData = json_decode(
                                              json_encode(
                                                json_decode($this->cpa($afbsite[$i]['cv'] ,$afbsite[$i]['price'] , 4))
                                              ), True
                                  );
                                  $afbsite[$i]['cpa']= $calData['cpa']; //CPA
                                  $afbsite[$i]['cost']= $calData['cost']; //獲得単価
                                 
                                  $afbsite[$i]['date'] = date('Y-m-d', strtotime('-1 day'));
                                }
                                //var_dump($data);

                                //return $data;
                                
                              //});



                              $afbdata[0]['active'] = $active[0];
                              $afbdata[0]['partnership'] = $partnership[0];
                              
                              //var_dump($afbdata);

                              $this->save_daily(json_encode($afbdata));
                              $this->save_site(json_encode($afbsite));

                          }
                    });
    }

    public function rentracks($product_base_id){//OK

    /*
     昨日の日付　取得
    */
            $yesterday = date('d', strtotime('-1 day'));

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
    /*
      案件の大本IDからASP別のプロダクトIDを取得
    */
            $product_id = $this->BasetoProduct(5, $product_base_id);

    /*
      Chromeドライバーのインスタンス呼び出し
    */
            $client = new Client(new Chrome($options));

    /*
      Chromeドライバー実行
      　引数
      　　$product_id:案件ID
      　　$yesterday:昨日の日付
    */
            $client->browse(function (Browser $browser) use (&$crawler,$product_id,$yesterday) {

              $product_infos = \App\Product::all()->where('id',$product_id);
            

                foreach ($product_infos as $product_info){
                  // /var_dump($product_info->asp);
                /*
                  クロール：ログイン＝＞パートナー分析より検索
                */
                  $crawler = $browser->visit($product_info->asp->login_url)
                                  ->type($product_info->asp->login_key , $product_info->login_value)
                                  ->type($product_info->asp->password_key , $product_info->password_value )

                                  ->click($product_info->asp->login_selector)
                                  ->visit($product_info->asp->lp1_url)
                                  ->select('#idDropdownlist1',$product_info->asp_product_id)
                                  ->select('#idDoneDay', $yesterday )
                                  ->click('#idButton1')
                                  ->crawler();
                /*
                  クロール：
                */

                  $crawler2 = $browser->visit($product_info->asp->lp2_url)->crawler();

                /*
                  クロール：
                */

                  $crawler3 = $browser->visit($product_info->asp->lp3_url)->crawler();

                /*
                  selector 設定
                */
                                $selector1 = array (
                                      'imp' => $product_info->asp->daily_imp_selector,
                                      'click' => $product_info->asp->daily_click_selector,
                                      'cv' => $product_info->asp->daily_cv_selector,
                                      
                                );
                                $selector2 = array (
                                      'partnership' => $product_info->asp->daily_partnership_selector,
                                );
                                $selector3 = array (
                                      'active' => $product_info->asp->daily_active_selector ,
                                );

                                //var_dump($crawler);
                                //var_dump($crawler2);
                                //var_dump($crawler3);
                                //$crawler->each(function (Crawler $node) use ( $selector ){

                /*
                  $crawler　をフィルタリング
                */
                                $rtdata = $crawler->each(function (Crawler $node)use ( $selector1 ,$product_info){
                                
                                      $data = array();
                                      $data['asp'] = $product_info->asp_id;
                                      $data['product'] = $product_info->id;
                                      
                                      $data['date'] = date('Y-m-d', strtotime('-1 day'));

                                      foreach($selector1 as $key => $value){
                                        $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                      }

                                  return $data;

                                });
                /*
                  $crawler2　をフィルタリング
                */
                                $rtdata2 = $crawler2->each(function (Crawler $node)use ( $selector2 ,$product_info){
                                
                                      $data = array();

                                      foreach($selector2 as $key => $value){
                                        $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                      }

                                  return $data;

                                });
                /*
                  $crawler3　をフィルタリング
                */
                                $rtdata3 = $crawler3->each(function (Crawler $node)use ( $selector3 ,$product_info){
                                
                                      $data = array();

                                      foreach($selector3 as $key => $value){
                                        $data[$key] = trim(preg_replace('/[^0-9]/', '', $node->filter($value)->text()));
                                      }

                                  return $data;

                                });
                                //var_dump($rtdata3);
                /*
                  サイト抽出　
                */
                                $crawler_for_site = $browser
                                            ->visit("https://manage.rentracks.jp/sponsor/detail_partner")
                                            ->select('#idDropdownlist1',$product_info->asp_product_id)
                                            ->select('#idDoneDay', $yesterday)
                                            ->select('#idPageSize','300')
                                            ->click('#idButton1')
                                            ->crawler();

                                            var_dump($crawler_for_site->html());
                                            //アクティブ件数を取得
                                            $active_partner = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > div.hitbox > em')->text()));
                                            echo $active_partner;
                                        for( $i = 1 ; $active_partner >= $i ; $i++ ){
                                            $rtsite[$i]['product'] = $product_info->id;

                                            $iPlus = $i+1;
                                            echo 'iPlus'.$iPlus;
                                            
                                            $selector_for_site = array(
                                              'media_id'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c03',
                                              'site_name'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c04 > a',
                                              'imp'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c05',
                                              'click'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c06',
                                              'cv'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c10',
                                              'price'=>'#main > table > tbody > tr:nth-child('.$iPlus.') > td.c15',
                                            );

                                          foreach($selector_for_site as $key => $value){
                                              if( $key == 'site_name' ){
                                        
                                                $rtsite[$i][$key] = trim($crawler_for_site->filter($value)->text());
                                              
                                              }else{
                                              
                                                $rtsite[$i][$key] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter($value)->text()));
                                              }

                                          }
                                          $calData = json_decode(
                                                      json_encode(
                                                        json_decode($this->cpa($rtsite[$i]['cv'] ,$rtsite[$i]['price'] , 5))
                                                      ), True
                                                    );
                                          $rtsite[$i]['cpa']= $calData['cpa']; //CPA
                                          $rtsite[$i]['cost']= $calData['cost'];
                                          $rtsite[$i]['date'] = date('Y-m-d', strtotime('-1 day'));
                                        }


                            $rtdata[0]['price'] = trim(preg_replace('/[^0-9]/', '', $crawler_for_site->filter('#main > table > tbody > tr.total > td:nth-child(15)')->text()));

                            $rtdata[0]['partnership'] = $rtdata2[0]['partnership'];
                            $rtdata[0]['active'] = $rtdata3[0]['active'];

                            $calData = json_decode(
                                          json_encode(json_decode($this->cpa($rtdata[0]['cv'] ,$rtdata[0]['price'] , 5))), True
                                        );
                            $rtdata[0]['cpa']= $calData['cpa']; //CPA
                            $rtdata[0]['cost']= $calData['cost'];
                            
/*                            echo "<pre>";
                            var_dump($rtdata);
                            var_dump($rtsite);
                            echo "</pre>";
*/
                /*
                  サイトデータ・日次データ保存
                */
                            $this->save_site(json_encode($rtsite));
                            $this->save_daily(json_encode($rtdata));
                
                            //var_dump($crawler_for_site);
                }

            });

    }
    public function filterAsp( $product_id ){
      $target_asp = Product::select('asp_id','name')
                  ->join('asps','products.asp_id','=','asps.id')
                  ->where('product_base_id', $product_id )
                  ->where('products.killed_flag', 0 )
                  ->get();

      return json_encode($target_asp);
    }
    public function run(Request $request){

          $aspRow = array();
          $asp_array = array();

          $asp_name = $this->filterAsp($request->product);
          //var_dump($asp_name);
          $asp_array = (json_decode($asp_name,true));
          //echo gettype($asp_id);

          foreach ($asp_array as $name){
            array_push($aspRow,str_replace(' ', '' ,mb_strtolower($name["name"])));
          }
          var_dump($aspRow);

          foreach($aspRow as $function_name){
            $this->{$function_name}($request->product);
          }
          /**
            差分からデイリーの件数を取得
          */
          //$this->diff($request->product);
          //$this->diff_site($request->product);

          //$this->a8($request->product);
          //$this->rentracks($request->product);
          //$controller = new EstimateController;
          

          //$this->a8($request->product);
          //$this->rentracks($request->product);
          //$this->accesstrade($request->product);
          //$this->valuecommerce($request->product);
          //$this->afb($request->product);
          //echo "a";
          //return view('daily_result');
          //return redirect()->to('/daily_result', $status = 302, $headers = [], $secure = null);
    }
    /**
    *  前日分との差分からその日単位の増減数を計算
    *
    */
    public function diff($product_base_id = 4){
        $i =0;

        $Array = array();
        $Array_1 = array();
        $daily_diff = array();
        $daily_diff_1 = array();
        $diff_ = array();

        $date = date("Y-m-d",strtotime("-1 day")); 
        $date_1 = date("Y-m-d",strtotime("-2 day")); 

        $products = Product::select()
              ->where('product_base_id', $product_base_id)
              ->where('killed_flag', 0)
              ->get()
              ->toArray();

        foreach($products as $product){
          //昨日分
          $Array = Dailydata::where("product_id",$product['id'])->where("date",$date)->get()->toArray();
          if(!empty($Array)){
            array_push($daily_diff , $Array[0] );
          }
          //おととい分
          $Array_1 = Dailydata::where("product_id",$product['id'])->where("date",$date_1)->get()->toArray();
          if(!empty($Array_1)){
            array_push($daily_diff_1 , $Array_1[0] );
          }
          

        
        }
        echo "<pre>";
        var_dump($daily_diff);
        var_dump($daily_diff_1);
        echo "</pre>";

        /* 前日比でなくなっているASPを考慮 */
        if($daily_diff_1){
          foreach ( $daily_diff as $diff ){
              foreach ( $daily_diff_1 as $diff_1 ) {
                  if($diff["asp_id"] == $diff_1["asp_id"]){
                    //$asp_id = $diff["asp_id"];
                    $diff_[$i]["imp"] = $diff["imp"] - $diff_1["imp"];
                    $diff_[$i]["click"] = $diff["click"] - $diff_1["click"];
                    $diff_[$i]["cv"] = $diff["cv"] - $diff_1["cv"];
                    $diff_[$i]["ctr"] = 
                    ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                    $diff_[$i]["cvr"] = 
                    ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;

                    $diff_[$i]["active"] = $diff["active"];
                    $diff_[$i]["estimate_cv"] = $diff["estimate_cv"];
                    $diff_[$i]["partnership"] = $diff["partnership"];
                    $diff_[$i]["price"] = $diff["price"] - $diff_1["price"];
                    $diff_[$i]["cpa"] = $diff["cpa"];
                    $diff_[$i]["cost"] = $diff["cost"] - $diff_1["cost"];
                    $diff_[$i]["asp_id"] = $diff["asp_id"];
                    $diff_[$i]["date"] = $diff["date"];
                    $diff_[$i]["product_id"] = $diff["product_id"];
                    //$diff_[$i]["killed_flag"] = 0;
                    $i++;
                  }
              }
          }
        }else{
            $diff_= $daily_diff;
        }

        echo "<pre>最終データ";
        var_dump($diff_);
        echo "</pre>";
        //$daily_diff = new DailyDiff();
        foreach ($diff_ as $insert_diff) {
            DailyDiff::create(
                [
                'imp' => $insert_diff["imp"],
                'ctr' => $insert_diff["ctr"],
                'click' => $insert_diff["click"],
                'cv' => $insert_diff["cv"],
                'cvr' => $insert_diff["cvr"],
                'active' => $insert_diff["active"],
                'partnership' => $insert_diff["partnership"],
                'price' => $insert_diff["price"],
                'cpa' => $insert_diff["cpa"],
                'cost' => $insert_diff["cost"],
                'estimate_cv' => $insert_diff["estimate_cv"],
                'asp_id' => $insert_diff["asp_id"],
                'date' => $insert_diff["date"],
                'product_id' => $insert_diff["product_id"]
                ]
            );
        }

    }
    /**
    *  前日分との差分からその日のサイト単位の増減数を計算
    *
    */
    public function diff_site($product_base_id = 4){
        $i =0;

        $Array = array();
        $Array_1 = array();
        $daily_diff = array();
        $daily_diff_1 = array();
        $diff_ = array();
        $list = array();
        $date = date("Y-m-d",strtotime("-1 day")); 
        $date_1 = date("Y-m-d",strtotime("-2 day")); 

        $products = Product::select()
              ->where('product_base_id', $product_base_id)
              ->where('killed_flag', 0)
              ->get()
              ->toArray();

        foreach($products as $product){

          $Array[$product['id']] = Dailysite::where("product_id",$product['id'])->where("date",$date)->get()->toArray();
 /*         
          echo $i;
          echo "<pre>";
          var_dump($Array);
          echo "</pre>";*/
          //array_push($daily_diff[] , $Array );

          $Array_1[$product['id']] = Dailysite::where("product_id",$product['id'])->where("date",$date_1)->get()->toArray();
          //array_push($daily_diff_1 , $Array_1 );
          
          //$i++;
        
        }
        foreach ( $Array as $diff){
            foreach ( $diff as $site_a){
              array_push($daily_diff , $site_a );
            }
        }
        foreach ( $Array_1 as $diff){
            foreach ( $diff as $site_b){
              array_push($daily_diff_1 , $site_b );
            }
        }
        foreach ( $daily_diff_1 as $site){
              array_push($list , $site["media_id"]."_".$site["product_id"] );
        }
/*        echo "<pre>";
        echo "result1";
        var_dump($daily_diff);
        echo "result2";
        var_dump($daily_diff_1);
        echo "</pre>";
        echo "<pre>";
        var_dump($list);
        echo "</pre>";*/
        /* 前日比でなくなっているASPを考慮 */
        $i = 0;
        echo date("Y-m-t",strtotime("-1 month"));
        //月初一日以降
        if(date("Y-m-d",strtotime("-2 day")) != date("Y-m-t",strtotime("-1 month"))){
            foreach ( $daily_diff as $site){
                foreach ( $daily_diff_1 as $site_1){
              //foreach ( $Array_1 as $diff_1 ) {

                  if($site["media_id"] == $site_1["media_id"] && $site["product_id"] == $site_1["product_id"] ){
                  //$media_id = $diff["media_id"];
                  
                      $diff_[$i]["imp"] = $site["imp"] - $site_1["imp"];
                      $diff_[$i]["click"] = $site["click"] - $site_1["click"];
                      $diff_[$i]["cv"] = $site["cv"] - $site_1["cv"];
                      $diff_[$i]["ctr"] = 
                      ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                      $diff_[$i]["cvr"] = 
                      ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;
                      $diff_[$i]["estimate_cv"] = $site["estimate_cv"];
                      $diff_[$i]["price"] = $site["price"] - $site_1["price"];
                      $diff_[$i]["cpa"] = $site["cpa"];
                      $diff_[$i]["cost"] = $site["cost"] - $site_1["cost"];
                      $diff_[$i]["media_id"] = $site["media_id"];
                      $diff_[$i]["site_name"] = $site["site_name"];
                      $diff_[$i]["date"] = $site["date"];
                      $diff_[$i]["product_id"] = $site["product_id"];
                 
                    //} 
                    $i++;
                    break;
                  }

              }
          }
          foreach ( $daily_diff as $site){
                  if(!in_array($site["media_id"]."_".$site["product_id"], $list)){
                      $diff_[$i]["imp"] = $site["imp"];
                      $diff_[$i]["click"] = $site["click"];
                      $diff_[$i]["cv"] = $site["cv"];
                      $diff_[$i]["ctr"] = 
                      ($diff_[$i]["imp"] > 0 && $diff_[$i]["click"] > 0 ) ? intval($diff_[$i]["imp"])/intval($diff_[$i]["click"]): 0 ;
                      $diff_[$i]["cvr"] = 
                      ($diff_[$i]["click"] > 0 && $diff_[$i]["cv"] > 0 )? intval($diff_[$i]["click"])/intval($diff_[$i]["cv"]): 0 ;
                      $diff_[$i]["estimate_cv"] = $site["estimate_cv"];
                      $diff_[$i]["price"] = $site["price"];
                      $diff_[$i]["cpa"] = $site["cpa"];
                      $diff_[$i]["cost"] = $site["cost"] ;
                      $diff_[$i]["media_id"] = $site["media_id"];
                      $diff_[$i]["site_name"] = $site["site_name"];
                      $diff_[$i]["date"] = $site["date"];
                      $diff_[$i]["product_id"] = $site["product_id"];
                      $i++;
                  }
              
          }
        }else{
            $diff_= $Array ;
        }
        
        echo "<pre>";
        echo "result1";
        var_dump($diff_);
        echo "</pre>";
        //$daily_diff = new DailyDiff();
        foreach ($diff_ as $insert_diff) {
          //echo "<pre>";
          //var_dump($insert_diff);
          //echo "</pre>";
            DailySiteDiff::create(
                [
                  'imp' => $insert_diff["imp"],
                  'ctr' => $insert_diff["ctr"],
                  'click' => $insert_diff["click"],
                  'cv' => $insert_diff["cv"],
                  'cvr' => $insert_diff["cvr"],
                  'media_id' => $insert_diff["media_id"],
                  'site_name' => $insert_diff["site_name"],
                  'price' => $insert_diff["price"],
                  'cpa' => $insert_diff["cpa"],
                  'cost' => $insert_diff["cost"],
                  'date' => $insert_diff["date"],
                  'estimate_cv' => $insert_diff["estimate_cv"],
                  'product_id' => $insert_diff["product_id"]
                ]
            );
        }

    }
    public function dailytimer(){

      $products = Schedule::Select('product_base_id')->where('killed_flag',0)->get()->toArray();
      //var_dump($products);

      foreach($products as $product){
          $aspRow = array();
          $asp_array = array();
          //echo $product["product_base_id"];
          $asp_name = $this->filterAsp($product["product_base_id"]);
          //var_dump($asp_name);
          $asp_array = (json_decode($asp_name,true));
          //echo gettype($asp_id);
          foreach ($asp_array as $name){
            array_push($aspRow,str_replace(' ', '' ,mb_strtolower($name["name"])));
          }
          //var_dump($aspRow);

          foreach($aspRow as $function_name){
            $this->{$function_name}($product["product_base_id"]);

          }
            /**
              差分からデイリーの件数を取得
            */
            //$this->diff($product["product_base_id"]);
            //$this->diff_site($product["product_base_id"]);
      }
    }


  }