@extends('layouts.sponsor')

@section('content')
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">実行</h1>
            </div>
        </div>
    <div class="row">
        <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">日次レポート</div>
                    <div class="panel-body">
                        <div class="col-md-12">
                            <br>
                            <form method='post' action="{{ route('crawlerdaily')}}">
                                @csrf
                                <div class="col-md-4 wigth-left">
                                    <select class="form-control" name="product" >
                                        <option value=""> -- </option>
                                        @foreach($product_bases as $product_base)
                                         <option value="{{ $product_base -> id }}">{{ $product_base -> product_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input class="btn btn-primary" type="submit" value='run' >
                            </form>
                            <br>
                            
                        </div>
                    </div>
                </div>
            <div class="panel panel-default">
                    <div class="panel-heading">月次レポート</div>
                    <div class="panel-body">
                        <div class="col-md-12">
                            <br>
                            <form method='post' action="{{ route('crawlermonthly')}}">
                                @csrf
                                <div class="col-md-4 wigth-left">
                                    <select class="form-control" name="product" >
                                        <option value=""> -- </option>
                                        @foreach($product_bases as $product_base)
                                         <option value="{{ $product_base -> id }}">{{ $product_base -> product_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input class="btn btn-primary" type="submit" value='run' >
                            </form>
                            <br>
                            
                        </div>
                    </div>
                </div>
             </div>
       </div>
@endsection
