<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <style type="text/css">
        @font-face {
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
        }

        body {
            font-family: ipag !important;
        }
        table{
            margin:100px auto;
        }
        tr:nth-child(even){
            background:#F2F2F2;
        }
        th{
            background:#222222;
            color:white;
        }
        th:nth-child(odd){
            background:#444444;
        }
        th,td{
            padding:5px;
            font-size:small;
        }

    </style>

</head>
<body onload="init()">
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<!--グラフ-->
        <div class="row">
            <div class="col-lg-12">
          <div class="panel panel-default">
            <div class="panel-heading">ASP別　日次CV推移</div>
            <div class="panel-body">
                    <div id="line_top_x" style="width: 100%;" ></div>
            </div>
          </div>
      </div>
    </div> 
    <div class="row">
      <div class="col-lg-12">
          <div class="panel panel-default">
            <div class="panel-heading">CV数xクリック数xインプレッション数</div>
            <div class="panel-body">
                <div id="line_top_y" ></div>
            </div>
          </div>
      </div>
    </div> 
<div class="row">
    <div class="col-md-12">
                <table cellspacing="0" width="100%">
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
                </table>

        </div>
    </div>
</div>
</div>
<script src="/js/jquery-1.11.1.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/chart.min.js"></script>
<script src="/js/chart-data.js"></script>
<script src="/js/easypiechart.js"></script>
<script src="/js/easypiechart-data.js"></script>
<script src="/js/bootstrap-datepicker.js"></script>
<script src="/js/custom.js"></script>
<script src="/js/addons/datatables.min.js"></script>
<script>
    function init() {
      google.load("visualization", "1", {
                packages: ["line"]
            });
      google.charts.setOnLoadCallback(drawChart);
      google.charts.setOnLoadCallback(drawChart_total);
    }

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
</body>