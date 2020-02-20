@extends('layouts.admin')

@section('content')
<div class="row">
   <ol class="breadcrumb">
      <li>結果一覧</li>
      <li class="active">月次レポート(サイト別)</li>
   </ol>
   <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
      <h3>月次レポート(サイト別) 
        @php $product_id = (old('product'))? old('product'):'3' @endphp
        <a class="btn btn-info pull-right" href="{{ url('admin/monthly/site/edit/'.$product_id) }}">編集</a> 
      </h3>
      <div class="panel panel-default">
         <div class="panel-heading text-center">検索する</div>
         <form role="form" action="{{ url('admin/monthly_result_site')}}" method="post" >
            @csrf
            <div class="panel-body ">
              <div class="col-md-7 col-md-offset-2">
                  <div class="form-group col-md-6">
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
                  <div class="form-group col-md-6">
                    <label class="control-label">対象月</label>
                      <input id="month" type="month" name="month" class="form-control" value=@if( old('month')) 
                      {{ old('month') }}
                    @else
                      {{ date('Y-m',strtotime('-1 day')) }}
                    @endif>
                  </div>
                  <div class="form-group col-md-12">
                    <label class="control-label">広告主</label>
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
                  <div class="form-group col-md-12 col-sm-12 col-xs-12 col-lg-12">
                   <button type="submit" class="btn btn-primary col-lg-10 col-lg-offset-1 col-md-10 col-md-offset-1 col-sm-10 col-sm-offset-1 col-xs-10 col-xs-offset-1"><i class='fas fa-search'></i> 検索</button>
                  </div>
              </div>
         </div>
         </form>

      </div>
      @if (count($errors) > 0)
         <div class="alert alert-danger">
           @foreach ($errors->all() as $error)
             {{ $error }}
           @endforeach
         </div>
      @endif
    </div>
</div>

<div class="row">
   <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
      <div class="panel panel-default">
         <div class="panel-heading text-center">現在の検索条件を表示する</div>
         <form role="form" action="{{ url('admin/monthly_result_site')}}" method="post" >
            @csrf
            <div class="panel-body ">
              <div class="col-md-7 col-md-offset-2">
                  <div class="form-group col-md-6">
                      <label class="control-label">ASP</label>
                      <div>
                              <p class="form-control-static">
                                @if( old('asp_id')  )
                                    @foreach($asps as $asp)
                                        @if( old('asp_id') == $asp->id  )
                                            {{ $asp -> name }}
                                        @endif
                                    @endforeach
                                @else
                                 すべてのASP
                                @endif
                              </p>
                      </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label class="control-label">対象月</label>
                              <p class="form-control-static">
                                @if(old('month'))
                                {{ old('month') }}
                                @else
                                 {{ date("Y-m",strtotime('-1 day')) }}　
                                @endif
                                @if(old('month') == date("Y-m",strtotime('-1 day'))|| !old('month') )
                                {{"前日分までのデータ"}}
                                @endif
                              </p>
                  </div>
                  <div class="form-group col-md-12">
                    <label class="control-label">広告主</label>
                              <p class="form-control-static">
                                @foreach($product_bases as $product_base)
                                
                                  @if( old('product')  )
                                        @if( old('product') == $product_base->id  )
                                            {{ $product_base -> product_name }}
                                        @endif
                                  @else
                                        @if( $product_base->id ==3 )
                                            {{ $product_base -> product_name }}
                                        @endif
                                  @endif
                                @endforeach
                              </p>

                  </div>
                  @if(!$products->isEmpty())
                      <div class="form-group col-md-12 col-sm-12 col-xs-12 col-lg-12">
                
                        <button type="button" class="btn btn-success col-lg-10 col-lg-offset-1 col-md-10 col-md-offset-1 col-sm-10 col-sm-offset-1 col-xs-10 col-xs-offset-1"><i class='fas fa-file-download'></i>
                          <?php
                            $month = (old("month"))? old("month"): date('Y-m',strtotime('-1 day'));
                            $product_base = ( old('product'))? old('product') : 3; 
                          ?>
                          <a href="{{ url('admin/monthly/site/csv?p='. $product_base .'&month='. $month )}}" class='d-block'>
                            ＣＳＶ
                          </a>
                        </button>
                      </div>
                  @endif
              </div>
         </div>
         </form>

      </div>
   </div>
</div>


@if(!$products->isEmpty())

