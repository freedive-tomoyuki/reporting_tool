<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dailydata extends Model
{
    protected $fillable =[
    	'imp','click','cv','cvr','ctr','active','partnership','price','cpa','cost','asp_id','product_id','estimate_cv','date'
    ];

}
