@extends('layouts.admin')

@section('content')

<div class="row">
   <ol class="breadcrumb">
      <li>結果一覧</li>
      <li class="active">日次レポート（案件別）</li>
   </ol>
   <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
      <h3>日次レポート（案件別）
         
         @php $product_id = (old('product'))? old('product'):'3' @endphp
         <a class="btn btn-info pull-right" href="{{ url('admin/daily/edit/'.$product_id) }}">編集</a> 
      </h3>
      <!-- <div class="d-inline text-right"><button class="btn btn-info">編集</button></div> -->
      <div class="panel panel-default">
         <div class="panel-heading text-center">検索する</div>
         <form role="form" action="{{ url('admin/daily_result')}}" method="post" >
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


<!--現在の検索条件を表示する-->
<div class="row">
   <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
      <div class="panel panel-default">
         <div class="panel-heading text-center">現在の検索条件を表示する</div>
         <form role="form" action="{{ url('admin/daily_result')}}" method="post" >
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
                   <div class="form-group col-md-6 ">
                    <label class="center-block">Date</label>
                      <p class="form-control-static">
                         @if(old('searchdate_start') || old('searchdate_end'))
                         {{ old('searchdate_start') }}
                         〜
                         {{ old('searchdate_end')}}
                         @else
                         {{ date('Y/m/01',strtotime('-1 day'))}}〜{{ date('Y/m/d',strtotime('-1 day'))}}
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
                <button type="button" class="btn btn-success btn-md col-lg-10 col-lg-offset-1 col-md-10 col-md-offset-1 col-sm-10 col-sm-offset-1 col-xs-10 col-xs-offset-1">
                  <?php
                   $s_date = (old("searchdate_start"))? old("searchdate_start"): date("Y-m-01",strtotime('-1 day'));
                   $e_date = (old("searchdate_end"))? old("searchdate_end"):date("Y-m-d",strtotime('-1 day'));
                   $asp = ( old('asp_id'))? old('asp_id') : '';
                   $product_base = ( old('product'))? old('product') : 3; 
                  //      $product_base = old('product') ;
                  //  }else{
                  //      $product_base =  3 ;
                  //  }
                  ?>
                <a href="{{ url('admin/daily/csv?p='. $product_base .'&s_date='. urlencode($s_date) .'&e_date='. urlencode($e_date).'&asp='. $asp) }}" class='d-block'>
                <i class="fas fa-file-download"></i> CSV
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
         <div class="panel-heading sp-small">ASP別　日次CV推移</div>
         <div class="panel-body table-responsive">
            <div id="line_top_x" class='linechart'></div>
         </div>
      </div>
   </div>
</div>
<div class="row">
   <div class="col-lg-12">
      <div class="panel panel-default">
         <div class="panel-heading sp-small">CV数xクリック数xインプレッション数</div>
         <div class="panel-body table-responsive">
            <div id="line_top_y" class='linechart'></div>
         </div>
      </div>
   </div>
</div>
<!--/.row-->
<div class="row">
   <div class="col-md-12 ">
        <div class="table-responsive">
                <table id="dtBasicExample" class="table table-striped table-bordered table-hover table-sm sp-wide-tabel" cellspacing="0" width="100%">
                  <thead>
                        <tr>
                            <th class="th-sm">No</th>
                            <th class="th-sm">ASP</th>
                            <th class="th-sm">Date</th>
                            <th class="th-sm">Imp</th>
                            <th class="th-sm">CTR</th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR</th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">予想CV</th>
                            <th class="th-sm">アクティブ数</th>
                            <th class="th-sm">提携数</th>
                            <th class="th-sm">FDグロス</th>
                            <th class="th-sm">CPA</th>
                        </tr>
                  </thead>
                <tbody>
                    <?php 
                      $i = 1; 
                     ?>
                      @foreach($daily_data as $d)
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $d->name }}</td>
                        <td>{{ $d->date }}</td>
                        <td>{{ number_format($d->imp) }}</td>
                        <td>{{ number_format($d->ctr) }}</td>
                        <td>{{ number_format($d->click) }}</td>
                        <td>{{ number_format($d->cvr) }}</td>
                        <td>{{ number_format($d->cv) }}</td>
                        <td>{{ number_format($d->estimate_cv)  }}</td>
                        <td>{{ number_format($d->active) }}</td>
                        <td>{{ number_format($d->partnership) }}</td>
                        <td>{{ number_format($d->cost) }}</td>
                        <td>{{ number_format($d->cpa) }}</td>
                        <?php $i++; ?>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                  @foreach($total as $t)
                  <tr>
                        <td></td>
                        <td>合計</td>
                        <td> -- </td>
                        <td>{{ number_format($t->total_imp) }}</td>
                        <td>{!! calc_percent($t->total_click , $t->total_imp) !!}</td>
                        <td>{{ number_format($t->total_click) }}</td>
                        <td>{!! calc_percent( $t->total_cv , $t->total_click ) !!}</td>
                        <td>{{ number_format($t->total_cv) }}</td>
                        <td>{{ number_format($t->total_estimate_cv) }}</td>
                        <td>{{ number_format($t->total_active) }}</td>
                        <td>{{ number_format($t->total_partnership) }}</td>
                        <td>{{ number_format($t->total_price) }}</td>
                        <td> -- </td>
                  </tr>
                  @endforeach
                </tfoot>

      </div>
  </div>
