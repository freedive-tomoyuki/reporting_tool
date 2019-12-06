<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Site;

class SiteController extends Controller
{
    //コンストラクタ
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function list(){
        $user = Auth::user();
        $sites = Site::paginate(100);
        // var_dump($user);
        return view('admin.site_list',compact('sites','user'));
    }
    public function search(Request $request){
        //$user = $this->user;
        //$request->site_name;
        $user = Auth::user();
        $sites = New Site;

        if(isset($request->asp)){
            $asp = $request->asp;
            $sites = $sites->where('asp_id', '=', $asp);
        }
        if(isset($request->site_name)){
            $site_name = $request->site_name;
            $sites = $sites->where('site_name', 'like', "%{$site_name}%" );
        }
        
        $sites = $sites->paginate(100);
        
        return view('admin.site_list',compact('sites','user'));

    }
}
