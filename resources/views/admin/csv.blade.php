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
    <!--
        <div class="panel panel-default">
            <div class="panel-heading">月次CSVインポート</div>
            <div class="panel-body">
                <form role="form" enctype="multipart/form-data" method="post" action="/admin/csv/month/import">
                    {{ csrf_field() }}
                    <label></label>

                    <div class="form-group form-inline ">
                        <input type="file" name="csv_file" id="csv_file">

                    </div>
                    <button type="submit" class="btn btn-primary">登録</button>
                        
                </form>


            </div>
        </div>
    -->
        <div class="panel panel-default">
            <div class="panel-heading">月次/日次 CSVインポート</div>
            <div class="panel-body">
                <form role="form" enctype="multipart/form-data" method="post" action="{{ url('admin/csv/daily/import')}}">
                    {{ csrf_field() }}
                    
                    <div class="form-group form-inline ">
                        <input type="file" name="csv_file" id="csv_file">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload">&nbsp;</i>登録</button>

                </form>
                <div>
                <hr>
                    <u><a href="{{ url('admin/DownloadTemplateCsv')}}"><i class="fas fa-download">&nbsp;</i>CSVフォーマットをダウンロードする</a></br></u>
                    ※【ASP】×【日別】✕【案件】の件数をご記載してください。</br>
                    ※ASPIDは、ASP管理をご参照ください。</br>
                    ※案件IDは、広告主管理をご参照ください。</br>
                </div>

            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">サイト別(月別)CSVインポート</div>
            <div class="panel-body">
                <form role="form" enctype="multipart/form-data" method="post" action="{{ url('admin/csv_site/import')}}">
                    {{ csrf_field() }}
                    <div class="form-group  col-md-3">
                        <label>アップロード月</label>
                        <input type="month" name="month" class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="file" name="csv_file" id="csv_file">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload">&nbsp;</i>登録</button>

                </form>
                <hr>
                    <u><a href="{{ url('admin/DownloadTemplateCsvSite')}}"><i class="fas fa-download">&nbsp;</i>CSVフォーマットをダウンロードする</a></br></u>
                    ※【ASP】✕【サイト】✕【月別】×【案件】の件数をご記載してください。</br>
                    ※ASPIDは、ASP管理をご参照ください。</br>
                    ※案件IDは、広告主管理をご参照ください。</br>
                    <span style="color:red">※日別サイト毎データはこちらで更新することができません。</span></br>

            </div>
        </div>

</div>
@endsection
