<?php

namespace App\Http\Controllers\Api;

use DB;
use App\Product;
use App\Asp;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class getAspController extends Controller
{
/*    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:api');
    }*/
    /*
     必須項目取得
    */
    public function getRequiredFlag($id)
    {
        //$RequiredFlag = 1;
        //var_dump(1);
        $RequiredFlag = DB::table('asps')->select('sponsor_id_require_flag','product_id_require_flag')->where('id', $id)->get();
        return $RequiredFlag;
    }

}
