@extends('layouts.sponsor')

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
              <form role="form" action="{{ url('monthly_result')}}" method="post" class="form-horizontal">
                @csrf
                  <div class="form-group form-inline " style="padding:10px;">
                  <label>Month</label>
                    <input id="month" type="month" name="month" class="form-control" 
                    @if( old('month')) 
                      value="{{ old('month') }}"
                    @else
                      value="{{ date('Y-m', strtotime('-1 day')) }}"
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
@if(!$products->isEmpty())

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
              <button class="btn btn-success btn-md pull-right">
                <i class='fas fa-file-download'></i>
                <?php
                   $month = (old("month"))? old("month"): date('Y-m',strtotime('-1 day'));
                   $product_base = ( old('product'))? old('product') : 3; 
                ?>
                <a href="{{ url('admin/monthly/csv?p='.$product_base.'&month='.$month ) }}">
                    CSV
                </a>
                </button>
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
                    
                    @foreach($products as $p)
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>{{ $p->name }}</td>
                        <td>{{ number_format($p->imp) }}</td>
                        <td>{{ $p->ctr }}</td>
                        <td>{{ number_format($p->click) }}</td>
                        <td>{{ $p->cvr }}</td>
                        <td>{{ number_format($p->cv) }}</td>
                        <td>{{ number_format($p->active) }}</td>
                        <td>{{ number_format($p->partnership) }}</td>
                        <td>{{ number_format($p->cost) }}</td>
                        <!-- <td>{{ number_format($p->price) }}</td> -->
                        <td>{{ number_format($p->cpa) }}</td>
                        <td>{{ number_format($p->approval) }}</td>
                        <td>{{ number_format($p->approval_price) }}</td>
                        <td>{{ number_format($p->approval_rate) }}%</td>
                        <td>{{ number_format($p->last_cv) }}</td>
                        <?php $i++; ?>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>合計</td>
                        
                        <td>{{ number_format($products_totals[0]['total_imp']) }}</td>
                        <?php 
                          $CtrTotal = (($products_totals[0]['total_imp'] != 0 )&&($products_totals[0]['total_click'] != 0 ))? 
                          ($products_totals[0]['total_click']/$products_totals[0]['total_imp'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CtrTotal,2) }}%</td>
                        <td>{{ number_format($products_totals[0]['total_click']) }}</td>
                        <?php 
                          $CvrTotal = (($products_totals[0]['total_click'] != 0 )&&($products_totals[0]['total_cv'] != 0 ))? 
                          ($products_totals[0]['total_cv']/$products_totals[0]['total_click'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CvrTotal,2) }}%</td>
                        <td>{{ number_format($products_totals[0]['total_cv']) }}</td>
                        <td>{{ number_format($products_totals[0]['total_active'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_partnership'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_cost'])}}</td>
                        <!-- <td>{{ number_format($products_totals[0]['total_price']) }}</td> -->
                        <?php 
                          $CpaTotal = (($products_totals[0]['total_cost'] != 0 )&&($products_totals[0]['total_cv'] != 0 ))? 
                          ($products_totals[0]['total_cost']/$products_totals[0]['total_cv'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CpaTotal) }}</td>
                        <td>{{ number_format($products_totals[0]['total_approval'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_approval_price'])}}</td>
                        <?php 
                          $ApprovalRate = (($products_totals[0]['total_approval'] != 0 )&&($products_totals[0]['total_cv'] != 0 ))? 
                          ($products_totals[0]['total_approval']/$products_totals[0]['total_cv'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($ApprovalRate,2) }}%</td>
                        <td>{{ number_format($products_totals[0]['total_last_cv']) }}</td>
                    </tr>
                </tfoot>
            </table>
          </div>
        </div>
        @if($products_estimates != 'Empty' )
        
          <div class="panel panel-default">
            <div class="panel-heading">
              想定値
              <button class="btn btn-success btn-md pull-right">
                <i class='fas fa-file-download'></i>
                <a href="{{ url('admin/csv_monthly_estimate/'.( old('product'))? old('product') :  3 ) }}">

                    CSV
                  </a>
                </button>
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
                    @foreach($products_estimates as $e)
                    <tr>
                      
                        <td><?php echo $i; ?></td>
                        <td>{{ $e->name }}</td>
                        <td>{{ number_format($e->estimate_imp) }}</td>
                        <td>{{ $e->estimate_ctr }}</td>
                        <td>{{ number_format($e->estimate_click) }}</td>
                        <td>{{ $e->estimate_cvr }}</td>
                        <td>{{ number_format($e->estimate_cv) }}</td>
                        <td>{{ number_format($e->estimate_cost) }}</td>
                        <!-- <td>{{ number_format($productsEstimate->estimate_price) }}</td> -->
                        <td>{{ number_format($e->estimate_cpa) }}</td>
                        <td>{{ number_format($e->estimate_approval) }}</td>
                        <td>{{ number_format($e->estimate_approval_price) }}</td>
                      <?php $i++; ?>
                    </tr>
                    @endforeach

                </tbody>
                <tfoot>
                    <tr>
                        <td>着地想定</td>
                        <td>合計</td>
                        <td>{{ number_format($products_estimate_totals[0]['total_estimate_imp']) }}</td>
                        <?php 
                          $t_imp = $products_estimate_totals[0]['total_estimate_imp'];
                          $t_click = $products_estimate_totals[0]['total_estimate_click'];
                          $t_cv = $products_estimate_totals[0]['total_estimate_cv'];
                          $t_cost = $products_estimate_totals[0]['total_estimate_cost'];
                          
                          $t_cvr = (($t_click != 0 )&&($t_cv != 0 ))? ($t_cv/$t_click) * 100 : 0 ;
                          $t_ctr = (($t_click != 0 )&&($t_imp != 0 ))? ($t_click/$t_imp) * 100 : 0 ;
                          $t_cpa = (($t_cv != 0 )&&($t_cost != 0 ))? ($t_cost/$t_cv) : 0 ;
                        ?>
                        <td>{{ number_format($t_cvr,2) }}%</td>
                        <td>{{ number_format($products_estimate_totals[0]['total_estimate_click']) }}</td>

                        <td>{{ number_format($t_ctr,2) }}%</td>
                        <td>{{ number_format($products_estimate_totals[0]['total_estimate_cv']) }}</td>
                        <td>{{ number_format($products_estimate_totals[0]['total_estimate_cost'])}}</td>
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

@else
    <div class="row">
         <div class="col-md-12">
            <div class="alert bg-danger" role="alert"><em class="fa fa-lg fa-warning">&nbsp;</em>
            検索結果が見つかりません。 <a href="#" class="pull-right"><em class="fa fa-lg fa-close"></em></a></div>
    </div>
</div>
@endif
@endsection
