<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Asp;

class AspController extends Controller
{
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
    }

    public function list() {
        $user = Auth::user();
        $asps = Asp::all();

        return view('admin.asp_list',compact('asps','user'));
 
    }
    public function detail($id) {
        $user = Auth::user();
    	$products = Asp::find($id)->product()->select('product','id','product_base_id')->where('products.killed_flag',0)->get();
        
        $asp = Asp::Where('id',$id)->get();
    	//echo $asp->toSql();
        return view('admin.asp_detail',compact('products','asp','user'));
 
    }
    public function register_form() {
        $user = Auth::user();
    	$products = Asp::find($id)->product()->select('product','id','product_base_id')->where('products.killed_flag',0)->get();
        $asp = Asp::find($id)->get();

        return view('admin.asp_register',compact('products','asp','user'));
 
    }
}
