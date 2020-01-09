<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
       //protected $fillable =['product'];
	protected $table = 'sites';
    protected $fillable =[
        'asp_id', 
        'site_name',
        'media_id',
        'url',
        'unit_price'
    ];

    public function asp()
    {
        return $this->belongsTo('App\Asp');
    }
}
