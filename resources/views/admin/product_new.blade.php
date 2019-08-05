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
<div class="container" id="app">
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
                                <select class="form-control" name="asp_id" v-model="selected" v-on:change="switchAsp">
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
                                <input id="loginid" type="text" class="form-control{{ $errors->has('loginid') ? ' is-invalid' : '' }}" name="loginid" value="" >

                                @if ($errors->has('loginid'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('loginid') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">パスワード</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" value="" >
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_sponsor_id" class="col-md-4 col-form-label text-md-right">ASP:広告主ID
                                <component_sponsor v-if="show"></component_sponsor>
                        </label>

                            <div class="col-md-6">
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" v-if="any">
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" v-if="required" required>

                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_product_id" class="col-md-4 col-form-label text-md-right" >ASP:案件ID
                                <component_product v-if="show1"></component_product>
                        </label>

                            <div class="col-md-6">
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" v-if="any">
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" v-if="required" required>
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
<script src="https://cdn.jsdelivr.net/npm/vue@2.5.13/dist/vue.min.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script>
        var ComponentA = {
            template: "<font style='color:red'>*</font>",
        }
        var ComponentB = {
            template: "<font style='color:red'>*</font>",
        }
        new Vue({
            el: '#app',
            data: {
                selected: {{ $products[0]['asp_id'] }},
                show: false,
                show1: false,
                any: true,
                required: false,
                any1: true,
                required1: false,
            },
            components: {
              'component_sponsor': ComponentA,
              'component_product': ComponentB,
            },
            methods: {
                switchAsp : function() {
                    var id = this.selected ;
                    //console.log(id);
                    axios.get('/api/getRequiredFlag/' + id).then((res)=>{
                        if(res.data[0]['sponsor_id_require_flag'] == 1 ){
                            this.show = true;
                            this.any = false;
                            this.required = true;
                        }else{
                            this.show = false;
                            this.any = true;
                            this.required = false;
                        }
                        if(res.data[0]['product_id_require_flag'] == 1 ){
                            this.show1 = true;
                            this.any1 = false;
                            this.required1 = true;
                        }else{
                            this.show1 = false;
                            this.any1 = true;
                            this.required1 = false;
                        }
                    })
                    .catch(error => { 
                        console.log(error)
                    })
                    .then(response => { 
                        console.log(response)
                    })
                }
            }
        })
    </script>
@endsection
