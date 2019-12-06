
@extends('layouts.admin')

@section('content')
<div class="row">
		<ol class="breadcrumb">
		<li><a href="{{ url('admin/monthly_result')}}">月次管理</a></li>
		</ol>
      <div class="col-lg-12">
        	<h1 class="page-header">{{ $monthly[0]->product->product_base_id }}　稼働案件一覧</h1>

			<div class="panel panel-default ">
			<form action="{{ url('admin/monthly/edit/'.$monthly[0]->product->product_base_id ) }}" method="post" >
				<div class="panel-heading">{{ $monthly[0]->product->product }}　稼働案件一覧<input type="submit" class="btn btn-success" value="編集" ></div>
					<div class="panel-body  articles-container">
						<div class="table-responsive">
							
							@csrf
							
							<table class="table table-striped table-bordered table-hover table-sm sp-wide-tabel">
								<thead>
									<th>対象月</th>
									<th>ASP名</th>
									<th>Imp</th>
									<th>CTR</th>
									<th>Click</th>
									<th>CVR</th>
									<th>Cv</th>
									<th>アクティブ数</th>
									<th>提携数</th>
									<th>ASP<br>グロス</th>
									<th>FREEDiVE<br>グロス</th>
									<th>承認件数</th>
									<th>承認金額</th>
									<th>承認率</th>
									<th>削除</th>
								</thead>
								<tbody>
								@foreach($monthly as $m)
									<tr>
										<td>{{ date('Y年m月', strtotime($m->date)) }}</td>
										<td>{{ $m->asp->name }}</td>
										<td><input type="text" id="imp-{{ $m->id }}" name="imp{{ $m->id }}" class="form-control" value="{{ $m->imp }}" ></td>
										<td><input type="text" id="ctr-{{ $m->id }}" name="ctr{{ $m->id }}" class="form-control" value="{{ $m->ctr }}"></td>
										<td><input type="text" id="click-{{ $m->id }}" name="click{{ $m->id }}" class="form-control" value="{{ $m->click }}"></td>
										<td><input type="text" id="cvr-{{ $m->id }}" name="cvr{{ $m->id }}" class="form-control" value="{{ $m->cvr }}"></td>
										<td><input type="text" id="cv-{{ $m->id }}" name="cv{{ $m->id }}" class="form-control" value="{{ $m->cv }}"></td>
										<td><input type="text" id="active-{{ $m->id }}" name="active{{ $m->id }}" class="form-control" value="{{ $m->active }}"></td>
										<td><input type="text" id="partner-{{ $m->id }}" name="partner{{ $m->id }}" class="form-control" value="{{ $m->partner }}"></td>
										<td><input type="text" id="cost-{{ $m->id }}" name="cost{{ $m->id }}" class="form-control" value="{{ $m->cost }}"></td>
										<td><input type="text" id="price-{{ $m->id }}" name="price{{ $m->id }}" class="form-control" value="{{ $m->price }}"></td>
										<td><input type="text" id="approval-{{ $m->id }}" name="approval{{ $m->id }}" class="form-control" value="{{ $m->approval }}"></td>
										<td><input type="text" id="approval_price-{{ $m->id }}" name="approval_price{{ $m->id }}" class="form-control" value="{{ $m->approval_proce }}"></td>
										<td><input type="text" id="approval_rate-{{ $m->id }}" name="approval_rate{{ $m->id }}" class="form-control" value="{{ $m->approval_rate }}"></td>
										<td><input type="checkbox" id="delete{{ $m->id }}" name="delete{{ $m->id }}" class="form-control" ></td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>		
					</div>
				</form>
			</div>
		</div>
</div>
@endsection