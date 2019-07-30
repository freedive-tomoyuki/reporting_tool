<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link href="{{ public_path('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/styles.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/addons/datatables.min.css')}}" rel="stylesheet">
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <title>年間レポート</title>
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
        div.next {
            /*page-break-after: always;*/
            page-break-before: always;
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
        }
        function drawChart() {

            var ranking = JSON.parse(escapeHtml('{{ $yearly_chart }}'));

            var array = new Array();
            var array_asp = new Array();
            i = 0;

              ranking.forEach(function(element,i) {
                //console.log(element);
                array[i] = new Array();

                for ( var key in element ) {

                  var data = element[key];

                  if(key == 'date'){
                      //var date = new Date(data);

                      //var year = date.getFullYear();
                      //var month = date.getMonth() + 1;
                      //var day = date.getDate();
                      data = data.substr( 0 , 7 );
                      array[i][key] = data;
          
                  }else{
                    array[i][key] = parseInt(data, 10);

                    if(array_asp.indexOf(key) < 0){
                      array_asp.push(key);
                    }
                    
                  }
                  
                }
                i = i+1;
                //console.log(array_ranking1[i]);
              });
              console.log(array_asp);
              console.log(array);

              array_ranking2 = new Array();
              element_data = new Array();
              array.forEach(function(element){
                //console.log(element);
                var valuesOf = function(obj) {
                  return Object.keys(obj).map(function (key) { return obj[key]; })
                }
                //console.log(valuesOf(element));
                array_ranking2.push(valuesOf(element));

              });
              console.log(array_ranking2);
            
              var data = new google.visualization.DataTable();
                  data.addColumn('string', 'months');

              array_asp.forEach(function(element){
                data.addColumn('number', element );
              });
              data.addRows(array_ranking2);

              var options = {
                width: '100%',

              };

              var chart = new google.visualization.LineChart(document.getElementById('line_top_x'));

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
</head>
<body onload="init()">
        <h3>年間総合</h3> 
                <table class="table table-striped table-bordered" width="100%">
                        <thead>
                              <tr>
                                  <th class="th-sm">No</th>
                                  @for( $i=12 ; $i > 0  ; $i-- )
                                    <?php $month = date("Y年m月", strtotime('-'.$i.' month'));?>
                                    <th class="th-sm">{{ $month }}</th>
                                  @endfor
                                  <th>合計</th>
                              </tr>
                        </thead>
                      <tbody>
                          <?php 
                            $i = 1; 
                            $total_imp = 0;
                            $total_click = 0;
                            $total_cv = 0;
                            $count = 0;
                          ?>
                          <tr>
                              <td>表示回数</td>
                          @foreach($yearly_imps as $imp)
                             <?php $total_imp += $imp; ?> 
                              <td>{{ $imp }}</td>
                          @endforeach
                              <td>{{ $total_imp }}</td>
                          </tr>
                          <tr>
                              <td>クリック数</td>
                          @foreach($yearly_clicks as $click)
                              <?php $total_click += $click; ?>
                              <td>{{ $click }}</td>
                          @endforeach
                              <td>{{ $total_click }}</td>
                          </tr>
                          <tr>
                              <td>CTR</td>
                          @foreach($yearly_ctrs as $ctr)
                              <td>{{ number_format($ctr,2) }}</td>
                          @endforeach
                              <td> - </td>
                          </tr>
                          <tr>
                              <td>発生成果数</td>
                          @foreach($yearly_cvs as $cv)
                              <?php $total_cv += $cv; ?>
                              <td>{{ $cv }}</td>
                          @endforeach
                              <td>{{ $total_cv }}</td>
                          </tr>
                          <tr>
                              <td>CVR</td>
                          @foreach($yearly_cvrs as $cvr)
                              <td>{{ number_format($cvr,2) }}</td>
                          @endforeach
                              <td> - </td>
                          </tr>

                      </tbody>
                    </table>
    <div class='next'></div>
     @foreach($asps as $asp)
        <h3>{{ $asp["name"] }}</h3>
          <?php $key = $asp["asp_id"];?>
                  <table class="table table-striped table-bordered " width="100%">
                        <thead>
                              <tr>
                                  <th class="th-sm">No</th>
                                  @for( $i=12 ; $i > 0  ; $i-- )
                                    <?php $month = date("Y年m月", strtotime('-'.$i.' month'));?>
                                    <th class="th-sm">{{ $month }}</th>
                                  @endfor
                                  <th>合計</th>
                              </tr>
                        </thead>
                      <tbody>
                          <?php 
                            $i = 1; 
                            $total_imp_asp = 0;
                            $total_click_asp = 0;
                            $total_cv_asp = 0;
                          ?>
                          <tr>
                              <td>表示回数</td>
                          @foreach($yearly_imps_asp[$key] as $imp)
                             <?php $total_imp_asp += $imp; ?> 
                              <td>{{ $imp }}</td>
                          @endforeach
                              <td>{{ $total_imp_asp }}</td>
                          </tr>
                          <tr>
                              <td>クリック数</td>
                          @foreach($yearly_clicks_asp[$key] as $click)
                              <?php $total_click_asp += $click; ?>
                              <td>{{ $click }}</td>
                          @endforeach
                              <td>{{ $total_click_asp }}</td>
                          </tr>
                          <tr>
                              <td>CTR</td>
                          @foreach($yearly_ctrs_asp[$key] as $ctr)
                              <td>{{ number_format($ctr,2) }}</td>
                          @endforeach
                              <td> - </td>
                          </tr>
                          <tr>
                              <td>発生成果数</td>
                          @foreach($yearly_cvs_asp[$key] as $cv)
                              <?php $total_cv_asp += $cv; ?>
                              <td>{{ $cv }}</td>
                          @endforeach
                              <td>{{ $total_cv_asp }}</td>
                          </tr>
                          <tr>
                              <td>CVR</td>
                          @foreach($yearly_cvrs_asp[$key] as $cvr)
                              <td>{{ number_format($cvr,2) }}</td>
                          @endforeach
                              <td> - </td>
                          </tr>

                      </tbody>
                    </table>
          <?php 
            $count++;
          ?>
          @if( $count % 3 == 0 )
            <div class='next'></div>
          @endif
      @endforeach
<!--グラフ-->
    <h3 class='top'>ASP別　年間CV推移</h3>
    <div id="line_top_x" class="pie-chart"></div>
</body>