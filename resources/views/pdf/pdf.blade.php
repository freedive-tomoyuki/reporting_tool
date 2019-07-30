<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link href="{{ public_path('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/styles.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/addons/datatables.min.css')}}" rel="stylesheet">
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <title>全体成果　月間レポート</title>
    <style type="text/css">
        /*@font-face {
            font-family: ipag;
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: ipag;
            font-style: bold;
            font-weight: bold;
            src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
        }*/
        body{
            background:#ffffff;
        }
        .pie-chart {
            width: 1700px;
            height: 700px;
            margin: 0 auto;
        }
        h3.top {
            /*page-break-after: always;*/
            page-break-before: always;
        }
        h3 {
          border-bottom: solid 3px #000000;
          bottom: -3px;
          position: relative;
        }

    </style>
    <script>
        function init() {
            google.load("visualization", "1.1", {
                packages: ["corechart"],
                callback: 'drawChart'
            });
            google.load("visualization", "1.1", {
                packages: ["corechart"],
                callback: 'drawChart_total'
            });
            google.load("visualization", "1.1", {
                packages: ["corechart"],
                callback: 'drawChartImp'
            });
            google.load("visualization", "1.1", {
                packages: ["corechart"],
                callback: 'drawChartClick'
            });
            google.load("visualization", "1.1", {
                packages: ["corechart"],
                callback: 'drawChartCv'
            });
        }
