    @extends('layouts.admin')

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
                    <form role="form" enctype="multipart/form-data" method="post" action="{{ url('admin/export')}}">
                        {{ csrf_field() }}
                        <div class="form-group form-inline">
                            <label>
                            案件選択
                            </label>
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
                            <button type="submit" class="btn btn-default">更新</button> 
                    
                        </div>
                    </form> 
                    @if(old('product'))
                    <hr>
                        <div class="form-group form-inline ">
                            <label>
                                <button class="btn btn-success"><a href='{{ url("admin/pdf")}}'><i class="fas fa-download">&nbsp;</i>テンプレート</a></button>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">年間</label>
                                <div class="col-sm-10">
                                    <div class="text-left form-control-static">
                                        <a href='{{ url("admin/pdf/yearly/". old('product') )}}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">直近３ヶ月</label>
                                <div class="col-sm-10">
                                    <div class="text-left form-control-static">
                                        <a href='{{ url("admin/pdf/three_month/". old('product') ."/term")}}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">昨月の全体成果</label>
                                <div class="col-sm-10">
                                    <div class="text-left form-control-static">
                                        <a href='{{ url("admin/pdf/monthly/". old('product') ."/one_month")}}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">今月の全体成果</label>
                                <div class="col-sm-10">
                                    <div class="text-left form-control-static">
                                        <a href='{{ url("admin/pdf/monthly/". old('product')) }}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">昨月のメディア別成果</label>
                                <div class="col-sm-10">
                                    <div class="text-left form-control-static">
                                        <a href='{{ url("admin/pdf/media/". old('product') ."/one_month")}}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">今月のメディア別成果</label>
                                <div class="col-sm-10">
                                    <div class="text-left form-control-static">
                                        <a href='{{ url("admin/pdf/media/". old('product') )}}' class="btn btn-success btn-sm" role="button"><i class="fas fa-download">&nbsp;</i>PDF</a>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endif

                </div>

            </div>
        </div>

</div>
@endsection
