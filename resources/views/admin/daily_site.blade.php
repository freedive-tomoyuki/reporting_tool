@extends('layouts.admin')

@section('content')
<div class="row">
   <ol class="breadcrumb">
      <li>結果一覧</li>
      <li class="active">日次レポート(サイト別)</li>
   </ol>
   <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
      <h3>日次レポート(サイト別)
        @php $product_id = (old('product'))? old('product'):'3' @endphp
        <a class="btn btn-info pull-right" href="{{ url('admin/daily/site/edit/'.$product_id) }}">編集</a> 
      </h3>
      <div class="panel panel-default">
         <div class="panel-heading text-center">検索する</div>
         <form role="form" action="{{ url('admin/daily_result_site')}}" method="post" >
            @csrf
            <div class="panel-body ">
              <div class="col-md-9 col-md-offset-1">
                <div class="col-md-12">
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
                  <div class="form-group col-md-8">
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
                </div>
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
         <div class="panel-heading text-center">現在の検索結果を表示する</div>
         <form role="form" action="{{ url('admin/daily_result_site')}}" method="post" >
            @csrf
            <div class="panel-body ">
              <div class="col-md-9 col-md-offset-1">
                <div class="col-md-12">
                  <div class="form-group col-md-6">
                      <label class="control-label">ASP</label>
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
                  <div class="form-group col-md-6">
                    <label class="center-block">Date</label>
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
                <div class="form-group col-md-12">
                    <label class="control-label col-md-12">Product</label>
                    <div class="col-md-12">
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
                </div>
                <div class="form-group col-md-12 col-sm-12 col-xs-12 col-lg-12">
                   <button type="submit" class="btn btn-success col-lg-10 col-lg-offset-1 col-md-10 col-md-offset-1 col-sm-10 col-sm-offset-1 col-xs-10 col-xs-offset-1"><i class='fas fa-file-download'></i>
                    <?php
                      $s_date = (old("searchdate_start"))? old("searchdate_start"): date('Y-m-01',strtotime('-1 day'));
                      $e_date = (old("searchdate_end"))? old("searchdate_end"):date("Y-m-d",strtotime('-1 day'));
                      if( old('product')){
                          $product_base = old('product') ;
                      }else{
                          $product_base =  3 ;
                      }
                      
                    ?>
                    <a href='/admin/csv_site/{{ $product_base }}/{{ urlencode($s_date) }}/{{  urlencode($e_date) }}' class='d-block'>
                      CSV
                    </a>
                  </button>
                </div>
              </div>
         </div>
         </form>
      </div>
   </div>
</div>


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
                          <th class="th-sm" style="max-width: 50px;" >Media ID</th>
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
                        <td>{{ number_format($product->imp) }}</td>
                        <td>{{ number_format($product->ctr) }}</td>
                        <td>{{ number_format($product->click) }}</td>
                        <td>{{ number_format($product->cvr) }}</td>
                        <td>{{ number_format($product->cv) }}</td>
                        <td>{{ number_format($product->estimate_cv)  }}</td>
                        <td>{{ number_format($product->cost) }}</td>
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

@endsection
