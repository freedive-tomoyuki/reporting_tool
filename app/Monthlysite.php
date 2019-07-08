<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Monthlysite extends Model
{

    //const $month = date('Ym');
    
    //protected $table = $month.'_monthlysites';
    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $month = date('Ym',strtotime('-1 day'));
        $model = parent::newInstance($attributes, $exists);
        // 設定されている関連テーブルを新しいインスタンスにも設定
        $model->setTable( $month.'_monthlysites' );
        return $model;
    }

    protected $fillable =[
    	'media_id', 
    	'site_name', 
    	'imp', 
    	'click', 
    	'cv', 
    	'cvr', 
    	'ctr', 
    	'url',
    	'product_id', 
    	'price',
    	'cpa',
    	'approval',
    	'approval_price',
        'approval_rate',
    	'cost',
    	'date',
        'estimate_cv',
    ];

}
