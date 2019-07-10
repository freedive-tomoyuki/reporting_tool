<?php
use App\Http\Components\CSV;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {

    return view('auth.login');
});

//Route::get('/','DailyCrawlerController@show_test')->name('show_test');

Auth::routes();
//Route::resource('hoge', 'HogeController');

/*
|--------------------------------------------------------------------------
| 3) Admin 認証不要
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'admin'], function() {
    //Route::get('/',         function () { return redirect('/admin/login'); });
    Route::get('login',     'Admin\Auth\LoginController@showLoginForm')->name('admin.login');
    Route::post('login',    'Admin\Auth\LoginController@login');
});
 
/*
|--------------------------------------------------------------------------
| 4) Admin ログイン後
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'admin', 'middleware' => 'auth:admin'], function() {
    Route::get('register', 'Admin\Auth\RegisterController@showRegisterForm')->name('admin.register');
    Route::post('register', 'Admin\Auth\RegisterController@register')->name('admin.register');

    Route::post('logout',   'Admin\Auth\LoginController@logout')->name('admin.logout');
    //Route::get('daily_result',      'Admin\DailyController@index')->name('admin.daily_result');

    //デイリーレポート実行画面
    Route::get('daily_report','Admin\DailyCrawlerController@index')->name('admin.crawlerdaily');
    Route::get('daily_report_comp','Admin\DailyCrawlerController@complete')->name('admin.crawlerdaily_com');

    //デイリーレポート実行
    Route::post('daily_report','Admin\DailyCrawlerController@run');
    Route::post('monthly_report','Admin\MonthlyCrawlerController@run')->name('admin.crawlermonthly');

    //デイリーレポート一覧
    Route::get( 'daily_result','Admin\DailyController@daily_result')->name('admin.daily_result');
    Route::post( 'daily_result','Admin\DailyController@daily_result_search');

    //デイリーレポート（サイト別）一覧
    Route::get( 'daily_result_site','Admin\DailyController@daily_result_site')->name('admin.daily_result_site');
    Route::post( 'daily_result_site','Admin\DailyController@daily_result_site_search');

    //マンスリーレポート一覧
    Route::get('monthly_result','Admin\MonthlyController@monthly_result');
    Route::post('monthly_result','Admin\MonthlyController@monthly_result_search');

    //マンスリーレポート（サイト別）一覧
    Route::get( 'monthly_result_site','Admin\MonthlyController@monthly_result_site')->name('admin.monthly_result_site');
    Route::post( 'monthly_result_site','Admin\MonthlyController@monthly_result_site_search');

    //イヤーリーレポート一覧
    Route::get( 'yearly_result','Admin\YearlyController@yearly_result')->name('admin.yearly_result');
    Route::post( 'yearly_result','Admin\YearlyController@yearly_result_search');

    //ASP一覧・詳細
    Route::get('asp_list','Admin\AspController@list')->name('admin.asp_list');
    Route::get('asp_detail/{id}','Admin\AspController@detail');

    //案件一覧・登録
    Route::get('product_list','Admin\ProductController@list')->name('admin.product_list');
    Route::get('product_detail/{id}','Admin\ProductController@detail');
    Route::get('product_asp','Admin\ProductController@edit');
    Route::get('product/edit/{id}','Admin\ProductController@edit');
    Route::post('product/edit/{id}','Admin\ProductController@update_product');
    Route::get('product/add','Admin\ProductController@add');
    Route::post('product/add','Admin\ProductController@create_product');

    //広告主一覧・登録
    Route::get('product_base/add','Admin\ProductController@add_product_base');
    Route::post('product_base/add','Admin\ProductController@create_product_base');
    Route::get('product_base/edit/{id}','Admin\ProductController@edit_product_base');
    Route::post('product_base/edit/{id}','Admin\ProductController@update_product_base');
    Route::get('product_delete/{product_base_id}/{asp_id}','Admin\ProductController@delete');

    //インポート
    Route::get('csv/import','Admin\CsvImportController@index')->name('admin.csv.import');
    //エクスポート
    Route::get('export','Admin\ExportController@index')->name('admin.csv.export');
    Route::post('export','Admin\ExportController@selected');
    
    //月次
    Route::post('csv/month/import','Admin\CsvImportController@store_month');
    Route::post('csv_site/import','Admin\CsvImportController@store_month_site');
    //日次
    Route::post('csv/daily/import','Admin\CsvImportController@store_daily');
    Route::post('csv_site/daily/import','Admin\CsvImportController@store_daily_site');
    Route::get('showApproval','Admin\MonthlyCrawlerController@calc_approval_rate');
    Route::get('showApprovalSite','Admin\MonthlyCrawlerController@calc_approval_rate_site');

    Route::get('csv/{id}/{s_date?}/{e_date?}','Admin\CsvExportController@downloadDaily');
    Route::get('csv_site/{id}/{s_date?}/{e_date?}','Admin\CsvExportController@downloadSiteDaily');
    Route::get('DownloadTemplateCsvSite',
    function() {
        $data = array();
        $csvHeader = ['日付','案件ID','ASPID','Imp','Click','CV','アクティブ','提携数','発生金額','承認件数','承認金額'];
        return CSV::download($data, $csvHeader, 'template_daily.csv');
    });
    Route::get('DownloadTemplateCsv',
    function() {
        $data = array();
        $csvHeader = ['日付','案件ID','ASPID','Imp', 'Click','CV','SiteID','Site名','発生金額','承認件数','承認金額'];
        return CSV::download($data, $csvHeader, 'template_monthly_site.csv');
    });
    Route::get('excel_test','Admin\ExportController@excel');
});
Route::group(['middleware' => 'auth:user'], function() {

    //デイリーレポート一覧
    Route::get( 'daily_result','DailyController@daily_result')->name('daily_result');
    Route::post( 'daily_result','DailyController@daily_result_search');

    //デイリーレポート（サイト別）一覧
    Route::get( 'daily_result_site','DailyController@daily_result_site')->name('daily_result_site');
    Route::post( 'daily_result_site','DailyController@daily_result_site_search');

    //マンスリーレポート一覧
    Route::get('monthly_result','MonthlyController@monthly_result');
    Route::post('monthly_result','MonthlyController@monthly_result_search');

    //マンスリーレポート（サイト別）一覧
    Route::get( 'monthly_result_site','MonthlyController@monthly_result_site')->name('monthly_result_site');
    Route::post( 'monthly_result_site','MonthlyController@monthly_result_site_search');

});

//CSV出力
Route::get('csv/{id}/{s_date?}/{e_date?}','DailyController@downloadCSV');
Route::get('csv_site/{id}/{s_date?}/{e_date?}','DailyController@downloadSiteCSV');
Route::get('dailycal','EstimateController@dailyCal');



/*
//ASP一覧・詳細
Route::get('asp_list','AspController@list')->name('asp_list');
Route::get('asp_detail/{id}','AspController@detail');

//案件一覧・登録
Route::get('product_list','ProductController@list')->name('product_list');
Route::get('product_detail/{id}','ProductController@detail');
Route::get('product_asp','ProductController@edit');
Route::get('product/edit/{id}','ProductController@edit');
Route::post('product/edit/{id}','ProductController@update_product');
Route::get('product/add','ProductController@add');
Route::post('product/add','ProductController@create_product');

//広告主登録
Route::get('product_base/add','ProductController@add_product_base');
Route::post('product_base/add','ProductController@create_product_base');
Route::get('product_base/edit/{id}','ProductController@edit_product_base');
Route::post('product_base/edit/{id}','ProductController@update_product_base');
Route::get('product_delete/{product_base_id}/{asp_id}','ProductController@delete');

*/
//テスト用
Route::get('felmat','Asp\Daily\FelmatController@felmat');
Route::get('affitown/{id}','Asp\Daily\AffiTownController@affitown');
Route::get('trafficgate/{id}','Asp\Daily\TrafficGateController@trafficgate');
Route::get('scan/{id}','Asp\Daily\SCANController@scan');
Route::get('rentracks','Asp\Daily\RentracksController@rentracks');

Route::get('affitownMonthly/{id}','Asp\Monthly\AffiTownController@affitown');
Route::get('trafficgateMonthly/{id}','Asp\Monthly\TrafficGateController@trafficgate');
Route::get('scanMonthly/{id}','Asp\Monthly\SCANController@scan');

//テスト用
Route::get('/afb', 'DailydataController@afb');
Route::get('/at', 'DailydataController@at');
Route::get('/vc', 'DailydataController@vc');
Route::get('/test','ScrapingController@index');

Route::get('test1','DailyCrawlerController@dailytimer');
Route::get('test2','MonthlyCrawlerController@monthlytimer');
Route::get('check','CheckController@check');
Route::get('diff','DailyCrawlerController@diff');
Route::get('diff_site','DailyCrawlerController@diff_site');

/*成功例
Route::get('/demo', function() {
   $crawler = Goutte::request('GET', 'https://www.rentracks.co.jp/ir/f_results/');
   $crawler->filter('table tr')->each(function ($node) {
    $th = $node->filter('th')->text();
    $td = $node->filter('td')->text();
    var_dump($td);
   });
   return view('welcome');
});*/
