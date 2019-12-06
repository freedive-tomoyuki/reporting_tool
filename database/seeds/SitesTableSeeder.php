<?php

use Illuminate\Database\Seeder;

class SitesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$sites = array(
			array(
				'media_id'=>'61485', 
				'site_name'=>'宿便解消したよー！快調茶の実際の効果はこうでした【口コミも！】',
				'url'=>'https://快調茶効果.jp',
				'asp_id'=>'6',
				'product_id'=>'15',
				'unit_price'=>'1000'  
			),			
			array(
				'media_id'=>'59085', 
				'site_name'=>'sorishiyoドットコム',
				'url'=>'https://soreshiyo.com/',
				'asp_id'=>'6',
				'product_id'=>'15',
				'unit_price'=>'1000'  
			),
			array(
				'media_id'=>'9566', 
				'site_name'=>'春の出雲旅行日記',
				'url'=>'http://141414.pupu.jp/izumo/',
				'asp_id'=>'6',
				'product_id'=>'15',
				'unit_price'=>'1500'  
			)
		);
		foreach ($sites as $site) {
            DB::table('sites')->insert(
        		[
				'media_id' => $site['media_id'],
				'site_name' => $site['site_name'],
				'url' => $site['url'],
				'asp_id' => $site['asp_id'],
				'product_id' => $site['product_id'],
				'unit_price' => $site['unit_price'],
				'killed_flag' => 0,
		        ]
        	);
        }
    }
}
