@extends('layouts.sponsor')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h2 class="card-header">{{ __('設定') }}</h2>
    </div>
</div>

<div class="col-md-12">
    @if($errors->any())
        @foreach($errors->all() as $message)
            <div class="alert bg-danger" role="alert">
            <em class="fa fa-lg fa-warning">&nbsp;</em> 
            {{ $message }} 
            </div>
            @endforeach
    @endif

        <div class="panel panel-default">
            <div class="panel-heading">エクスポート</div>
            <div class="panel-body">
                    <form role="form" enctype="multipart/form-data" method="post" action="/export">
                        <div class="form-group form-inline">
                            <label>
                            案件選択
                            </label>
                            <fieldset disabled>
                            <select class="form-control" name="product" >
                                    <option value=""> -- </option>
                                    @foreach($product_bases as $product_base)
                                      <option value="{{ $product_base -> id }}"
                                        @if( $user->product_base_id )
                                          @if( $user->product_base_id == $product_base->id  )
                                            selected
                                          @endif
                                        @else
                                          @if( $product_base->id == $user->product_base_id )
                                            selected
                                          @endif
                                        @endif
                                        >{{ $product_base -> product_name }}</option>
                                    @endforeach
                            </select>
                            </fieldset>
                            <!-- <button type="submit" class="btn btn-default">更新</button> --> 
                    
                        </div>
                    </form> 
                    <hr>
                        <div class="form-group form-inline ">
                            <label>
                                <button class="btn btn-success"><a href='/admin/pdf'><i class="fas fa-download">&nbsp;</i>テンプレート</a></button>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">年間</label>
                                <div class="col-sm-9">
                                    <div class="text-left form-control-static">
                                        <a href='/pdf/yearly/{{ $user->product_base_id }}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">直近３ヶ月</label>
                                <div class="col-sm-9">
                                    <div class="text-left form-control-static">
                                        <a href='/pdf/three_month/{{ $user->product_base_id }}/term' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">昨月の全体成果</label>
                                <div class="col-sm-9">
                                    <div class="text-left form-control-static">
                                        <a href='/pdf/monthly/{{ $user->product_base_id }}/one_month' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">今月の全体成果</label>
                                <div class="col-sm-9">
                                    <div class="text-left form-control-static">
                                        <a href='/pdf/monthly/{{ $user->product_base_id }}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">昨月のメディア別成果</label>
                                <div class="col-sm-9">
                                    <div class="text-left form-control-static">
                                        <a href='/pdf/media/{{ $user->product_base_id }}/one_month' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">今月のメディア別成果</label>
                                <div class="col-sm-9">
                                    <div class="text-left form-control-static">
                                        <a href='/pdf/media/{{ $user->product_base_id }}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>

                </div>

        </div>

</div>
@endsection
