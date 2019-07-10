    @extends('layouts.appnew')

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
                    <form role="form" enctype="multipart/form-data" method="post" action="/admin/export">
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
                            <button class="btn btn-default"><a href='/admin/pdf'>テンプレート</a></button>
                        </label>
                    </div>
                    <div class="form-group form-inline ">
                        <label>
                            年間
                            <button class="btn btn-default"><a href='/admin/pdf/{{ old('product') }}'>PDF</a></button> 
                            <button class="btn btn-success"><a href='/admin/excel/{{ old('product') }}'>Excel</a></button>
                        </label>
                    </div>
                    <div class="form-group form-inline ">
                        <label>
                            直近３ヶ月
                            <button class="btn btn-default"><a href='/admin/pdf/{{ old('product') }}'>PDF</a></button> 
                            <button class="btn btn-success"><a href='/admin/excel/{{ old('product') }}'>Excel</a></button>
                        </label>
                    </div>
                    <div class="form-group form-inline ">
                        <label>
                            昨月の全体成果
                            <button class="btn btn-default"><a href='/admin/pdf/{{ old('product') }}'>PDF</a></button> 
                            <button class="btn btn-success"><a href='/admin/excel/{{ old('product') }}'>Excel</a></button>
                        </label>
                    </div>
                    <div class="form-group form-inline ">
                        <label>
                            今月の全体成果
                            <button class="btn btn-default"><a href='/admin/pdf/{{ old('product') }}'>PDF</a></button> 
                            <button class="btn btn-success"><a href='/admin/excel/{{ old('product') }}'>Excel</a></button>
                        </label>
                    </div>
                    <div class="form-group form-inline ">
                        <label>
                            昨月のメディア別成果
                            <button class="btn btn-default"><a href='/admin/pdf/{{ old('product') }}'>PDF</a></button> 
                            <button class="btn btn-success"><a href='/admin/excel/{{ old('product') }}'>Excel</a></button>
                        </label>
                    </div>
                    <div class="form-group form-inline ">
                        <label>
                            今月のメディア別成果 
                            <button class="btn btn-default"><a href='/admin/pdf/{{ old('product') }}'>PDF</a></button> 
                            <button class="btn btn-success"><a href='/admin/excel/{{ old('product') }}'>Excel</a></button>
                        </label>
                    </div>
                    @endif
                    

                <div>
                <hr>
                    <a href='/admin/DownloadTemplateCsv'>CSVフォーマットをダウンロードする</a></br>
                    ※【ASP】×【日別】✕【案件】の件数をご記載してください。</br>
                    ※ASPIDは、ASP管理をご参照ください。</br>
                    ※案件IDは、広告主管理をご参照ください。</br>
                </div>

            </div>
        </div>

</div>
@endsection
