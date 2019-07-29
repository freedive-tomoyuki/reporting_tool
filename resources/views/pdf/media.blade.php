<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link href="{{ public_path('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/datepicker3.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/styles.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/addons/datatables.min.css')}}" rel="stylesheet">
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <title>メディア別　月間レポート</title>
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
        body{
            background:#ffffff;
        }
        .pie-chart {
            width: 1700px;
            height: 700px;
            margin: 0 auto;
        }
        h3.chart {
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
      function drawChart() {
        
        var ranking = JSON.parse(escapeHtml('{{ $site_ranking }}'));

        array_ranking = new Array();
        array_ranking1 = new Array();
        i = 0;

        ranking.forEach(function(element,i) {
          //console.log(element);
          array_ranking1[i] = new Array();

          for ( var key in element ) {
            var data = element[key];
            array_ranking1[i][key] = data;

          }
          i = i+1;
          //console.log(array_ranking1[i]);
        });
        array_ranking2 = [['Task', 'Hours per Day']];
        array_ranking1.forEach(function(element){
              array_ranking2.push([ element["site_name"], parseInt(element["cv"], 10) ]);
        });
        console.log(array_ranking2) ;
        var data = google.visualization.arrayToDataTable(array_ranking2);

        var options = {
            width: '100%',
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart'));

        chart.draw(data, options);
      }
    </script>

</head>
<body onload="init()">
    <h3>メディア別</h3> 

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
    </table>
<!--グラフ-->
    <h3 class='chart'>サイト別　CV比率</h3>
    <div id="piechart" class="pie-chart"></div>
</body>