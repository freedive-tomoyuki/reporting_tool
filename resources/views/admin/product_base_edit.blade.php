@extends('layouts.admin')

@section('content')

<div class="row">
    <ol class="breadcrumb">
        <li><a href="{{ url('admin/product_list')}}">広告主管理</a></li>
        <li class="active">広告主編集</li>
    </ol>
    <div class="col-md-12">
        <h2 class="card-header">広告主編集</h2>
    </div>
</div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                

                <div class="card-body">
                    <form method="POST" action="{{ url('admin/product_base/edit/'. $product_bases[0]['id']) }}" aria-label="{{ __('Register') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">広告主名</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? 'is-invalid' : '' }}" name="name" value="{{ $product_bases[0]['product_name'] }}"  autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">タイマー設定</label>

                            <div class="col-md-6">
                                <select id="schedule" type="text" class="form-control" name="schedule" autofocus>
                                    <option value="0" @if($schedule[0]['killed_flag'] == 0 ) selected @endif>Active</option>
                                    <option value="1" @if($schedule[0]['killed_flag'] == 1 ) selected @endif>No Active</option>
                                </select>
                                @if ($errors->has('schedule'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('schedule') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <input type="hidden" name="id" value="{{ $product_bases[0]['id'] }}">
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
