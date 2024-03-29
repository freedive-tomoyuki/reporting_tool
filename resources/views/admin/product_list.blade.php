
@extends('layouts.admin')

@section('content')
<div class="row">
       	<ol class="breadcrumb">
		  <li class="active">広告主管理</li>
		</ol>
      <div class="col-lg-12">
        <h1 class="page-header">広告主管理</h1>

        <div class="panel panel-default ">

        	<div class="panel-heading">広告主　一覧 
        			<button type="button" class="btn btn-md btn-info pull-right">
						<a href="{{ url('admin/register')}}" >新規広告主登録</a>
					</button>
			</div>
		        <div class="panel-body  articles-container">
					<?php $i = 1;?>

					@foreach($products_bases as $product)
						<?php //var_dump($product); ?>
						<div class="article border-bottom">
							<div class="col-xs-12">
								<div class="row">
									<div class="col-xs-2 col-md-2 date">
										<div class="large">{{ $product->id }}</div>
										
									</div>
									<div class="col-xs-8 col-md-8">
										<h4><a href="{{ url('admin/product_detail/'. $product->id) }}">{{ $product->product_name }}</a></h4>
										<span><b>Email</b>：{{ $product->email }}</span>
										<span><b>登録日</b>：{{ $product->created_at }}</span>
									</div>
									<div class="col-xs-2 col-md-2">
										<a href="{{ url('admin/product_base/edit/'. $product->id )}}">
											<button type="button" class="btn btn-md btn-info">設定</button>
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
		</div>
</div>
@endsection