@extends('layouts.appnew')

@section('content')
<div class="row">
    <ol class="breadcrumb">
      <li><a href="/admin/product_list">広告主管理</a></li>
      <li ><a href="/admin/product_detail/{{ $products[0]['id'] }}">案件一覧</a></li>
      <li class="active">案件編集</li>
    </ol>
    <div class="col-md-12">
        <h2 class="card-header">案件編集</h2>
    </div>
</div>
<div class="container" id="app">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                

                <div class="card-body">
                    <form method="POST" action="/admin/product/edit/{{ $products[0]['id'] }}" aria-label="{{ __('Register') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">案件名<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? 'is-invalid' : '' }}" name="name" value="{{ $products[0]['product'] }}"  autofocus>

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
                                            
                                            @foreach($product_bases as $product_base)
                                              <option value="{{ $product_base -> id }}"
                                                @if(  $products[0]['product_base_id'] == $product_base->id  )
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
                                <select class="form-control" name="asp_id"  v-model="selected" v-on:change="switchAsp">
                                  <option value=""> -- </option>
                                            @foreach($asps as $asp)
                                              <option value="{{ $asp -> id }}"
                                                @if( $products[0]['asp_id'] == $asp->id  )
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
                                <input id="loginid" type="text" class="form-control{{ $errors->has('loginid') ? ' is-invalid' : '' }}" name="loginid" value="{{  $products[0]['login_value'] }}">

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
                                <input id="password" type="password" class="form-control" name="password" value="{{ $products[0]['password_value'] }}" >
                            </div>
                        </div> 
                        
                        <div class="form-group row">
                            <label for="asp_sponsor_id" class="col-md-4 col-form-label text-md-right">ASP:広告主ID
                            <component_sponsor v-if="show"></component_sponsor>
                            </label>

                            <div class="col-md-6">
                                
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" v-if="any" value="{{ $products[0]['asp_sponsor_id'] }}">
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" v-if="required"   value="{{ $products[0]['asp_sponsor_id'] }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_product_id" class="col-md-4 col-form-label text-md-right">ASP:案件ID
                            <component_product v-if="show1"></component_product>
                            </label>

                            <div class="col-md-6">
                                
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" v-if="any" value="{{ $products[0]['asp_product_id'] }}">
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" v-if="required" value="{{ $products[0]['asp_product_id'] }}" required>
                            </div>
                        </div>
                        <input type="hidden" name="id" value="{{ $products[0]['id'] }}">
                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Edit') }}
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