/*        function drawCharts() {

            var data = google.visualization.arrayToDataTable([
                ['Task', 'Hours per Day'],
                ['Coding', 11],
                ['Eat', 1],
                ['Commute', 2],
                ['Looking for code Problems', 4],
                ['Sleep', 6]
            ]);
            var options = {
                title: 'My Daily Activities',
            };
            var chart = new google.visualization.PieChart(document.getElementById('charts'));
            chart.draw(data, options);
        }*/
        function escapeHtml(str){
            str = str.replace(/&amp;/g, '&');
            str = str.replace(/&gt;/g, '>');
            str = str.replace(/&lt;/g, '<');
            str = str.replace(/&quot;/g, '"');
            str = str.replace(/&#x27;/g, "'");
            str = str.replace(/&#x60;/g, '`');
            return str;
        }
        function drawChart() {

            var ranking = JSON.parse(escapeHtml('{{ $daily_ranking }}'));
            
            //alert(ranking);
            //console.log(ranking);

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

            //console.log(array_ranking1);
            //array_ranking2 = [['day',  'A8', 'Accesstrade', 'Accesstrade', 'ValueCommerce','Afb']];
            array_ranking2 = new Array();
            element_data = new Array();

            array_ranking1.forEach(function(element){
              //console.log(element);
              var valuesOf = function(obj) {
                return Object.keys(obj).map(function (key) { return obj[key]; })
              }
              //console.log(valuesOf(element));
              array_ranking2.push(valuesOf(element));

            });
            //  console.log(array_ranking2);

          var data = new google.visualization.DataTable();
          

          data.addColumn('string', '');

          array_asp.forEach(function(element){
            data.addColumn('number', element );
            //alert(element);
          });

          data.addRows(array_ranking2);

          //document.getElementById('line_top_x').innerHTML = array_ranking2;

          var options = {
            width: '100%',
          };

            var chart = new google.visualization.LineChart(document.getElementById('line_top_x'));

            chart.draw(data, options);
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
          //console.log(JSON.parse(escapeHtml('{{$total_chart}}')));

          data.addRows(JSON.parse(escapeHtml('{{$total_chart}}')));

          var options = {
            width: '100%',

          };
            var chart = new google.visualization.LineChart(document.getElementById('line_top_y'));
            chart.draw(data, options);
            //var chart_total = new google.charts.Line(document.getElementById('line_top_y'));
            //chart_total.draw(data, google.charts.Line.convertOptions(options));
        }
        //インプレッショングラフ（円）
        function drawChartImp() {
          var ranking = JSON.parse(escapeHtml('{{ $monthlyCharts }}'));
          console.log(ranking);
          i = 0;
          imp_array = [['ASP', 'imp']];
          click_array = [['ASP', 'click']];
          cv_array = [['ASP', 'cv']];

          ranking.forEach(function(element){
                imp_array.push([ element["name"], parseInt(element["imp"], 10) ]);
                click_array.push([ element["name"], parseInt(element["click"], 10) ]);
                cv_array.push([ element["name"], parseInt(element["cv"], 10) ]);
          });
          var data = google.visualization.arrayToDataTable(imp_array);
          var options = {
            legend: { position: 'bottom'} 
          };

          var chart = new google.visualization.PieChart(document.getElementById('chart_imp'));
          chart.draw(data, options);
        }

        google.charts.load("current", {packages:["corechart"]});
        google.charts.setOnLoadCallback(drawChartClick);

        //クリックグラフ（円）
        function drawChartClick() {
          //[ASP名,]
          var data = google.visualization.arrayToDataTable(click_array);

          var options = {
            legend: { position: 'bottom'} 
          };

          var chart = new google.visualization.PieChart(document.getElementById('chart_click'));
          chart.draw(data, options);
        }
        google.charts.load("current", {packages:["corechart"]});
        google.charts.setOnLoadCallback(drawChartCv);

        //CVグラフ（円）
        function drawChartCv() {
          var data = google.visualization.arrayToDataTable(cv_array);

          var options = {
            legend: { position: 'bottom'} 
          };

          var chart = new google.visualization.PieChart(document.getElementById('chart_cv'));
          chart.draw(data, options);
        }
      </script>

</head>
<body onload="init()">
<h3>実績値データ</h3>
        <table class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                  <thead>
                        <tr>
                            <th class="th-sm">No</th>
                            <th class="th-sm">ASP</th>
                            <th class="th-sm">Imp</th>
                            <th class="th-sm">CTR [ % ]</th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR [ % ]</th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">アクティブ数</th>
                            <th class="th-sm">提携数</th>
                            <th class="th-sm">FDグロス</th>
                            <th class="th-sm">CPA</th>
                            <th class="th-sm">承認件数</th>
                            <th class="th-sm">承認金額</th>
                            <th class="th-sm">承認率</th>
                            <th class="th-sm">前月CV（前月比）</th>
                        </tr>
                  </thead>
                <tbody>
                    <?php $i = 1; ?>
                    
                    @foreach($monthlyDatas as $monthlyData)
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $monthlyData->name }}</td>
                        <td>{{ number_format($monthlyData->imp) }}</td>
                        <td>{{ $monthlyData->ctr }}</td>
                        <td>{{ number_format($monthlyData->click) }}</td>
                        <td>{{ $monthlyData->cvr }}</td>
                        <td>{{ number_format($monthlyData->cv) }}</td>
                        <td>{{ number_format($monthlyData->active) }}</td>
                        <td>{{ number_format($monthlyData->partnership) }}</td>
                        <td>{{ number_format($monthlyData->cost) }}</td>
                        <td>{{ number_format($monthlyData->cpa) }}</td>
                        <td>{{ number_format($monthlyData->approval) }}</td>
                        <td>{{ number_format($monthlyData->approval_price) }}</td>
                        <td>{{ number_format($monthlyData->approval_rate) }}%</td>
                        <td>{{ number_format($monthlyData->last_cv) }}</td>
                        <?php $i++; ?>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>合計</td>
                        
                        <td>{{ number_format($monthlyDataTotals[0]['total_imp']) }}</td>
                        <?php 
                          $CtrTotal = (($monthlyDataTotals[0]['total_imp'] != 0 )&&($monthlyDataTotals[0]['total_click'] != 0 ))? 
                          ($monthlyDataTotals[0]['total_click']/$monthlyDataTotals[0]['total_imp'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CtrTotal,2) }}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_click']) }}</td>
                        <?php 
                          $CvrTotal = (($monthlyDataTotals[0]['total_click'] != 0 )&&($monthlyDataTotals[0]['total_cv'] != 0 ))? 
                          ($monthlyDataTotals[0]['total_cv']/$monthlyDataTotals[0]['total_click'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CvrTotal,2) }}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_cv']) }}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_active'])}}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_partnership'])}}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_cost'])}}</td>
                        <?php 
                          $CpaTotal = (($monthlyDataTotals[0]['total_cost'] != 0 )&&($monthlyDataTotals[0]['total_cv'] != 0 ))? 
                          ($monthlyDataTotals[0]['total_cost']/$monthlyDataTotals[0]['total_cv']) : 0 ; 
                        ?>
                        <td>{{ number_format($CpaTotal) }}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_approval'])}}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_approval_price'])}}</td>
                        <?php 
                          $ApprovalRate = (($monthlyDataTotals[0]['total_approval'] != 0 )&&($monthlyDataTotals[0]['total_cv'] != 0 ))? 
                          ($monthlyDataTotals[0]['total_approval']/$monthlyDataTotals[0]['total_cv'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($ApprovalRate,2) }}</td>
                        <td>{{ number_format($monthlyDataTotals[0]['total_last_cv']) }}</td>
                    </tr>
                </tfoot>
          </table>
