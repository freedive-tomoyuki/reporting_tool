<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link href="{{ public_path('css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ public_path('css/styles.css')}}" rel="stylesheet">
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
    </style>

</head>
<body>
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
                            
                          ?>
                          <tr>
                              <td>表示回数</td>
                          @foreach($yearly_imps as $imp)
                             <?php $total_imp =+ $imp; ?> 
                              <td>{{ $imp }}</td>
                          @endforeach
                              <td>{{ $total_imp }}</td>
                          </tr>
                          <tr>
                              <td>クリック数</td>
                          @foreach($yearly_clicks as $click)
                              <?php $total_click =+ $click; ?>
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
                              <?php $total_cv =+ $cv; ?>
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
                            
                          ?>
                          <tr>
                              <td>表示回数</td>
                          @foreach($yearly_imps_asp[$key] as $imp)
                             <?php $total_imp_asp =+ $imp; ?> 
                              <td>{{ $imp }}</td>
                          @endforeach
                              <td>{{ $total_imp_asp }}</td>
                          </tr>
                          <tr>
                              <td>クリック数</td>
                          @foreach($yearly_clicks_asp[$key] as $click)
                              <?php $total_click_asp =+ $click; ?>
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
                              <?php $total_cv_asp =+ $cv; ?>
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
      @endforeach

</body>