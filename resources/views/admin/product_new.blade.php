@extends('layouts.admin')

@section('content')



<div class="row">
    <ol class="breadcrumb">
      <li><a href="{{ url('admin/product_list')}}">広告主管理</a></li>
      <li class="active">案件登録</li>
    </ol>
    <div class="col-md-12">
        <h2 class="card-header">案件登録</h2>
    </div>
</div>
<div class="container" >
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                

                <div class="card-body">
                    
                    <form method="POST" action="{{ url('admin/product/add')}}" aria-label="{{ __('Register') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">案件名<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}"  autofocus>

                                @if ($errors->has('name'))
                                    <div class="alert alert-danger" role="alert">
                                        {{ $errors->first('name') }}
                                    </div>
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
                                    <div class="alert alert-danger" role="alert">
                                        {{ $errors->first('product') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">ASP<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <select class="form-control" name="asp_id" v-model="selected" v-on:change="switchAsp" v-bind:readonly="fixed">
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
                                    <div class="alert alert-danger" role="alert">
                                        {{ $errors->first('asp_id') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="loginid" class="col-md-4 col-form-label text-md-right" >ログインID<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="loginid" type="text" class="form-control{{ $errors->has('loginid') ? ' is-invalid' : '' }}" name="loginid" value="{{ old('loginid') }}"  v-model="login" v-bind:readonly="fixed">

                                @if ($errors->has('loginid'))
                                    <div class="alert alert-danger" role="alert">
                                        {{ $errors->first('loginid') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right" >パスワード<font style="color:red">*</font></label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password"  v-model="password" v-bind:readonly="fixed">

                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="price" class="col-md-4 col-form-label text-md-right" >単価</label>
                            <div class="col-md-6">
                                <input id="price" type="text" class="form-control" name="price">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_sponsor_id" class="col-md-4 col-form-label text-md-right">ASP:広告主ID
                                <component_sponsor v-if="show"></component_sponsor>
                        </label>

                            <div class="col-md-6">
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" v-model="sponsor" v-if="any" value="{{ old('asp_sponsor_id') }}" >
                                <input id="asp_sponsor_id" type="text" class="form-control" name="asp_sponsor_id" v-model="sponsor" v-if="required" value="{{ old('asp_sponsor_id') }}" required>

                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asp_product_id" class="col-md-4 col-form-label text-md-right" >ASP:案件ID
                                <component_product v-if="show1"></component_product>
                        </label>

                            <div class="col-md-6">
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" v-model="product" v-if="any" value="{{ old('asp_product_id') }}">
                                <input id="asp_product_id" type="text" class="form-control" name="asp_product_id" v-model="product" v-if="required" value="{{ old('asp_product_id') }}" required>
                            </div>
                        </div>
                        <div class="form-group row" v-if="product_order">
                            <label for="asp_product_id" class="col-md-4 col-form-label text-md-right">対象案件のフォームの順序
                            <font style='color:red'>*</font>
                            </label>
                            <div class="col-md-6">
                                <select class="form-control" name='product_order'>
                                
                                    <option v-for="n in 5" v-bind:value="n" > @{{ n }}番目</option>
                                
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 offset-md-4">
                                <button type="submit" class="btn btn-primary" v-if="ok">
                                    {{ __('Register') }}
                                </button>
                                <button type="button" id="checkStart" class="btn btn-success" v-else v-on:click="checkStart">
                                    {{ __('Check') }}
                                </button>
                                <div class="pull-right">
                                    <item-component v-show="loading"></item-component>
                                </div>
                        </div>
                        <div class="col-md-8 offset-md-6">
                            <div class="alert alert-danger " v-if="errorMessage">ID/PASSWORDが異なります</div>
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
        var ItemComponent = {
            template: "<div class='loader'>Loading...</div>",
        }
        new Vue({
            el: '#app',
            data: {
                selected: '',
                show: false,
                show1: false,
                any: true,
                required: false,
                any1: true,
                required1: false,
                ok: false,
                errorMessage: false,
                login:'',
                password:'',
                product:'',
                sponsor:'',
                loading: false,
                product_order:false,
                fixed:false,
                
            },
            components: {
              'component_sponsor': ComponentA,
              'component_product': ComponentB,
              'item-component': ItemComponent
            },
            methods: {
                switchAsp : function() {
                    var id = this.selected ;
                    //console.log(id);
                    axios.get('{{ url("api/getRequiredFlag/")}}/' + id).then((res)=>{
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
                        if(id == 6 || id == 4 ){
                            this.product_order = true;
                            this.fixed = false;
                            this.ok = false;
                        }else if(id==12){
                            this.ok = true;
                            this.errorMessage = false;
                            this.fixed = true;
                            this.product_order = false;
                        }else{
                            this.fixed = false;
                            this.ok = false;
                            this.product_order = false;
                        }
                    })
                    .catch(error => { 
                        console.log(error)
                    })
                    .then(response => { 
                        console.log(response)
                    })
                },
                checkStart:function(){
                    //this.login = 
                    console.log(this);
                    this.loading = true;
                    axios.post('{{ url("admin/product/check")}}',
                    {
                        login:this.login,
                        password:this.password,
                        asp_id:this.selected,
                        sponsor:this.sponsor,
                        product:this.product,
                    }
                    ).then((res)=>{
                        console.log(res.data);
                        this.loading = false;
                        if(res.data == 1){
                            this.ok = true;
                            this.errorMessage = false;
                            this.fixed = true;

                        }else{
                            this.ok = false;
                            this.errorMessage = true;
                        }
                    }).catch(error => { 
                        this.loading = false;
                        this.errorMessage = true;
                    })

                }
            }
        })
    </script>
@endsection
