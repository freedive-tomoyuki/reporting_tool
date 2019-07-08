<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailyEstimateTotal extends Model
{
    protected $table = 'daily_estimate_totals';
    protected $fillable =[
    	'product_base_id', 
    	'estimate_total_imp', 
    	'estimate_total_click',
    	'estimate_total_cv',
    	'total_imp',
    	'total_click',
    	'total_cv',
        'estimate_total_cost',
        'estimate_total_price',
        'estimate_total_cvr',
        'estimate_total_ctr',
        'estimate_total_cpa',
        'ratio',
    	'date'
    ];
}
