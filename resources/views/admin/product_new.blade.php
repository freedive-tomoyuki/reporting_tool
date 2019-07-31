@extends('layouts.appnew')

@section('content')
<div class="row">
    <ol class="breadcrumb">
      <li><a href="/admin/product_list">広告主管理</a></li>
      <li class="active">案件登録</li>
    </ol>
    <div class="col-md-12">
        <h2 class="card-header">案件登録</h2>
    </div>
</div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                

                <div class="card-body">
                    <form method="POST" action="/admin/product/add" aria-label="{{ __('Register') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">案件名<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}"  autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">広告主<font style="color:red">*</font></label>

                            <div class="col-md-6">
                               <select class="form-control" name="product" >
                                            <option value=""> -- </option>
                                            {{$product_bases}}
                                            @foreach($product_bases as $product_base)
                                              <option value="{{ $product_base -> id }}"
                                                @if( old('product') == $product_base->id  )
                                                  selected
                                                @endif

                                                >{{ $product_base->product_name }}
                                            </option>
                                            @endforeach
                                </select>
                                @if ($errors->has('product'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('product') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">ASP<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <select class="form-control" name="asp_id" >
                                  <option value=""> -- </option>
                                            @foreach($asps as $asp)
                                              <option value="{{ $asp -> id }}"
                                                @if( old('asp_id') == $asp->id  )
                                                  selected
                                                @endif
                                                >{{ $asp -> name }}</option>
                                                
                                            @endforeach
                                </select>
                                @if ($errors->has('asp_id'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('asp_id') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="loginid" class="col-md-4 col-form-label text-md-right">ログインID<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="loginid" type="text" class="form-control{{ $errors->has('loginid') ? ' is-invalid' : '' }}" name="loginid" >

                                @if ($errors->has('loginid'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('loginid') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">パスワード<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" >
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_sponsor_id" class="col-md-4 col-form-label text-md-right">ASP:広告主ID@if($products[0]['sponsor_id_require_flag'] == 1 )
                            <font style="color:red">*</font>
                            @endif</label>

                            <div class="col-md-6">
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" @if($products[0]['sponsor_id_require_flag'] == 1 )
                            required 
                            @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_product_id" class="col-md-4 col-form-label text-md-right">ASP:案件ID@if($products[0]['sponsor_id_require_flag'] == 1 )
                            <font style="color:red">*</font>
                            @endif</label>

                            <div class="col-md-6">
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" @if($products[0]['sponsor_id_require_flag'] == 1 )
                            required 
                            @endif>
                            </div>
                        </div>
                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
