<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Asp extends Model
{
    
    protected $table = 'asps';

    public function product(){
        return $this->hasMany('App\Product');
    }
}
