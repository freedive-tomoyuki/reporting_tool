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
}
