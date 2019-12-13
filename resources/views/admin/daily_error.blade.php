@extends('layouts.admin')

@section('content')
<div class="row">
       <ol class="breadcrumb">
          <li>結果一覧</li>
          <li class="active">日次レポート（案件別）</li>
       </ol>
    <div class="col-lg-12">
        <h1 class="page-header text-center">結果一覧</h1>
        @php $product_id = (old('product'))? old('product'):'3' @endphp
        @if( strpos(request()->path(),'daily_result') !== false )
          @if( strpos(request()->path(),'site') !== false )
            <h3>日次(サイト別) 
              <a class="btn btn-info pull-right" href="{{ url('admin/daily/site/edit/'.$product_id) }}">編集</a> 
            </h3> 
          @else
            <h3>日次(案件別) 
              <a class="btn btn-info pull-right" href="{{ url('admin/daily/edit/'.$product_id) }}">編集</a>
            </h3> 
          @endif
        @endif
        @if( strpos(request()->path(),'monthly_result') !== false )
          @if( strpos(request()->path(),'site') !== false )
            <h3>月別(サイト別) 
              <a class="btn btn-info pull-right" href="{{ url('admin/monthly/site/edit/'.$product_id) }}">編集</a>
            </h3> 
          @else
            <h3>月別(案件別) 
              <a class="btn btn-info pull-right" href="{{ url('admin/monthly/edit/'.$product_id) }}">編集</a>
            </h3> 
          @endif
        @endif
        <div class="panel panel-default ">

          <div class="panel-heading text-center">検索する</div>
          <div class="panel-body">
          <form role="form" action="{{ url(request()->path()) }}" method="post" >
                @csrf
              <div class="col-md-9 col-md-offset-1">
                <div class="col-md-12">
                  @if( strpos(request()->path(),'monthly_result_site') !== false ||
                         strpos(request()->path(),'daily') !== false )
                   <div class="form-group col-md-4">
                      <label class="control-label">ASP</label>
                      <div>
                         <select class="form-control" name="asp_id" >
                            <option value=""> すべてのASP </option>
                            @foreach($asps as $asp)
                            <option value="{{ $asp -> id }}"
                            @if( old('asp_id') == $asp->id  )
                            selected
                            @endif
                            >{{ $asp -> name }}</option>
                            @endforeach
                         </select>
                      </div>
                   </div>
                  @endif

                  @if( strpos(request()->path(),'daily') !== false )
                   <div class="form-group col-md-8 ">
                    <label class="center-block">Date</label>
                       <input type="date" name="searchdate_start" class="datepicker form-control date-style" id="datepicker_start" max='{{ date("Y-m-d",strtotime('-1 day')) }}' value=@if( old('searchdate_start')) 
                         {{ old('searchdate_start') }}
                         @else
                         {{ date('Y-m-01',strtotime('-1 day')) }} 
                         @endif>

                        <input type="date" name="searchdate_end" class="datepicker form-control date-style" id="datepicker_end" max='{{ date("Y-m-d",strtotime('-1 day')) }}' value=@if( old('searchdate_start')) 
                         {{ old('searchdate_end') }}
                         @else
                         {{ date('Y-m-d',strtotime('-1 day')) }} 
                         @endif>
                   </div>
                  @else
                   <div class="form-group col-md-6">
                        <label class="control-label">Month</label>
                        <div>
                           <input id="month" type="month" name="month" class="form-control" value=@if( old('month')) 
                          {{ old('month') }}
                        @else
                          {{ date('Y-m',strtotime('-1 day')) }}
                        @endif>
                        </div>
                    </div>
                  @endif


                  @if( strpos(request()->path(),'monthly_result_site') === false &&
                         strpos(request()->path(),'daily') === false )
                    <div class="form-group col-md-6">
                      <label class="control-label">Product</label>
                         <select class="form-control" name="product" >
                                  <option value=""> -- </option>
                                  @foreach($product_bases as $product_base)
                                    <option value="{{ $product_base->id }}"
                                      @if( old('product'))
                                        @if( old('product') == $product_base->id )
                                          selected
                                        @endif
                                      @else
                                        @if( $product_base->id == 3 )
                                          selected
                                        @endif
                                      @endif

                                      >{{ $product_base->product_name }}</option>
                                  @endforeach
                        </select>
                    </div>
                  @endif
                </div>
                  @if( strpos(request()->path(),'monthly_result_site') !== false ||
                         strpos(request()->path(),'daily') !== false )
                <div class="form-group col-md-12">
                    <label class="control-label col-md-12">Product</label>
                    <div class="col-md-12">
                       <select class="form-control" name="product" >
                          <option value=""> -- </option>
                          @foreach($product_bases as $product_base)
                          <option value="{{ $product_base -> id }}"
                          @if( old('product'))
                          @if( old('product') == $product_base->id  )
                          selected
                          @endif
                          @else
                          @if( $product_base->id == 3 )
                          selected
                          @endif
                          @endif
                          >{{ $product_base -> product_name }}</option>
                          @endforeach
                       </select>
                    </div>
                </div>
                @endif

                <div class="form-group col-md-12 col-sm-12 col-xs-12 col-lg-12">
                 <button type="submit" class="btn btn-primary col-lg-10 col-lg-offset-1 col-md-10 col-md-offset-1 col-sm-10 col-sm-offset-1 col-xs-10 col-xs-offset-1"><i class='fas fa-search'></i> 検索</button>
                </div>
              </div>
            </form>
          </div>
    　　</div>
    </div>
