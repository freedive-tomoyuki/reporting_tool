@extends('layouts.appnew')

@section('content')
    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header">結果一覧</h1>

        @if( request()->path() == 'daily_result' )<h3>日次レポート(案件別) </h3> @endif
        @if( request()->path() == 'daily_result_site' )<h3>日次レポート(サイト別) </h3> @endif
        @if( request()->path() == 'monthly_result' )<h3>月別レポート </h3> @endif

<!--         <div class="row">
          <div class="col-lg-12">
            <a href="/daily_result">
              <button class="btn btn-md 
              @if( request()->path() == 'daily_result' )
                btn-primary
              @else 
                btn-default
              @endif
              ">日次（案件別）</button>
            </a>
            <a href="/daily_result_site">
              <button class="btn btn-md 
              @if( request()->path() == 'daily_result_site' )
                btn-primary
              @else 
                btn-default
              @endif
              ">サイト別</button>
            </a>
            <a href="/monthly_result">
              <button class="btn btn-md 
              @if( request()->path() == 'monthly_result' )
                btn-primary
              @else 
                btn-default
              @endif
              ">月次</button>
            </a>
          </div>
        </div> -->
        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-6">
              <form role="form" action="/{{ request()->path() }}" method="post" class="form-horizontal">
                @csrf
                  @if( request()->path() != 'admin/monthly_result' )
                  <div class="form-group">
                    <label>ASP</label>
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
                  </div>
                  @endif
                  @if( request()->path() == 'admin/monthly_result' || request()->path() == 'admin/monthly_result_site' )
                  <div class="form-group">
                    <label>Month</label>
                      <input id="month" type="month" name="month" class="form-control" value="{{ old('month') }}">
                  </div>
                  @else
                  <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="searchdate_start" class="datepicker form-control" id="datepicker_start" max='{{ date("Y-m-d") }}' value="{{ old('searchdate_start') }}"placeholder="From">
                    ~<input type="date" name="searchdate_end" class="datepicker form-control" id="datepicker_end" max='{{ date("Y-m-d") }}' value="{{ old('searchdate_end') }}" placeholder="to">
                  </div>
                  @endif


                  <div class="form-group">
                    <label>Product</label>
                    <select class="form-control" name="product" >
                                <option value=""> -- </option>
                                @foreach($product_bases as $product_base)
                                  <option value="{{ $product_base -> id }}"
                                    @if( old('product') == $product_base->id  )
                                      selected
                                    @endif

                                    >{{ $product_base -> product_name }}</option>
                                @endforeach
                    </select>
                  </div>

                  <button type="submit" class="btn btn-primary">検索</button>
                  
                </div>
              
            </div>
          </div>


      </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">検索条件
              @if( request()->path() == 'admin/monthly_result' )
                
                  <button class="btn btn-md btn-primary">
                  <a href="/dailycal" target="_blank" >月次：手動実行</a></button>
                
              @endif
              </div>
                  <div class="panel-body">
                    <div class="form-group">
                        <label>ASP</label>
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
                    @if( request()->path() == 'admin/monthly_result' || request()->path() == 'admin/monthly_result_site' )
                    <div class="form-group">
                        <label>Month</label>
                        @if(old('month'))
                        {{ old('month') }}
                        @else
                         {{ date("Y-m") }}　
                         <!-- 期間の指定がございません -->
                        @endif
                    </div>
                    @else
                    <div class="form-group">
                        <label>Date</label>
                        @if(old('searchdate_start') || old('searchdate_end'))
                        {{ old('searchdate_start') }}
                        〜
                        {{ old('searchdate_end')}}
                        @else
                         期間の指定がございません
                        @endif
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Product</label>
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