</div>
<script>
      //google.charts.load('current', {'packages':['line']});
      google.load("visualization", "1", {
                packages: ["line"]
            });
      google.charts.setOnLoadCallback(drawChart);
      //google.charts.load('current', {'packages':['line']});
      google.charts.setOnLoadCallback(drawChart_total);

    function drawChart() {

		var ranking = JSON.parse(escapeHtml('{{ $daily_ranking }}'));
            console.log(ranking);

        array_ranking1 = new Array();
        array_asp = new Array();

        i = 0;

        ranking.forEach(function(element,i) {
          //console.log(element);
          array_ranking1[i] = new Array();

          for ( var key in element ) {

            var data = element[key];

            if(key == 'date'){

            	var date = new Date(data);

            	var year = date.getFullYear();
      				var month = date.getMonth() + 1;
      				var day = date.getDate();

            	array_ranking1[i][key] = year +'-'+ month +'-'+ day;
            	

            }else{
            	array_ranking1[i][key] = parseInt(data, 10);

              if(array_asp.indexOf(key) < 0){
                array_asp.push(key);
              }
                      }
                  }
          i = i+1;
          //console.log(array_ranking1[i]);
        });
        console.log(array_ranking1);
        //array_ranking2 = [['day',  'A8', 'Accesstrade', 'Accesstrade', 'ValueCommerce','Afb']];
        array_ranking2 = new Array();
        element_data = new Array();

        array_ranking1.forEach(function(element){
          //console.log(element);
          var valuesOf = function(obj) {
            return Object.keys(obj).map(function (key) { return obj[key]; })
          }
          console.log(valuesOf(element));
          array_ranking2.push(valuesOf(element));

        });
		  console.log(array_ranking2);

      var data = new google.visualization.DataTable();

      data.addColumn('string', '');

      array_asp.forEach(function(element){
        data.addColumn('number', element );
        console.log(element);
      });

      data.addRows(array_ranking2);

      var options = {

        height: 300,
        legend: 'bottom',
          };

      var chart = new google.charts.Line(document.getElementById('line_top_x'));

      chart.draw(data, google.charts.Line.convertOptions(options));
    }



    function drawChart_total() {

      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Day');
      data.addColumn('number', 'インプレッション数');
      data.addColumn('number', 'クリック数');
      data.addColumn('number', 'CV数');
        date = new Date();
      date1 = date.getFullYear()+'-'+(date.getMonth() + 1)+'-'+(date.getDate()-1);
      date2 = date.getFullYear()+'-'+(date.getMonth() + 1)+'-'+date.getDate();
      

      element = [[date1,1000,200,5],[date2,950,30,2]];
      console.log(JSON.parse(escapeHtml('{{$total_chart}}')));

      data.addRows(JSON.parse(escapeHtml('{{$total_chart}}')));

      var options = {
        height: 300,
        legend: 'bottom',

      };

      var chart_total = new google.charts.Line(document.getElementById('line_top_y'));

      chart_total.draw(data, google.charts.Line.convertOptions(options));
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