</div>
<!--検索条件を表示する-->
<div class="row">
    <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading text-center">現在の検索条件を表示する

              </div>
              <div class="panel-body">
              <div class="col-md-9 col-md-offset-1">
                <div class="col-md-12">
                  @if( strpos(request()->path(),'monthly_result_site') !== false ||
                         strpos(request()->path(),'daily') !== false )
                   <div class="form-group col-md-6">
                      <label class="control-label">ASP</label>
                      <div>
                        @if( old('asp_id') )
                            @foreach($asps as $asp)
                                @if( old('asp_id') == $asp->id  )
                                    {{ $asp -> name }}
                                @endif
                            @endforeach
                        @else
                         ASPの指定がございません
                        @endif
                      </div>
                   </div>
                  @endif

                  @if( strpos(request()->path(),'daily') !== false )
                   <div class="form-group col-md-6 ">
                    <label class="center-block">Date</label>
                        @if(old('searchdate_start') || old('searchdate_end'))
                        {{ old('searchdate_start') }}
                        〜
                        {{ old('searchdate_end')}}
                        @else
                         期間の指定がございません
                        @endif
                   </div>
                  @else
                   <div class="form-group col-md-6">
                        <label class="control-label">Month</label>
                        <div>
                         @if(old('month'))
                          {{ old('month') }}
                          @else
                           {{ date("Y-m") }}　
                           <!-- 期間の指定がございません -->
                          @endif
                        </div>
                    </div>
                  @endif


                  @if( strpos(request()->path(),'monthly_result_site') === false &&
                         strpos(request()->path(),'daily') === false )
                    <div class="form-group col-md-6">
                      <label class="control-label">Product</label>
                      <div>
                        @if( old('product')  )
                            @foreach($product_bases as $product_base)
                                @if( old('product') == $product_base->id  )
                                    {{ $product_base -> product_name }}
                                @endif
                            @endforeach
                        @else
                         案件の指定がございません
                        @endif
                      </div>
                    </div>
                  @endif
                </div>
                  @if( strpos(request()->path(),'monthly_result_site') !== false ||
                         strpos(request()->path(),'daily') !== false )
                <div class="form-group col-md-12">
                    <label class="control-label col-md-12">Product</label>
                    <div class="col-md-12">
                        @if( old('product')  )
                            @foreach($product_bases as $product_base)
                                @if( old('product') == $product_base->id  )
                                    {{ $product_base -> product_name }}
                                @endif
                            @endforeach
                        @else
                         案件の指定がございません
                        @endif
                    </div>
                </div>
                @endif
              </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
        <div class="col-md-12">
          <div class="alert bg-danger" role="alert"><em class="fa fa-lg fa-warning">&nbsp;</em>
          検索結果が見つかりません。 <a href="#" class="pull-right"><em class="fa fa-lg fa-close"></em></a></div>
        </div>
</div>


@endsection
