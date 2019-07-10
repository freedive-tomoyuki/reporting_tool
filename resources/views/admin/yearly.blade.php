@extends('layouts.appnew')

@section('content')

    <div class="row">
      <ol class="breadcrumb">
        <li>レポート</li>
        <li class="active">年間レポート</li>
      </ol>
      <div class="col-lg-12">
        <h3>年間レポート</h3> 

        <div class="panel panel-default ">

          <div class="panel-heading">検索</div>
          <div class="panel-body">
            <div class="col-md-6">
              <form role="form" action="/admin/yearly_result" method="post" class="form-horizontal">
                @csrf

                <div class="form-group">
                  <label class="col-sm-2 control-label">Product</label>
                  <div class="col-sm-10">
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
                  <button type="submit" class="btn btn-primary">検索</button>
                  </form>
                </div>
              
            </div>
          </div>
          @if (count($errors) > 0)
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
          @endif

      </div>
    </div>



    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">検索条件
              <button class="btn btn-success btn-md pull-right">
                <?php
                  $s_date = (old("searchdate_start"))? old("searchdate_start"): date("Y-m-d",strtotime('-1 day'));
                  $e_date = (old("searchdate_end"))? old("searchdate_end"):date("Y-m-d",strtotime('-1 day'));
                  if( old('product')){
                      $product_base = old('product') ;
                  }else{
                      $product_base =  3 ;
                  }
                  
                ?>
                <a href='/admin/csv/{{ $product_base }}/{{ urlencode($s_date) }}/{{  urlencode($e_date) }}' class='d-block text-info'>
                  ＣＳＶ
                </a>
              </button>
                

              </div>
                  <div class="panel-body">

                    <div class="form-group">
                      <label class="col-sm-2 control-label">Product</label>
                      <div class="col-sm-10">
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
        </div>
    </div>
		<!--/.row-->

    <div class="col-md-12">
        <div class="panel panel-primary ">
              <div class="panel-heading">
                年間
              </div>
              <div class="panel-body table-responsive">
                      <table class="table table-striped table-bordered table-hover table-sm" cellspacing="0" width="100%">
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
            </div>
        </div>
      @foreach($asps as $asp)
        <div class="panel panel-success ">
              <div class="panel-heading">
                {{ $asp["name"] }}
                <?php $key = $asp["asp_id"];?>
              </div>
              <div class="panel-body table-responsive">
                      <table class="table table-striped table-bordered table-hover table-sm" cellspacing="0" width="100%">
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
            </div>
        </div>
      @endforeach
    </div>


@endsection
