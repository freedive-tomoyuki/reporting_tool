<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Controller;
use App\Asp;


class CheckerController extends Controller
{
    //protected $calculationservice;

    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
    }
    public function index()
    {
        $user = Auth::user();
        $product_bases = ProductBase::all();
        //var_dump($product_bases);
        return view('admin.crawlerdaily',compact('product_bases','user'));
    }
    public function show_test()
    {
      $datas = \App\Product::all()->where('id','6');
      foreach($datas as $data)
      {
              echo $data->asp->login_url;
      }
    }

    public function check(Request $request){

          $result = 0;

          if(($request->login != '') && ($request->password != '') && ($request->asp_id != '') ){

              //echo "aa";
              $asp_name = Asp::select('name')->where('id', '=' ,$request->asp_id)->get()->toArray();
              $functionName = str_replace(' ', '' ,mb_strtolower($asp_name[0]["name"]));

              $className = 'App\Http\Controllers\Admin\Asp\Check'. '\\'.str_replace(' ', '' ,$asp_name[0]["name"]).'Controller';
              $run = new $className();
          
              $pid = ($request->product != '')? $request->product : '' ;
              $sid = ($request->sponsor != '')? $request->sponsor : '' ;
              
              $result = $run->{$functionName}($request->login ,$request->password, $pid ,$sid );
          
          }else{
            $result = 0;
          }
          //var_dump($result);
          return $result;

          //return redirect()->to('/daily_result', $status = 302, $headers = [], $secure = null);
    }

}