@extends('layouts.app')

@section('content')
    <div class="row">
      <ol class="breadcrumb">
        <li>結果一覧</li>
        <li class="active">月次レポート（案件別）</li>
      </ol>
      <div class="col-lg-12">
        <h3>月次レポート（案件別）</h3> 

        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-6">
              <form role="form" action="/monthly_result" method="post" class="form-horizontal">
                @csrf
                  <div class="form-group form-inline " style="padding:10px;">
                  <label>Month</label>
                    <input id="month" type="month" name="month" class="form-control" value=@if( old('month')) 
                      {{ old('month') }}
                    @else
                      {{ date('Y-m',strtotime('-1 day')) }}
                    @endif>
                  </div>

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
              
                <a href='/csv/{{ $products[0]->id }}/{{ $products[0]->date }}'>
                <button class="btn btn-default btn-md pull-right">
                  ＣＳＶ
                </button>
                </a>
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

                          <div class="form-group">
                            <label class="col-sm-2 control-label">消化率</label>
                            <div class="col-sm-10">
                              <p class="form-control-static">
                              <?php
                                   echo ceil((date("d",strtotime('-1 day'))/date("t"))*100)." %";
                              ?>
                              </p>
                            </div>
                          </div>
                      </div>
            </div>
        </div>
    </div>

