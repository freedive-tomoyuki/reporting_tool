@extends('layouts.app')

@section('content')
    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header">結果一覧</h1>

        <h3>日次レポート(サイト別) </h3> 
<!--         <div class="row">
          <div class="col-lg-12">
            <a href="/daily_result">
              <button class="btn btn-md btn-default">日次（案件別）</button>
            </a>
            <a href="/daily_result_site">
              <button class="btn btn-md btn-primary">サイト別</button>
            </a>
            <a href="/monthly_result">
              <button class="btn btn-md btn-default">月次</button>
            </a>
          </div>
        </div> -->
        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-12">
              <form role="form" action="/daily_result_site" method="post" class="form-horizontal">
                @csrf
                  <div class="form-group form-inline " style="padding:10px;">
                    <label>ASP</label>
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
                  <div class="form-group form-inline " style="padding:10px;">
                    <label>Date</label>
                    <input type="date" name="searchdate_start" class="datepicker form-control" id="datepicker_start" max='{{ date("Y-m-d") }}' value=@if( old('searchdate_start')) 
                      {{ old('searchdate_start') }}
                    @else
                      {{ date('Y-m-01') }} 
                    @endif>
                    ~<input type="date" name="searchdate_end" class="datepicker form-control form-inline" id="datepicker_end" max='{{ date("Y-m-d") }}' value=@if( old('searchdate_start')) 
                      {{ old('searchdate_end') }}
                    @else
                      {{ date('Y-m-d',strtotime('-1 day')) }} 
                    @endif>
                  </div>
<!--
                  <div class="form-group form-inline " style="padding:10px;">
                    <label>Product</label>
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
-->
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
                <button class="btn btn-success btn-md pull-right">
                <a href='/csv_site/{{ old("product") }}{{ (old("searchdate_start"))?"/s_".old("searchdate_start"):"" }}{{ (old("searchdate_end"))?"/e_".old("searchdate_end"):"" }}' class='d-block text-info'>
                  ＣＳＶ
                </a>
                </button>
                
              </div>
                  <div class="panel-body">
                    <div class="form-group">
                        <label>ASP</label>
                        @if( old('asp_id')  )
                            @foreach($asps as $asp)
                                @if( old('asp_id') == $asp->id  )
                                    {{ $asp -> name }}
                                @endif
                            @endforeach
                        @else
                         すべてのASP
                        @endif
                        
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        @if(old('searchdate_start') || old('searchdate_end'))
                        {{ old('searchdate_start') }}
                        〜
                        {{ old('searchdate_end')}}
                        @else
                         {{ date('Y/m/01')}}〜{{ date('Y/m/d',strtotime('-1 day'))}}
                        @endif
                        
                    </div>
<!--
                    <div class="form-group">
                        <label>Product</label>
                        @foreach($product_bases as $product_base)
                        
                          @if( old('product')  )
                                @if( old('product') == $product_base->id  )
                                    {{ $product_base -> product_name }}
                                @endif
                          @else
                                @if( $product_base->id ==1 )
                                    {{ $product_base -> product_name }}
                                @endif
                          @endif
                        @endforeach
                    </div>
-->
                  </div>
            </div>
        </div>
    </div>


<!--グラフ-->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-body">
            <div class="panel-body">
              <div id="piechart" style="width: 900px; height: 500px;"></div>
            </div>
          </div>
        </div>
      </div>
    </div><!--/.row-->

    <div class="col-md-12">
        <div class="table-responsive">
                <table id="dtBasicExample" class="table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead>
                      <tr>
                          <th class="th-sm">No</th>
                          <th class="th-sm">ASP</th>
                          <th class="th-sm">Date</th>
                          <th class="th-sm">Media ID</th>
                          <th class="th-sm">サイト名</th>
                          <th class="th-sm">Imp</th>
                          <th class="th-sm">CTR</th>
                          <th class="th-sm">Click</th>
                          <th class="th-sm">CVR</th>
                          <th class="th-sm">CV</th>
                          <th class="th-sm">予想CV</th>
                          <th class="th-sm">FDグロス</th>
                          
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
                        <td>{{ $product->date }}</td>
                        <td>{{ $product->media_id }}</td>
                        <td>{{ $product->site_name }}</td>
                        <td>{{ $product->imp }}</td>
                        <td>{{ $product->ctr }}</td>
                        <td>{{ $product->click }}</td>
                        <td>{{ $product->cvr }}</td>
                        <td>{{ $product->cv }}</td>
                        <td>{{ $product->estimate_cv  }}</td>
                        <th>{{ $product->cost }}</th>
                        <th><?php
                          echo $val;
                        ?></th>
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

        array_ranking2 = [['Task', 'Hours per Day']];
        array_ranking1.forEach(function(element){
              array_ranking2.push([ element["site_name"], parseInt(element["total_cv"], 10) ]);
        });
        console.log(array_ranking2) ;
        var data = google.visualization.arrayToDataTable(array_ranking2);

        var options = {
          title: 'TOP１０媒体別　CV比率'
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

@endsection
