<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MonthlyTotal extends Model
{
	protected $table = 'monthly_totals';
    protected $fillable =[
    	'product_base_id', 
    	'total_imp', 
    	'total_click',
    	'total_cv',
    	'total_cvr',
    	'total_ctr',
    	'total_cost',
    	'total_price', 
    	'total_cpa',
        'total_approval', 
        'total_approval_price',
    	'date',

    ];
}
