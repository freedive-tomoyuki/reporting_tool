<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dailysite extends Model
{
    protected $fillable =[
    	'media_id', 'site_name', 'imp', 'click', 'cv', 'cvr', 'ctr', 'url','product_id', 'price','cpa','cost','date','estimate_cv',
    ];

}