<!--グラフ-->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">TOP１０媒体別　CV比率</div>
            <div class="panel-body">
                <div id="piechart" style="width: 100%; height: 300px;"></div>
            </div>
        </div>
      </div>
    </div>

    <div class="col-md-12">
        <div class="table-responsive">
                <table id="dtBasicExample" class="table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead>
                      <tr>
                          <th class="th-sm">No</th>
                          <th class="th-sm">ASP</th>
                          <th class="th-sm">案件名</th>
                          
                          <th class="th-sm" style="max-width: 50px;" >Media ID</th>
                          <th class="th-sm">サイト名</th>
                          <th class="th-sm">Imp</th>
                          <th class="th-sm">CTR</th>
                          <th class="th-sm">Click</th>
                          <th class="th-sm">CVR</th>
                          <th class="th-sm">CV</th>
                          <th class="th-sm">FDグロス</th>
                          <th class="th-sm">承認件数</th>
                          <th class="th-sm">承認成果報酬</th>
                          <th class="th-sm">承認率</br><div>(直近3ヶ月から算出)</div></th>
                          <th class="th-sm">CPA</th>
                      </tr>
                </thead>
                <tbody>
                    <?php 
                      $i = 1; 
                      
                    ?>
                    
                    @foreach($products as $product)
                    <?php 
                      $val = sprintf('%.7f', $product->cpa);
                      $val = preg_replace('/\.?0+$/', '', $val);
                      //$val = ereg_replace("\.$", '', $val);
                    ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->product }}</td>
                        <td class="media-id-style">{{ $product->media_id }}</td>
                        <td>{{ $product->site_name }}</td>
                        <td>{{ number_format($product->imp) }}</td>
                        <td>{{ number_format($product->ctr) }}</td>
                        <td>{{ number_format($product->click) }}</td>
                        <td>{{ number_format($product->cvr) }}</td>
                        <td>{{ number_format($product->cv) }}</td>
                        <td>{{ number_format($product->cost) }}</td>
                        <td>{{ number_format($product->approval) }}</td>
                        <td>{{ number_format($product->approval_price) }}</td>
                        <td>{{ number_format($product->approval_rate) }}%</td>
                        <td><?php
                          echo $val;
                        ?></td>
                        <?php $i++; ?>
                    </tr>
                    @endforeach

                </tbody>
                
        </div>
    </div>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        
        var ranking = JSON.parse(escapeHtml('{{ $site_ranking }}'));
        
        console.log(ranking);
        //alert(typeof ranking) ;

        array_ranking = new Array();
        array_ranking1 = new Array();
        i = 0;

        ranking.forEach(function(element,i) {
          //console.log(element);
          array_ranking1[i] = new Array();

          for ( var key in element ) {
            var data = element[key];
            array_ranking1[i][key] = data;

            //console.log(array_ranking1);
            
          }
          i = i+1;
          //console.log(array_ranking1[i]);
        });
        console.log(array_ranking1);
/*        array_ranking = Object.entries(ranking).map(([key, value]) => ({[key]: value}));
        //array_ranking1 = Object.entries(ranking).map(([key, value]) => ({key, value}));
        console.log(array_ranking1);
        //alert(typeof array_ranking) ;

        //foreach (array_ranking as array_s){
        array_ranking.forEach(function(element) {
          array_ranking1 = Object.entries(element).map(([key, value]) => ({[key]: value}));

          console.log( array_ranking1[0] );
          
          console.log( Object.entries(array_ranking1));
          console.log( Object.entries(element));

          //console.log( Object.entries(element).map(([key, value]) => ({key, value})));
          //console.log(Object.entries(element[1][1]).map(([key, value]) => ({[key]: value}))) ;
          //console.log(element) ;
        });*/
        array_ranking2 = [['Task', 'Hours per Day']];
        array_ranking1.forEach(function(element){
              array_ranking2.push([ element["site_name"], parseInt(element["cv"], 10) ]);
        });
        console.log(array_ranking2) ;
        var data = google.visualization.arrayToDataTable(array_ranking2);

        var options = {
          height: 300,
          legend: { position: 'bottom'} 
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }
      function escapeHtml(str){
        str = str.replace(/&amp;/g, '&');
        str = str.replace(/&gt;/g, '>');
        str = str.replace(/&lt;/g, '<');
        str = str.replace(/&quot;/g, '"');
        str = str.replace(/&#x27;/g, "'");
        str = str.replace(/&#x60;/g, '`');
        return str;
      }
    </script>
@else
   <div class="row">
         <div class="col-md-12">
            <div class="alert bg-danger" role="alert"><em class="fa fa-lg fa-warning">&nbsp;</em>
            検索結果が見つかりません。 <a href="#" class="pull-right"><em class="fa fa-lg fa-close"></em></a></div>
         </div>
   </div>
@endif

@endsection
