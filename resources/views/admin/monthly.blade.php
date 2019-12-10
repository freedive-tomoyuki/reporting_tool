@extends('layouts.admin')

@section('content')
<div class="row">
   <ol class="breadcrumb">
      <li>結果一覧</li>
      <li class="active">月次レポート（案件別）</li>
   </ol>
   <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
      <h3>月次レポート（案件別）</h3>
      <div class="panel panel-default">
         <div class="panel-heading text-center">検索する</div>
         <form role="form" action="{{ url('admin/monthly_result')}}" method="post" >
            @csrf
            <div class="panel-body ">
              <div class="col-md-9 col-md-offset-1">
                  <div class="form-group col-md-6">
                      <label class="control-label">Month</label>
                      <div>
                         <input id="month" type="month" name="month" class="form-control" value=@if( old('month')) 
                        {{ old('month') }}
                      @else
                        {{ date('Y-m',strtotime('-1 day')) }}
                      @endif>
                      </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label class="control-label">Product</label>
                       <select class="form-control" name="product" >
                                <option value=""> -- </option>
                                @foreach($product_bases as $product_base)
                                  <option value="{{ $product_base->id }}"
                                    @if( old('product'))
                                      @if( old('product') == $product_base->id )
                                        selected
                                      @endif
                                    @else
                                      @if( $product_base->id == 3 )
                                        selected
                                      @endif
                                    @endif

                                    >{{ $product_base->product_name }}</option>
                                @endforeach
                      </select>
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
         <div class="panel-heading text-center">現在の検索条件を表示する</div>
         <form role="form" action="{{ url('admin/monthly_result')}}" method="post" >
            @csrf
            <div class="panel-body ">
              <div class="col-md-9 col-md-offset-1">
                  <div class="col-md-12">
                     <div class="form-group col-md-6">
                        <label class="control-label">Month</label>
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

                     <div class="form-group col-md-6">
                      <label class="control-label">Product</label>
                                <p class="form-control-static">
                                  @if( old('product'))
                                    @foreach($product_bases as $product_base)
                                        @if( old('product') == $product_base->id  )
                                            {{ $product_base -> product_name }}
                                        @endif
                                    @endforeach
                                @else
                                 案件の指定がございません
                                @endif
                                </p>
                     </div>
                     <div class="form-group col-md-12">
                      <label class="control-label">消化率</label>
                              <p class="form-control-static">
                                @if(old('month') == date('Y-m') || !old('month'))
                                  {{ ceil((date("d",strtotime('-1 day'))/date("t"))*100).'%' }} 
                                @else
                                  {{ '100%' }}　
                                @endif
                              </p>
                     </div>
                  </div>
              </div>
         </div>
         </form>
      </div>
   </div>
</div>
 <!--        <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">検索条件
              

                </<a>
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
                            <label class="col-sm-2 control-label">Product</label>
                            <div class="col-sm-10">
                              <p class="form-control-static">
                                @if( old('product'))
                                  @foreach($product_bases as $product_base)
                                      @if( old('product') == $product_base->id  )
                                          {{ $product_base -> product_name }}
                                      @endif
                                  @endforeach
                              @else
                               案件の指定がございません
                              @endif
                              </p>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-2 control-label">消化率</label>
                            <div class="col-sm-10">
                              <p class="form-control-static">
                              <?php
                                   //echo ceil((date("d",strtotime('-1 day'))/date("t"))*100)." %";
                              ?>
                              </p>
                            </div>
                          </div>
                      </div>
            </div>
        </div>
    </div> -->

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
                <a href="/admin/csv_monthly/@if( old('product')){{ old('product') }}@else{{ 3 }}@endif/@if( old('month')){{ old('month') }}
                  @else{{ date('Y-m',strtotime('-1 day'))}}@endif">
                  
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
                            <th class="th-sm">CTR <div>[ % ]</div></th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR <div>[ % ]</div></th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">アクティブ数</th>
                            <th class="th-sm">提携数</th>
                            <th class="th-sm">FDグロス</th>
                            <!-- <th class="th-sm">獲得単価</th> -->
                            <th class="th-sm message"><span class="balloon">FDグロス/CV数</span>CPA</th>
                            <th class="th-sm">承認件数</th>
                            <th class="th-sm">承認金額</th>
                            <th class="th-sm message"><span class="balloon">直近３ヶ月の数値から算出</span>承認率</th>
                            <th class="th-sm">前月CV<div>（前月比）</div></th>
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
                        
                        <td>{{ number_format($products_totals[0]['total_imp']) }}</td>
                        <?php 
                          $CtrTotal = (($products_totals[0]['total_imp'] != 0 )&&($products_totals[0]['total_click'] != 0 ))? 
                          ($products_totals[0]['total_click']/$products_totals[0]['total_imp'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CtrTotal,2) }}</td>
                        <td>{{ number_format($products_totals[0]['total_click']) }}</td>
                        <?php 
                          $CvrTotal = (($products_totals[0]['total_click'] != 0 )&&($products_totals[0]['total_cv'] != 0 ))? 
                          ($products_totals[0]['total_cv']/$products_totals[0]['total_click'])*100 : 0 ; 
                        ?>
                        <td>{{ number_format($CvrTotal,2) }}</td>
                        <td>{{ number_format($products_totals[0]['total_cv']) }}</td>
                        <td>{{ number_format($products_totals[0]['total_active'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_partnership'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_cost'])}}</td>
                        
                        <td>{{ number_format($products_totals[0]['total_cpa']) }}</td>
                        <td>{{ number_format($products_totals[0]['total_approval'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_approval_price'])}}</td>
                        <td>{{ number_format($products_totals[0]['total_approval_rate'],2) }}%</td>
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
                  <a href="/admin/csv_monthly_estimate/@if( old('product')){{ old('product') }}@else{{ 3 }}@endif">
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
                            <th class="th-sm">CTR <div>[ % ]</div></th>
                            <th class="th-sm">Click</th>
                            <th class="th-sm">CVR <div>[ % ]</div></th>
                            <th class="th-sm">CV</th>
                            <th class="th-sm">FDグロス</th>
                            <!-- <th class="th-sm">予想承認金額</th> -->
                            <!-- <th class="th-sm">獲得単価</th> -->
                            <th class="th-sm">CPA</th>

                            
                        </tr>
                  </thead>
                <tbody>
                  <?php $i = 1; ?>
                    @foreach($products_estimates as $productsEstimate)
                    <tr>
                      
                        <td><?php echo $i; ?></td>
                        <td>{{ $p->name }}</td>
                        <td>{{ number_format($p->estimate_imp) }}</td>
                        <td>{{ number_format($p->estimate_ctr,2) }}</td>
                        <td>{{ number_format($p->estimate_click) }}</td>
                        <td>{{ number_format($p->estimate_cvr,2) }}</td>
                        <td>{{ number_format($p->estimate_cv) }}</td>
                        <td>{{ number_format($p->estimate_cost) }}</td>
                        <!-- <td>{{ number_format($p->estimate_approval_price) }}</td> -->
                        <?php
                          $t_cpa = (($p->estimate_cost != 0 )&&($p->estimate_cv != 0 ))? ($p->estimate_cost/$p->estimate_cv) : 0 ;
                        

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
                        <td>{{ number_format($t_cvr,2) }}</td>
                        <td>{{ number_format($products_estimate_totals[0]['total_estimate_click']) }}</td>

                        <td>{{ number_format($t_ctr,2) }}</td>
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
@endsection
