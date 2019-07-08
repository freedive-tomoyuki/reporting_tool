<?php

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = [
        	['BroadWiMAX' ,'1' , 'eclogin','auhikari','ecpasswd','qmfk4Ivr'],
        	['BroadWiMAX' ,'2' , 'login_name','broadwimax', 'password','c1xVXpjw' ],
        	['BroadWiMAX' ,'3' , 'userName','ac-si', 'password','50dosuvu3v9' ],
        	['BroadWiMAX' ,'4' , 'login_form[email_address]','imai@surprizz.co.jp', 'login_form[password]','Wimax12345' ],
        ];
        foreach ($products as $product) {
            DB::table('products')->insert(
        		[
		          'product' => $product[0],
		          'asp_id' => $product[1],
		          'login_key' => $product[2],
		          'login_value' => $product[3],
		          'password_key' => $product[4],
		          'password_value' => $product[5]
		        ]
        	);
        }
    }
}
