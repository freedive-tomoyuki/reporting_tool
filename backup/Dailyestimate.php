<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dailyestimate extends Model
{
	protected $table = 'dailyestimates';
    protected $fillable =[
    	'asp_id' ,
    	'product_id', 
    	'estimate_imp', 
    	'estimate_click',
    	'estimate_cv',
    	'date',
    	'estimate_cost',
    	'estimate_price',
    	'estimate_cvr', 
    	'estimate_ctr',
    	'estimate_cpa',
    	'ratio',
    ];
}
