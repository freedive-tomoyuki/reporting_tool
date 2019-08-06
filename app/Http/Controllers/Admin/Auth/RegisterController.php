<?php

namespace App\Http\Controllers\Admin\Auth;

use App\User;
use Auth;
use App\Schedule;
use App\ProductBase;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Listeners\SendEmailVerificationNotification;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    //protected $redirectTo = '/admin/daily_report';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest');
        $this->middleware('auth:admin');
    }
    public function showRegisterForm()
    {
        $user = Auth::user();
        return view('admin.register',compact('user'));
    }
    /**
    * ユーザー登録処理
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function register( Request $request )
    {
        // バリデーション
        $this->validator( $request->all() )->validate();
 
        // ユーザー登録のLaravelシステムイベント発行
        event( new Registered( $user = $this->create( $request->all() ) ) );
        //var_dump($user);

        // 登録時はログインさせない
        //$this->guard()->login($this->middleware('auth:admin'));
 
        return redirect('/admin/daily_result');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $name = $data['name'];
        $email = $data['email'];

        $data = ProductBase::create([
            'product_name' => $name,
        ]);
        Schedule::create([
            'killed_flag' => 0,
            'product_base_id' => $data->id,
        ]);
        
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($data['password']),
            'product_base_id' => $data->id,
        ]);
        
    }
}
