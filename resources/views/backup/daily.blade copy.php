@extends('layouts.app')

@section('content')
    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header">結果一覧</h1>
        <h3>日次レポート（案件別）</h3> 

        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-12">
              <form role="form" action="/daily_result" method="post" class="form-horizontal">
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
                      {{ date('Y-m-d',strtotime('-1 day')) }} 
                    @endif>
                     〜<input type="date" name="searchdate_end" class="datepicker form-control" id="datepicker_end" max='{{ date("Y-m-d") }}' value=@if( old('searchdate_start')) 
                      {{ old('searchdate_end') }}
                    @else
                      {{ date('Y-m-d',strtotime('-1 day')) }} 
                    @endif>
                  </div>
<!--
                  <div class="form-group  form-inline" style="padding:10px;">
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
                  </form>
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
                <a href='/csv/{{ $products[0]->id }}{{ (old("searchdate_start"))?"/s_".old("searchdate_start"): "/s_".date("Y-m-d") }}{{ (old("searchdate_end"))?"/e_".old("searchdate_end"):"/e_".date("Y-m-d") }}' class='d-block text-info'>
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
                         {{ date('Y/m/d',strtotime('-1 day'))}}〜{{ date('Y/m/d',strtotime('-1 day'))}}
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
            <div class="panel-heading">グラフ</div>
            <div class="panel-body">
            		<div id="line_top_x" class="col-lg-6"></div>
                <div id="line_top_y" class="col-lg-6"></div>
            </div>
          </div>
      </div>
    </div>
		<!--/.row-->

    <div class="col-md-12">
        <div class="table-responsive">
                <table id="dtBasicExample" class="table table-striped table-bordered table-hover table-sm" cellspacing="0" width="100%">
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
                    
                    @foreach($products as $product)
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->date }}</td>
                        <td>{{ $product->imp }}</td>
                        <td>{{ $product->ctr }}</td>
                        <td>{{ $product->click }}</td>
                        <td>{{ $product->cvr }}</td>
                        <td>{{ $product->cv }}</td>
                        <td>{{ $product->estimate_cv  }}</td>
                        <td>{{ $product->active }}</td>
                        <td>{{ $product->partnership }}</td>
                        <td>{{ $product->cost }}</td>
                        <td>{{ $product->cpa }}</td>
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
                        <td>{{ $t->total_imp }}</td>
                        <td>{{

                        sprintf('%.2f',( $t->total_click / $t->total_imp ) *100)

                        }}</td>
                        <td>{{ $t->total_click }}</td>
                        <td>{{

                        sprintf('%.2f',( $t->total_cv / $t->total_click ) *100)

                        }}</td>
                        <td>{{ $t->total_cv }}</td>
                        <td>{{ $t->total_estimate_cv }}</td>
                        <td>{{ $t->total_active }}</td>
                        <td>{{ $t->total_partnership }}</td>
                        <td>{{ $t->total_price }}</td>
                        <td> -- </td>
                  </tr>
                  @endforeach
                </tfoot>

    </div>
</div>
<script>
      google.charts.load('current', {'packages':['line']});
      google.charts.setOnLoadCallback(drawChart);

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

      data.addColumn('string', 'Day');

      array_asp.forEach(function(element){
        data.addColumn('number', element );
        console.log(element);
      });

      data.addRows(array_ranking2);

      var options = {
        chart: {
          title: 'ASP別　日次CV推移'
        },
        height: 500,
        
      };

      var chart = new google.charts.Line(document.getElementById('line_top_x'));

      chart.draw(data, google.charts.Line.convertOptions(options));
    }

    google.charts.load('current', {'packages':['line']});
    google.charts.setOnLoadCallback(drawChart_total);

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
        chart: {
          title: 'CV数xクリック数xインプレッション数',
        },

        height: 500,

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
