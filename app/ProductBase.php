<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductBase extends Model
{
	protected $fillable = ['product_name'];

    public function product()
    {
        return $this->hasMany('App\Product');
    }
    public function product_base_users(){
       //$email = $this->select("email")->join("user", "product_base.id" , "users.product_base_id")->get();
       $product_base_users = $this->select("product_bases.id","product_name","email","product_bases.created_at")
                    ->join("users", "product_bases.id" , "users.product_base_id")
                    ->where('product_bases.killed_flag',0)
                    ->get();

       return $product_base_users; 
    }
}
