<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailyTotal extends Model
{
	protected $table = 'daily_totals';
    protected $fillable =[
    	'product_id', 
    	'total_imp', 
    	'total_click',
    	'total_cv',
    	'total_cvr',
    	'total_ctr',
    	'total_cost',
    	'total_price', 
    	'total_cpa',
    	'date',
    ];
}
