
@extends('layouts.admin')

@section('content')
<div class="row">
		<ol class="breadcrumb">
		<li><a href="{{ url('admin/monthly_result')}}">月次管理（サイト別）</a></li>
		</ol>
      <div class="col-lg-12">
        	<h1 class="page-header">{{ $products[0]->product }} | 稼働案件一覧</h1>
			@if (count($errors) > 0)
			@foreach ($errors->all() as $error)
			<div class="alert bg-danger" role="alert"><em class="fa fa-lg fa-warning">&nbsp;</em>{{ $error }}</div>
            @endforeach
			@endif
			<div class="panel panel-default ">
				<form method="get" action="{{ url('/admin/monthly/site/edit/'. $products[0]->product_base_id  ) }}" >
				<div class="panel-heading">　絞り込み　</div>
					<div class="panel-body articles-container">
						<div class="form-group col-md-12">
							<div class="col-md-4 col-sm-4">
								<input type="month" name="search_date" class="form-control" value="{{ old('search_date') }}">
							</div>	
							<div class="col-md-4 col-sm-4">
								<input type="text" name="site_name" class="form-control" value="{{ old('site_name') }}" placeholder="サイト名を入力してくだい。" >
							</div>						
							<div class="col-md-4 col-sm-4">
								<select class="form-control" name="search_asp" >
									<option value=''>-- ASP --</option>
									@foreach($asps as $a)
										@if( old('search_asp') && $a['id'] == old('search_asp') )
											<option value='{{ $a["id"] }}' selected>{{ $a["name"] }}</option>
										@else	
											<option value='{{ $a["id"] }}'>{{ $a["name"] }}</option>
										@endif
									@endforeach
								</select>
							</div>	
						</div>
						<div class="col-md-10 col-sm-10">
								<input type="submit" class="btn btn-info " value="絞り込み">
						</div>
					</div>
				</form>
			</div>
			<div class="panel panel-default ">
				<form method="post" action="{{ url('/admin/monthly/site/add') }}" >
				<div class="panel-heading">　月次データ追加　<input type="submit" class="btn btn-info pull-right" value="追加"></div>
				<div class="panel-body articles-container">
					<div class="table-responsive">
							@csrf
								<table class="table table-striped table-bordered table-hover table-sm sp-wide-table">
									<thead>
										<th>対象月</th>
										<th>ASP名</th>
										<th>サイト名</th>
										<th>Imp</th>
										<th>CTR</th>
										<th>Click</th>
										<th>CVR</th>
										<th>Cv</th>
										<th>ASP<br>グロス</th>
										<th>FREEDiVE<br>グロス</th>
										<th>承認件数</th>
										<th>承認金額</th>
										<th>承認率</th>
									</thead>
									<tbody>
										<tr>
											<td><input type="month" name="date[0]" class="form-control" value="{{ old('date.0') }}"></td>
											<td>
												<select class="form-control" name="asp[0]"  >
													<option value=''>-- ASP --</option>
													@foreach($asps as $a)
														<option value='{{ $a["id"] }}'>{{ $a["name"] }}</option>
													@endforeach
												</select>
											</td>
											<td>
												<select class="form-control" name="sites[0]"  >
													<option value=''>-- サイト名 --</option>
													@foreach($sites as $s)
														<option value='{{ $s["id"] }}'>{{ $s["name"] }}</option>
													@endforeach
												</select>
											</td>
											<td><input type="text" id="imp0" name="imp[0]" class="form-control" value="{{ old('imp.0') }}" ></td>
											<td><input type="text" id="ctr0" name="ctr[0]" class="form-control" value="{{ old('ctr.0') }}" ></td>
											<td><input type="text" id="click0" name="click[0]" class="form-control" value="{{ old('click.0') }}" ></td>
											<td><input type="text" id="cvr0" name="cvr[0]" class="form-control" value="{{ old('cvr.0') }}" ></td>
											<td><input type="text" id="cv0" name="cv[0]" class="form-control" value="{{ old('cv.0') }}" ></td>
											<td><input type="text" id="cost0" name="cost[0]" class="form-control" value="{{ old('cost.0') }}" ></td>
											<td><input type="text" id="price0" name="price[0]" class="form-control"  value="{{ old('price.0') }}"></td>
											<td><input type="text" id="approval0" name="approval[0]" class="form-control" value="{{ old('approval.0') }}"></td>
											<td><input type="text" id="approval_price0" name="approval_price[0]" class="form-control" value="{{ old('approval_price.0') }}"></td>
											<td><input type="text" id="approval_rate0" name="approval_rate[0]" class="form-control" value="{{ old('approval_rate.0') }}"></td>
										</tr>
									</tbody>
								</table>
								<!-- <input type="hidden" v-bind:value="obj.asp0" value="0"> -->
								<input type="hidden"  name="product[0]" value="{{ $products[0]->product_base_id }}">
							</div>		
						</div>
					</form>
			</div>	
			@if(!$monthly_sites->isEmpty())
			<div class="panel panel-default ">
			<form action="{{ url('admin/monthly/site/update/'. $products[0]->product_base_id ) }}" method="post" >
				<div class="panel-heading">　月次データ修正<input type="submit" class="btn btn-success pull-right" value="編集" ></div>
					<div class="panel-body  articles-container">
						<div class="table-responsive">
							
							@csrf
							<input type="hidden" value="{{ $selected_month }}" name="month">
							<input type="hidden" value="{{ $selected_asp }}" name="asp">
							<table class="table table-striped table-bordered table-hover table-sm sp-wide-tabel">
								<thead>
									<th>メディアID</th>
									<th>サイト名</th>
									<th>Imp</th>
									<th>CTR</th>
									<th>Click</th>
									<th>CVR</th>
									<th>Cv</th>
									<th>ASP<br>グロス</th>
									<th>FREEDiVE<br>グロス</th>
									<th>承認件数</th>
									<th>承認金額</th>
									<th>承認率</th>
									<th>削除</th>
								</thead>
								<tbody>
								@if(isset($monthly_sites))
									@foreach($monthly_sites as $s)
										<tr>
											@php $str = hash('md5', $s->id) @endphp
											<td><input type="text" id="media_id-{{ $str}}" name="media_id[{{ $str }}]" class="form-control" value="{{ $s->media_id }}" ></td>
											<td><input type="text" id="site_name-{{ $str}}" name="site_name[{{ $str }}]" class="form-control" value="{{ $s->site_name }}"></td>
											
											<td><input type="text" id="imp-{{ $str}}" name="imp[{{ $str }}]" class="form-control" value="{{ $s->imp }}" ></td>
											<td><input type="text" id="ctr-{{ $str}}" name="ctr[{{ $str }}]" class="form-control" value="{{ $s->ctr }}"></td>
											<td><input type="text" id="click-{{ $str}}" name="click[{{ $str }}]" class="form-control" value="{{ $s->click }}"></td>
											<td><input type="text" id="cvr-{{ $str}}" name="cvr[{{ $str }}]" class="form-control" value="{{ $s->cvr }}"></td>
											<td><input type="text" id="cv-{{ $str}}" name="cv[{{ $str }}]" class="form-control" value="{{ $s->cv }}"></td>
											<td><input type="text" id="cost-{{ $str}}" name="cost[{{ $str }}]" class="form-control" value="{{ $s->cost }}"></td>
											<td><input type="text" id="price-{{ $str}}" name="price[{{ $str }}]" class="form-control" value="{{ $s->price }}"></td>
											<td><input type="text" id="approval-{{ $str}}" name="approval[{{ $str }}]" class="form-control" value="{{ $s->approval }}"></td>
											<td><input type="text" id="approval_price-{{ $str}}" name="approval_price[{{ $str }}]" class="form-control" value="{{ $s->approval_price }}"></td>
											<td><input type="text" id="approval_rate-{{ $str}}" name="approval_rate[{{ $str }}]" class="form-control" value="{{ $s->approval_rate }}"></td>
											<td><input type="checkbox" id="delete{{ $str}}" name="delete[{{ $str }}]" class="form-control" ></td>
										</tr>
									@endforeach
								@endif
								
								</tbody>
							</table>
						</div>		
					</div>
				</form>
			</div>
			@endif
		</div>
</div>
@endsection

@section('script')
<script>
</script>
@endsection