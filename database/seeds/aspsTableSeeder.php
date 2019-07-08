<?php

use Illuminate\Database\Seeder;

class aspsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$asps = array(
			array(
				'name'=>'A8', 
				'login_url'=>'https=>//www.a8.net/',
				'login_selector'=>'#headerRight > form > ul > li.lgnBtn > input[type="image"]',
				'daily_imp_selector'=>'//*[@id="element"]/tbody/tr[1]/td[5]',
				'daily_imp_url'=>'lp1_url',
				'daily_click_selector'=>'//*[@id="element"]/tbody/tr[1]/td[6]',
				'daily_click_url'=>'lp1_url',
				'daily_cv_selector'=>'//*[@id="element"]/tbody/tr[1]/td[10]',
				'daily_cv_url'=>'lp1_url',
				'daily_active_selector'=>'//*[@id="element"]/tbody/tr[1]/td[3]',
				'daily_active_url'=>'lp1_url',
				'daily_partnership_selector'=>'//*[@id="element"]/tbody/tr[1]/td[2]',
				'daily_partnership_url'=>'lp1_url',
				'lp1_url'=>'https=>//adv.a8.net/a8v2/ecQuickReportAction.do?reportType=21&insId=s00000017954001' ,
				'lp2_url'=>''  , 
				'lp3_url'=>''  , 
				'lp4_url'=>''  , 
				'lp5_url'=>''  , 
				'lp6_url'=>''  , 
				'lp7_url'=>''  , 
				'lp8_url'=>''  , 
				'lp9_url'=>''  
			),
			array(
				'name'=>'Access Trade', 
				'login_url'=>'https=>//www.accesstrade.ne.jp/',
				'login_selector'=>'#login_m > form > input',
				'daily_imp_selector'=>'body > program-page > div > div > main > program-home > section=>nth-child(2) > div > div > div > summary-report > div > table > tbody > tr=>nth-child(1) > td:nth-child(2)',
				'daily_imp_url'=>'lp1_url',
				'daily_click_selector'=>'body > program-page > div > div > main > program-home > section:nth-child(2) > div > div > div > summary-report > div > table > tbody > tr:nth-child(1) > td:nth-child(3)',
				'daily_click_url'=>'lp1_url',
				'daily_cv_selector'=>'body > program-page > div > div > main > program-home > section:nth-child(2) > div > div > div > summary-report > div > table > tbody > tr:nth-child(1) > td:nth-child(4)',
				'daily_cv_url'=>'lp1_url',
				'daily_active_selector'=>'body > program-page > div > div > main > program-home > section:nth-child(3) > div:nth-child(1) > div > tiny-affiliate > div.ma_contents_body > table > tbody > tr:nth-child(2) > td:nth-child(2)',
				'daily_active_url'=>'lp1_url',
				'daily_partnership_selector'=>'body > program-page > div > div > main > program-home > section:nth-child(3) > div:nth-child(1) > div > tiny-affiliate > div.ma_contents_body > table > tbody > tr:nth-child(2) > td:nth-child(2) > a',
				'daily_partnership_url'=>'',
				'lp1_url'=>'https://merchant.accesstrade.net/matv3/program/program.html?programId=662365' , 
				'lp2_url'=>''  , 
				'lp3_url'=>''  , 
				'lp4_url'=>''  , 
				'lp5_url'=>''  , 
				'lp6_url'=>''  , 
				'lp7_url'=>''  , 
				'lp8_url'=>''  , 
				'lp9_url'=>''  
			),
			array(
				'name'=>'Value Commerce', 
				'login_url'=>'https://mer.valuecommerce.ne.jp/?type=4',
				'login_selector'=>'#login_mer > input.btn_purple',
				'daily_imp_selector'=>'#report > tbody > tr:nth-child(3) > td:nth-child(5)',
				'daily_imp_url'=>'lp1_url',
				'daily_click_selector'=>'#report > tbody > tr:nth-child(3) > td:nth-child(8)',
				'daily_click_url'=>'lp1_url',
				'daily_cv_selector'=>'#report > tbody > tr:nth-child(3) > td:nth-child(11)',
				'daily_cv_url'=>'lp1_url',
				'daily_active_selector'=>'#cusomize_wrap > span',
				'daily_active_url'=>'lp2_url',
				'daily_partnership_selector'=>'#report > tbody > tr:nth-child(3) > td:nth-child(2)',
				'daily_partnership_url'=>'lp1_url',
				'lp1_url'=>'https://mer.valuecommerce.ne.jp/report/simple_summary/' , 
				'lp2_url'=>'https://mer.valuecommerce.ne.jp/affiliate_analysis/'  , 
				'lp3_url'=>''  , 
				'lp4_url'=>''  , 
				'lp5_url'=>''  , 
				'lp6_url'=>''  , 
				'lp7_url'=>''  , 
				'lp8_url'=>''  , 
				'lp9_url'=>''  
			),
			array(
				'name'=>'afb', 
				'login_url'=>'https://www.afi-b.com/',
				'login_selector'=>'#pageTitle > aside.m-grid__itemOrder--03.m-gheader__loginForm > g-header-loginform > div.m-form__wrap > form > div > div.m-gLoginGlid__btn > m-btn > div > input',
				'daily_imp_selector'=>'#reportTable > tbody > tr.totalBgYellow > td:nth-child(3) > p',
				'daily_imp_url'=>'lp1_url',
				'daily_click_selector'=>'#reportTable > tbody > tr.totalBgYellow > td:nth-child(4) > p',
				'daily_click_url'=>'lp1_url',
				'daily_cv_selector'=>'#reportTable > tbody > tr.totalBgYellow > td:nth-child(7) > p',
				'daily_cv_url'=>'lp1_url',
				'daily_active_selector'=>'#report_view > div > ul > li:nth-child(4)',
				'daily_active_url'=>'lp3_url',
				'daily_partnership_selector'=>'form > ul.table_pagination > p',
				'daily_partnership_url'=>'lp2_url',
				'lp1_url'=>'https://client.afi-b.com/client/b/cl/report/?r=monthly' , 
				'lp2_url'=>'https://client.afi-b.com/client/b/cl/relationlist#relation_list'  , 
				'lp3_url'=>'https://client.afi-b.com/client/b/cl/report/?r=site#report_view'  , 
				'lp4_url'=>''  , 
				'lp5_url'=>''  , 
				'lp6_url'=>''  , 
				'lp7_url'=>''  , 
				'lp8_url'=>''  , 
				'lp9_url'=>''  
			)
		);
        foreach ($asps as $asp) {
            DB::table('asps')->insert(
        		[
		          'name' => $asp['name'],
		          'killed_flag' => 0,
		          'login_url'=> $asp['login_url'], 
		          'login_selector'=> $asp['login_selector'], 
		          'daily_imp_selector'=> $asp['daily_imp_selector'], 
		          'daily_imp_url'=> $asp['daily_imp_url'], 
		          'daily_click_selector'=> $asp['daily_click_selector'], 
		          'daily_click_url'=> $asp['daily_click_url'], 
		          'daily_cv_selector'=> $asp['daily_cv_selector'], 
		          'daily_cv_url'=> $asp['daily_cv_url'], 
		          'daily_active_selector'=> $asp['daily_active_selector'], 
		          'daily_active_url'=> $asp['daily_active_url'], 
		          'daily_partnership_selector'=> $asp['daily_partnership_selector'], 
		          'daily_partnership_url'=> $asp['daily_partnership_url'], 
		          'lp1_url'=> $asp['lp1_url'], 
		          'lp2_url'=> $asp['lp2_url'], 
		          'lp3_url'=> $asp['lp3_url'], 
		          'lp4_url'=> $asp['lp4_url'], 
		          'lp5_url'=> $asp['lp5_url'], 
		          'lp6_url'=> $asp['lp6_url'], 
		          'lp7_url'=> $asp['lp7_url'], 
		          'lp8_url'=> $asp['lp8_url'], 
		          'lp9_url'=> $asp['lp9_url'],
		        ]
        	);
        }
    }
}
