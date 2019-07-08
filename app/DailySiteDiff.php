<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailySiteDiff extends Model
{

	//const $month = date('Ym');
	
	//protected $table =  $month.'_daily_site_diffs';
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
	    $model->setTable( $month.'_daily_site_diffs' );
	    return $model;
	}
	
    protected $fillable =[
    	'imp','click','cv','cvr','ctr','media_id','site_name','price','cpa','cost','product_id','date','killed_flag','estimate_cv'
    ];



}
