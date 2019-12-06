
@extends('layouts.admin')

@section('content')
<div class="">
       	<ol class="breadcrumb">
		  <li class="active">サイト管理</li>
		</ol>
        <h1 class="page-header">サイト管理</h1>

        <div class="panel panel-default ">
			<div class="panel-heading">サイト　一覧 </div>

		        <div class="panel-body  articles-container table-responsive">
					<form action="{{ url('admin/site_list') }}" method="post">
					@csrf
						<div class="row form-group col-md-10">
							<div class="col-md-6">
								<input type="text" id="site_name" name="site_name" placeholder="サイト名" class="form-control">
							</div>
							<div class="col-md-6">
							<select id="asp" name="asp" class="form-control" >
								{!! asp_options() !!}
								<select>
							</div>
						</div>
						<div class="form-group col-md-5 text-center">
							<input id="search" name="search" type="submit" class="form-control btn-info">
						</div>
					</form>
					<?php $i = 1;?>
					<div class="form-group col-md-12">
					<table id="dtBasicExample" class="table table-striped table-bordered table-sm">
						<thead>
							<th class="th-sm">ASP</th>
							<th class="th-sm">サイトID</th>
							<th class="th-sm">サイト名</th>
							<th class="th-sm">URL</th>
							<th class="th-sm">単価</th>
						</thead>
						<tbody>
							@foreach($sites as $site)
								<tr>
									<td>{{ $site->asp->name }}</td>
									<td>{{ $site->media_id }}</td>
									<td>{{ $site->site_name }}</td>
									<td>{{ $site->url }}</td>
									<td>{{ $site->unit_price }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					</div>
				</div>
			<div class="d-flex justify-content-center">
				{{ $sites->links() }}
			</div>
		</div>
</div>
@endsection