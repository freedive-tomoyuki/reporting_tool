
@extends('layouts.appnew')

@section('content')
<div class="row">
       	<ol class="breadcrumb">
		  <li><a href="/product_list">広告主管理</a></li>
		  <li class="active">案件一覧</li>
		</ol>
      <div class="col-lg-12">

        <h1 class="page-header">案件一覧</h1>
        <div class="panel panel-default ">

        	<div class="panel-heading">稼働中ASP　一覧
           			<button type="button" class="btn btn-md btn-info pull-right">
						<a href="/product/add" >新規登録</a>
					</button></div>
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
										<h4>{{ $asp->product_name }} /<a href="/asp_detail/{{ $asp->id }}"> {{ $asp->name }}</a></h4>
										<p></p>

									</div>
									<div class="col-xs-2 col-md-2">
										<a href="/product/edit/{{ $asp->products_id }}">
											<button type="button" class="btn btn-md btn-info">ID/PW編集</button>
										</a>
										<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#demoNormalModal{{ $asp->id }}">
									    削除
										</button>
									</div>
									<div class="modal fade" id="demoNormalModal{{ $asp->id }}" tabindex="-1" role="dialog" aria-labelledby="modal" aria-hidden="true">
									    <div class="modal-dialog" role="document">
									        <div class="modal-content">
									            <div class="modal-header">
									                <h5 class="modal-title" id="demoModalTitle">タイトル</h5>
									                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
									                    <span aria-hidden="true">&times;</span>
									                </button>
									            </div>
									            <div class="modal-body">
									                削除してよろしいでしょうか。
									            </div>
									            <div class="modal-footer">
									                <button type="button" class="btn btn-secondary" data-dismiss="modal">
										                	閉じる
													</button>
									                <button type="button" class="btn btn-primary">
										                <a href="/product_delete/{{ $asp->product_base_id }}/{{ $asp->id }}">
										                ボタン
										            	</a>
									            	</button>
									            </div>
									        </div>
									    </div>
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