<!--グラフ-->
    <div class="row">
        <div class="col-lg-4">
          <div class="panel panel-default">
            <div class="panel-heading">インプレッション数</div>
            <div class="panel-body">
                <div id="chart_imp"style="height: 300px;"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="panel panel-default">
            <div class="panel-heading">クリック数</div>
            <div class="panel-body">
                <div id="chart_click" style="height: 300px;"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="panel panel-default">
            <div class="panel-heading">コンバージョン数</div>
            <div class="panel-body">
                <div id="chart_cv" style="height: 300px;"></div>
            </div>
          </div>
        </div>
        <div class="col-md-12">
          <div class="panel panel-default ">
            <div class="panel-heading">
              実績値
            </div>
            <div class="panel-body table-responsive">
                <table class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                  <thead>
                        <tr>
                            <th class="th-sm">No</th>
                            <th class="th-sm">ASP</th>
                            <!-- <th class="th-sm">Date</th> -->
                            <th class="th-sm">Imp</th>
                            <th class="th-sm">CTR</th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR</th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">アクティブ数</th>
                            <th class="th-sm">提携数</th>
                            <th class="th-sm">FDグロス</th>
                            <!-- <th class="th-sm">獲得単価</th> -->
                            <th class="th-sm">CPA</th>
                            <th class="th-sm">承認件数</th>
                            <th class="th-sm">承認金額</th>
                            <th class="th-sm">承認率</th>
                            <th class="th-sm">前月CV（前月比）</th>
                        </tr>
                  </thead>
                <tbody>
                    <?php $i = 1; ?>
                    
                    @foreach($products as $product)
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $product->name }}</td>
                        <td>{{ number_format($product->imp) }}</td>
                        <td>{{ $product->ctr }}</td>
                        <td>{{ number_format($product->click) }}</td>
                        <td>{{ $product->cvr }}</td>
                        <td>{{ number_format($product->cv) }}</td>
                        <td>{{ number_format($product->active) }}</td>
                        <td>{{ number_format($product->partnership) }}</td>
                        <td>{{ number_format($product->cost) }}</td>
                        <!-- <td>{{ number_format($product->price) }}</td> -->
                        <td>{{ number_format($product->cpa) }}</td>
                        <td>{{ number_format($product->approval) }}</td>
                        <td>{{ number_format($product->approval_price) }}</td>
                        <td>{{ number_format($product->approval_rate) }}%</td>
                        <td>{{ number_format($product->last_cv) }}</td>
                        <?php $i++; ?>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>合計</td>
                        
                        <td>{{ number_format($productsTotals[0]['total_imp']) }}</td>
                        <?php 
                          $CtrTotal = (($productsTotals[0]['total_imp'] != 0 )&&($productsTotals[0]['total_click'] != 0 ))? 
                          ($productsTotals[0]['total_click']/$productsTotals[0]['total_imp'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CtrTotal,2) }}%</td>
                        <td>{{ number_format($productsTotals[0]['total_click']) }}</td>
                        <?php 
                          $CvrTotal = (($productsTotals[0]['total_click'] != 0 )&&($productsTotals[0]['total_cv'] != 0 ))? 
                          ($productsTotals[0]['total_cv']/$productsTotals[0]['total_click'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CvrTotal,2) }}%</td>
                        <td>{{ number_format($productsTotals[0]['total_cv']) }}</td>
                        <td>{{ number_format($productsTotals[0]['total_active'])}}</td>
                        <td>{{ number_format($productsTotals[0]['total_partnership'])}}</td>
                        <td>{{ number_format($productsTotals[0]['total_cost'])}}</td>
                        <!-- <td>{{ number_format($productsTotals[0]['total_price']) }}</td> -->
                        <?php 
                          $CpaTotal = (($productsTotals[0]['total_cost'] != 0 )&&($productsTotals[0]['total_cv'] != 0 ))? 
                          ($productsTotals[0]['total_cost']/$productsTotals[0]['total_cv'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CpaTotal) }}</td>
                        <td>{{ number_format($productsTotals[0]['total_approval'])}}</td>
                        <td>{{ number_format($productsTotals[0]['total_approval_price'])}}</td>
                        <?php 
                          $ApprovalRate = (($productsTotals[0]['total_approval'] != 0 )&&($productsTotals[0]['total_cv'] != 0 ))? 
                          ($productsTotals[0]['total_approval']/$productsTotals[0]['total_cv'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($ApprovalRate,2) }}%</td>
                        <td>{{ number_format($productsTotals[0]['total_last_cv']) }}</td>
                    </tr>
                </tfoot>
            </table>
          </div>
        </div>
        @if($productsEstimates != 'Empty' )
        
          <div class="panel panel-default">
            <div class="panel-heading">
              想定値
            </div>
          <div class="panel-body table-responsive">
          <table class="table table-striped table-bordered table-hover " cellspacing="0" width="100%">
                  <thead>
                        <tr>
                            <th class="th-sm">No</th>
                            <th class="th-sm">ASP</th>
                            <!-- <th class="th-sm">Date</th> -->
                            <th class="th-sm">Imp</th>
                            <th class="th-sm">CTR</th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR</th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">FDグロス</th>
                            <th class="th-sm">承認件数</th>
                            <th class="th-sm">承認金額</th>
                            <!-- <th class="th-sm">獲得単価</th> -->
                            <th class="th-sm">CPA</th>

                            
                        </tr>
                  </thead>
                <tbody>
                  <?php $i = 1; ?>
                    @foreach($productsEstimates as $productsEstimate)
                    <tr>
                      
                        <td><?php echo $i; ?></td>
                        <td>{{ $productsEstimate->name }}</td>
                        <td>{{ number_format($productsEstimate->estimate_imp) }}</td>
                        <td>{{ $productsEstimate->estimate_ctr }}</td>
                        <td>{{ number_format($productsEstimate->estimate_click) }}</td>
                        <td>{{ $productsEstimate->estimate_cvr }}</td>
                        <td>{{ number_format($productsEstimate->estimate_cv) }}</td>
                        <td>{{ number_format($productsEstimate->estimate_cost) }}</td>
                        <!-- <td>{{ number_format($productsEstimate->estimate_price) }}</td> -->
                        <td>{{ number_format($productsEstimate->estimate_cpa) }}</td>
                        <td>{{ number_format($productsEstimate->estimate_approval) }}</td>
                        <td>{{ number_format($productsEstimate->estimate_approval_price) }}</td>
                      <?php $i++; ?>
                    </tr>
                    @endforeach

                </tbody>
                <tfoot>
                    <tr>
                        <td>着地想定</td>
                        <td>合計</td>
                        <td>{{ number_format($productsEstimateTotals[0]['total_estimate_imp']) }}</td>
                        <?php 
                          $t_imp = $productsEstimateTotals[0]['total_estimate_imp'];
                          $t_click = $productsEstimateTotals[0]['total_estimate_click'];
                          $t_cv = $productsEstimateTotals[0]['total_estimate_cv'];
                          $t_cost = $productsEstimateTotals[0]['total_estimate_cost'];
                          
                          $t_cvr = (($t_click != 0 )&&($t_cv != 0 ))? ($t_cv/$t_click) * 100 : 0 ;
                          $t_ctr = (($t_click != 0 )&&($t_imp != 0 ))? ($t_click/$t_imp) * 100 : 0 ;
                          $t_cpa = (($t_cv != 0 )&&($t_cost != 0 ))? ($t_cost/$t_cv) : 0 ;
                        ?>
                        <td>{{ number_format($t_cvr,2) }}%</td>
                        <td>{{ number_format($productsEstimateTotals[0]['total_estimate_click']) }}</td>

                        <td>{{ number_format($t_ctr,2) }}%</td>
                        <td>{{ number_format($productsEstimateTotals[0]['total_estimate_cv']) }}</td>
                        <td>{{ number_format($productsEstimateTotals[0]['total_estimate_cost'])}}</td>
                        <td>{{ number_format($t_cpa,2) }}</td>
                    </tr>
                </tfoot>
          </table>
          @endif
          </div>
        </div>
    </div>
</div>

    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChartImp);
      
      function drawChartImp() {
        var ranking = JSON.parse(escapeHtml('{{ $chart_data }}'));
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
        
        console.log(imp_array);
        console.log(click_array);
        console.log(cv_array);

        var data = google.visualization.arrayToDataTable(imp_array);

        var options = {
          legend: { position: 'bottom'} 
        };

        var chart = new google.visualization.PieChart(document.getElementById('chart_imp'));
        chart.draw(data, options);
      }
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChartClick);
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
      function drawChartCv() {
        var data = google.visualization.arrayToDataTable(cv_array);

        var options = {
          legend: { position: 'bottom'} 
        };

        var chart = new google.visualization.PieChart(document.getElementById('chart_cv'));
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
