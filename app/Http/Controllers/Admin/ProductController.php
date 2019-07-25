<?php

namespace App\Http\Controllers\Admin;

use DB;
use App\Product;
use App\Asp;
use App\ProductBase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProduct;
use App\Http\Requests\StoreProductBase;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function list() {
        $product_packages = array();
		$products_bases  = array();
        $user = Auth::user();
        
        $products = Product::where('killed_flag',0)->get();
        $products_id = $products->toArray();
        $products_bases = ProductBase::where('killed_flag',0)->get();

        foreach($products_id as $product_id){
        	if(!in_array( $product_id['product_base_id'],$product_packages)){
        		array_push($product_packages, $product_id['product_base_id']);
        	}
		}
        return view('admin.product_list',compact('products','products_bases','user'));
    }

    public function detail($id) {
        $user = Auth::user();
        $asps = Product::select('asps.name','asps.id','product_base_id','products.id as products_id','products.product as product_name')
                ->join('asps','products.asp_id','=','asps.id')
                ->where('product_base_id',$id)
                ->where('products.killed_flag',0)
                ->get();

        return view('admin.product_detail',compact('asps','user'));
    }

    public function add() {
        $product_bases = ProductBase::all();
        $asps = Asp::all();
        $user = Auth::user();

        return view('admin.product_new',compact('product_bases','asps','user'));
    }

    public function create_product(StoreProduct $request) {

        $product = new Product();
        $product->product = $request->name;
        $product->asp_id = $request->asp_id;
        $product->product_base_id = $request->product;
        $product->login_value = $request->loginid;
        $product->password_value = $request->password;
        $product->asp_product_id = $request->asp_product_id;
        $product->asp_sponsor_id = $request->asp_sponsor_id;
        $product->save();
 
        return redirect('/admin/product_list');
        
    }
    //各親案件の追加画面
    public function add_product_base() {
        //$product_bases = ProductBase::where('id',$id)->get()->toArray();
        $user = Auth::user();
        return view('admin.product_base_new',compact('user'));
    }
    //各親案件の追加実装
    public function create_product_base(StoreProductBase $request) {

        $product_base = new ProductBase();
        $product_base->product_name = $request->name;
        
        $product_base->save();

        return redirect('/admin/product_list');
        
    }
    //各親案件の編集画面
    public function edit_product_base($id) {
        $product_bases = ProductBase::where('id',$id)->get()->toArray();
        $user = Auth::user();
        return view('admin.product_base_edit',compact('product_bases','user'));
    }
    //各親案件の編集実装
    public function update_product_base($id,StoreProduct $request) {
        ProductBase::where('id',$id)
        ->update([
            'product_name' => $request->name,
        ]);
        return redirect('/admin/product_list');
    }
    //各案件の編集画面
    public function edit($id=null,Request $request) {
        $product_bases = ProductBase::all();
        $asps = Asp::all();
        $user = Auth::user();

        if($id != ''){
            $products = Product::where('id',$id)->get()->toArray();
        }else{
            $products = Product::where('asp_id',$request->asp_id)
                ->where('product_base_id',$request->product_base_id)
                ->get()
                ->toArray();
        }
        
        return view('admin.product_edit',compact('product_bases','asps','products','user'));
    }
    //各案件の編集実装
    public function update_product($id,StoreProduct $request) {
        Product::where('id',$id)
        ->update([
            'product' => $request->name,
            'asp_id' => $request->asp_id,
            'product_base_id' => $request->product,
            'login_value' => $request->loginid,
            'password_value' => $request->password,
            'asp_product_id' => $request->asp_product_id,
            'asp_sponsor_id' => $request->asp_sponsor_id,
            
            'killed_flag' => '0',
        ]);
        return redirect('/admin/product_list');
        
    }

    public function delete($product_base_id,$asp_id) {

        Product::where('asp_id', $asp_id)
        ->where('product_base_id', $product_base_id)
        ->update([
            'killed_flag' => '1',
        ]);
        return redirect('/admin/product_list');
    }

    //各案件の編集画面
    public function edit_product($asp_id,$product_base_id) {
        $product_bases = ProductBase::all();
        $asps = Asp::all();
        $user = Auth::user();

        $products = Product::where('asp_id',$asp_id)
                            ->where('product_base_id',$product_base_id)
                            ->get()
                            ->toArray();

        //echo $products;
        
        return view('admin.product_edit',compact('product_bases','asps','products','user'));
    }

}
