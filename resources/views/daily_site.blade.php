@extends('layouts.sponsor')

@section('content')
    <div class="row">
      <ol class="breadcrumb">
        <li>結果一覧</li>
        <li class="active">月次レポート（案件別）</li>
      </ol>
      <div class="col-lg-12">
        <h3>日次レポート(サイト別) </h3> 

        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-6">
              <form role="form" action="{{ url('daily_result_site')}}" method="post" class="form-horizontal">
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
                <div class="form-group form-inline" >
                  <label class="col-sm-2 control-label">Date</label>
                  <div class="col-sm-10">
                    <input type="date" name="searchdate_start" class="datepicker form-control" id="datepicker_start" max='{{ date("Y-m-d",strtotime('-1 day')) }}' value=@if( old('searchdate_start')) 
                      {{ old('searchdate_start') }}
                    @else
                      {{ date('Y-m-01',strtotime('-1 day')) }} 
                    @endif>
                     〜 <input type="date" name="searchdate_end" class="datepicker form-control form-inline" id="datepicker_end" max='{{ date("Y-m-d",strtotime('-1 day')) }}' value=@if( old('searchdate_start')) 
                      {{ old('searchdate_end') }}
                    @else
                      {{ date('Y-m-d',strtotime('-1 day')) }} 
                    @endif>
                  </div>
                </div>

                  <button type="submit" class="btn btn-primary">検索</button>
                  
                </div>
              
            </div>
          </div>
          @if (count($errors) > 0)
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
          @endif
      </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">検索条件
              @if(!$products->isEmpty())
                <button class="btn btn-success btn-md pull-right">
                <?php
                  $s_date = (old("searchdate_start"))? old("searchdate_start"): date("Y-m-d",strtotime('-1 day'));
                  $e_date = (old("searchdate_end"))? old("searchdate_end"):date("Y-m-d",strtotime('-1 day'));
                  $product_base = (old('product'))? old('product') : 3 ;
                ?>
                <a href="{{ url('csv_site/'. $product_base .'/'. urlencode($s_date) .'/'.  urlencode($e_date)) }}" class='d-block text-info'>
                  ＣＳＶ
                </a>
                </button>
              @endif
              </div>
                  <div class="panel-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">ASP</label>
                      <div class="col-sm-10">
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
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Date</label>
                      <div class="col-sm-10">
                              <p class="form-control-static">
                              @if(old('searchdate_start') || old('searchdate_end'))
                              {{ old('searchdate_start') }}
                              〜
                              {{ old('searchdate_end')}}
                              @else
                               {{ date('Y-m-01',strtotime('-1 day')) }} 〜{{ date('Y/m/d',strtotime('-1 day'))}}
                              @endif
                              </p>
                      </div>
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
            <div class="panel-body">
              <div id="piechart" style="width: 100%; height: 300px;"></div>
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
                          <th class="th-sm media-id-style">Media ID</th>
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
