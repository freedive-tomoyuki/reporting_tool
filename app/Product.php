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
        'price',
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
    /**
     * 可動しているASPを精査する関数
     */
    public function filterAsp($product_id)
    {
        $target_asp = Product::select('asp_id', 'name')
        ->join('asps', 'products.asp_id', '=', 'asps.id')
        ->where('product_base_id', $product_id)
        ->where('products.killed_flag', 0)
        ->get();
        
        return json_encode($target_asp);
    }
    /**
     * 親案件から案件一覧を取得する。
     * @param number $baseproduct
     * @return array $converter 
     */
    function convertProduct($base_product)
    {
        $converter = New Product;
        $converter->where('product_base_id', $base_product);
        $converter = $converter->get()->toArray();
        return $converter;
    }

}
