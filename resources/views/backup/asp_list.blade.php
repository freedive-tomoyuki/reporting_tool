@extends('layouts.appnew')

@section('content')
<div class="row">
    <ol class="breadcrumb">
      <li class="active">ASP管理</li>
    </ol>
      <div class="col-lg-12">
        <h1 class="page-header">ASP管理</h1>

        <div class="panel panel-default ">

        	<div class="panel-heading">ASP　一覧</div>
		        <div class="panel-body  articles-container">
					<?php $i = 1;?>
					@foreach($asps as $asp)
						<div class="article border-bottom">
							<div class="col-xs-12">
								<div class="row">
									<div class="col-xs-2 col-md-2 date">
										<div class="large"><?php echo $i; ?></div>
										
									</div>
									<div class="col-xs-8 col-md-8">
										<h4><a href="/asp_detail/{{ $asp -> id }}">{{ $asp -> name }}</a></h4>
										<p></p>

									</div>
									<div class="col-xs-2 col-md-2">
										<a href="{{ $asp -> login_url }}" target="_blank">
											<button type="button" class="btn btn-md btn-success">ログインURL</button>
										</a>
										

									</div>

								</div>
							</div>
							<div class="clear"></div>
							<?php $i = $i+1;?>
						</div>
					@endforeach
	</div>
</div>

@endsection