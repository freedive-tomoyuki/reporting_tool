<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailyDiff extends Model
{
    protected $fillable =[
    	'imp','click','cv','cvr','ctr','active','partnership','price','cpa','cost','asp_id','product_id','date','estimate_cv','killed_flag'
    ];

}