@if($monthlyDataEstimates != 'Empty' )
<h3>着地想定値データ</h3>
          <table class="table table-striped table-bordered table-hover " cellspacing="0" width="100%">
                  <thead>
                        <tr>
                            <th class="th-sm">No</th>
                            <th class="th-sm">ASP</th>
                            <th class="th-sm">Imp</th>
                            <th class="th-sm">CTR [ % ]</th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR [ % ]</th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">FDグロス</th>
                            <th class="th-sm">CPA</th>
                        </tr>
                  </thead>
                <tbody>
                  <?php $i = 1; ?>
                    @foreach($monthlyDataEstimates as $monthlyDataEstimate)
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $monthlyDataEstimate->name }}</td>
                        <td>{{ number_format($monthlyDataEstimate->estimate_imp) }}</td>
                        <td>{{ number_format($monthlyDataEstimate->estimate_ctr,2) }}</td>
                        <td>{{ number_format($monthlyDataEstimate->estimate_click) }}</td>
                        <td>{{ number_format($monthlyDataEstimate->estimate_cvr,2) }}</td>
                        <td>{{ number_format($monthlyDataEstimate->estimate_cv) }}</td>
                        <td>{{ number_format($monthlyDataEstimate->estimate_cost) }}</td>
                        <?php
                          $t_cpa = (($monthlyDataEstimate->estimate_cost != 0 )&&($monthlyDataEstimate->estimate_cv != 0 ))? 
                          ($monthlyDataEstimate->estimate_cost/$monthlyDataEstimate->estimate_cv) * 100 : 0 ;
                        ?>
                        <td>{{ number_format($t_cpa) }}</td>
                      <?php $i++; ?>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>着地想定</td>
                        <td>合計</td>
                        <td>{{ number_format($monthlyDataEstimateTotals[0]['total_estimate_imp']) }}</td>
                        <?php 
                          $t_imp = $monthlyDataEstimateTotals[0]['total_estimate_imp'];
                          $t_click = $monthlyDataEstimateTotals[0]['total_estimate_click'];
                          $t_cv = $monthlyDataEstimateTotals[0]['total_estimate_cv'];
                          $t_cost = $monthlyDataEstimateTotals[0]['total_estimate_cost'];
                          
                          $t_cvr = (($t_click != 0 )&&($t_cv != 0 ))? ($t_cv/$t_click) * 100 : 0 ;
                          $t_ctr = (($t_click != 0 )&&($t_imp != 0 ))? ($t_click/$t_imp) * 100 : 0 ;
                          $t_cpa = (($t_cv != 0 )&&($t_cost != 0 ))? ($t_cost/$t_cv) : 0 ;
                        ?>
                        <td>{{ number_format($t_cvr,2) }}</td>
                        <td>{{ number_format($monthlyDataEstimateTotals[0]['total_estimate_click']) }}</td>

                        <td>{{ number_format($t_ctr,2) }}</td>
                        <td>{{ number_format($monthlyDataEstimateTotals[0]['total_estimate_cv']) }}</td>
                        <td>{{ number_format($monthlyDataEstimateTotals[0]['total_estimate_cost'])}}</td>
                        <td>{{ number_format($t_cpa,2) }}</td>
                    </tr>
                </tfoot>
          </table>
  @endif
<!--グラフ-->
    <h3 class='top'>インプレッション比</h3>
    <div id="chart_imp" class="pie-chart"></div>
    <h3 class='top'>クリック比</h3>
    <div id="chart_click" class="pie-chart"></div>
    <h3 class='top'>CV比</h3>
    <div id="chart_cv" class="pie-chart"></div>
<h3 class='top'>日次データ</h3>
    <table class="table table-striped table-bordered " width="100%">
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
                            <td>
                            <?php
                             echo ($t->total_click != 0 ||$t->total_imp != 0 )? sprintf('%.2f',( $t->total_click / $t->total_imp ) *100): 0; 
                            ?>
                            </td>
                            <td>{{ $t->total_click }}</td>
                            <td>
                            <?php
                             echo ($t->total_click != 0 ||$t->total_cv != 0 )? sprintf('%.2f',( $t->total_cv / $t->total_click ) *100): 0; 
                            ?>
                            </td>
                            <td>{{ $t->total_cv }}</td>
                            <td>{{ $t->total_estimate_cv }}</td>
                            <td>{{ $t->total_active }}</td>
                            <td>{{ $t->total_partnership }}</td>
                            <td>{{ $t->total_price }}</td>
                            <td> -- </td>
                      </tr>
                      @endforeach
                    </tfoot>
                </table>
<!--グラフ-->
    <h3 class='top'>ASP別　日次CV推移</h3>
    <div id="line_top_x" class="pie-chart"></div>
    <h3 class='top'>CV数xクリック数xインプレッション数</h3>
    <div id="line_top_y" class="pie-chart"></div>

</body>