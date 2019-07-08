@extends('layouts.app')

@section('content')
    <div class="row">
      <ol class="breadcrumb">
        <li>結果一覧</li>
        <li class="active">月次レポート(サイト別)</li>
      </ol>
      <div class="col-lg-12">

        <h3>月次レポート(サイト別) </h3> 

        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-6">
              <form role="form" action="/monthly_result_site" method="post" class="form-horizontal">
                @csrf
                <div class="form-group">
                  <label class="col-sm-2 control-label">ASP</label>
                  <div class="col-sm-10">
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
                <div class="form-group">
                  <label class="col-sm-2 control-label">Month</label>
                  <div class="col-sm-10">
                    <input id="month" type="month" name="month" class="form-control" value=@if( old('month')) 
                      {{ old('month') }}
                    @else
                      {{ date('Y-m',strtotime('-1 day')) }}
                    @endif>
                  </div>
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
                <button class="btn btn-success btn-md pull-right">
                <a href='/csv_site/{{ $products[0]->id }}/{{ $products[0]->date }}' class='d-block text-info'>
                  ＣＳＶ
                </a>
                </button>
                
              </div>
                  <div class="panel-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">ASP</label>
                      <div class="col-sm-10">
                              <p class="form-control-static">
                                @if( old('asps')  )
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
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Month</label>
                      <div class="col-sm-10">
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
                          </div>


        </div>
    </div>


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
                          
                          <th class="th-sm">Media ID</th>
                          <th class="th-sm">サイト名</th>
                          <th class="th-sm">Imp</th>
                          <th class="th-sm">CTR</th>
                          <th class="th-sm">Click</th>
                          <th class="th-sm">CVR</th>
                          <th class="th-sm">CV</th>
                          <th class="th-sm">FDグロス</th>
                          <th class="th-sm">承認件数</th>
                          <th class="th-sm">承認成果報酬</th>
                          <th class="th-sm">承認率</br>(直近3ヶ月から算出)</th>
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
                        <td>{{ $product->media_id }}</td>
                        <td>{{ $product->site_name }}</td>
                        <td>{{ $product->imp }}</td>
                        <td>{{ $product->ctr }}</td>
                        <td>{{ $product->click }}</td>
                        <td>{{ $product->cvr }}</td>
                        <td>{{ $product->cv }}</td>
                        <td>{{ $product->cost }}</td>
                        <td>{{ $product->approval }}</td>
                        <td>{{ $product->approval_price }}</td>
                        <td>{{ $product->approval_rate }}%</td>
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


@endsection
