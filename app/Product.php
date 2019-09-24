<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //protected $fillable =['product'];
	protected $table = 'products';
    protected $fillable =[
        'product', 
        'asp_id', 
        'login_value',
        'password_value',
        'asp_product_id',
        'asp_sponsor_id',
        'product_base_id',
        'product_order',
    ];

    public function asp()
    {
        return $this->belongsTo('App\Asp');
    }
    public function product_base()
    {
        return $this->belongsTo('App\ProductBase');
    }
    public function dailydata()
    {
        return $this->hasMany('App\Dailydata');
    }
}
