<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Monthlydata extends Model
{
    protected $fillable =[
    	'imp',
    	'click',
    	'cv',
    	'cvr',
    	'ctr',
    	'active',
    	'partnership',
    	'price',
    	'cpa',
    	'cost',
    	'approval',
    	'approval_price',
        'approval_rate',
    	'asp_id',
    	'product_id',
    	'estimate_cv',
    	'date'
	];
	public function asp()
    {
        return $this->belongsTo('App\Asp');
	}
	public function product(){
        return $this->belongsTo('App\Product');
    }

}
