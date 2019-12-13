<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Product;

class Asp extends Model
{
    
    protected $table = 'asps';

    public function product(){
        return $this->hasMany('App\Product');
    }
    public function target_asp($id): array
    {
        $asps = array();

        $target_asp_id = Product::select('asp_id')
                                ->where('product_base_id','=',$id)
                                ->where('killed_flag','=', 0)
                                ->get()
                                ->toArray();

        foreach($target_asp_id as $a ){
            array_push($asps , $a['asp_id']);
        }
        $asps = Asp::select("name",'id')->whereIn('id',$asps)->where('killed_flag','=', 0)->get()->toArray();
        return $asps;
    }
}
