<?php

use Illuminate\Database\Seeder;

class AspTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $asps = ['A8','VC','abf','AccessTrade'];
        foreach ($asps as $asp) {
            DB::table('asp')->insert(
        		[
		          'name' => $asp,
		          'killed_flag' => '0',
		        ]
        	);
        }
    }
}
