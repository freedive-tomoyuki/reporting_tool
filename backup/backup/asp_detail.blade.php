
@extends('layouts.appnew')

@section('content')
<div class="row">
    <ol class="breadcrumb">
      <li><a href="/product_list">ASP管理</a></li>
      <li class="active">{{ $asp[0]->name }}　稼働案件一覧</li>
    </ol>
      <div class="col-lg-12">
        <h1 class="page-header">{{ $asp[0]->name }}　稼働案件一覧</h1>

        <div class="panel panel-default ">

        	<div class="panel-heading">{{ $asp[0]->name }}　稼働案件一覧</div>
		        <div class="panel-body  articles-container">
					<?php $i = 1;?>

					@foreach($products as $product)

						<div class="article border-bottom">
							<div class="col-xs-12">
								<div class="row">
									<div class="col-xs-2 col-md-2 date">
										<div class="large"><?php echo $i; ?></div>
										
									</div>
									<div class="col-xs-8 col-md-8">
										<h4><a href="/product_detail/{{ $product->product_base_id }}">{{ $product->product }}</a></h4>
										<p></p>

									</div>
									<div class="col-xs-2 col-md-2">
										<form action="/product_asp" method="get">
											<input type="hidden" name="asp_id" value="{{ $asp[0]->id }}"> 
											<input type="hidden" name="product_base_id" value="{{ $product->product_base_id }}"> 
											<button type="submit" class="btn btn-md btn-info">ID/PW編集</button>
										</form>
										
									</div>

								</div>
							</div>
							<div class="clear"></div>
							<?php $i = $i+1;?>
						</div>
					@endforeach
			</div>
		</div>
		</div>
</div>
@endsection