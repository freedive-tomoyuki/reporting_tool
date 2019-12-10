
@extends('layouts.admin')

@section('content')
<div class="row">
		<ol class="breadcrumb">
		<li><a href="{{ url('admin/daily_result')}}">月次管理</a></li>
		</ol>
      <div class="col-lg-12">
        	<h1 class="page-header">{{ $daily[0]->product->product_base_id }}　稼働案件一覧</h1>

			<div class="panel panel-default ">
				<div class="panel-heading">{{ $daily[0]->product->product }}　稼働案件一覧</div>
				<div class="panel-body  articles-container">
					<div class="table-responsive">
							<form  method="post" action="{{ url('/admin/daily/add') }}" >
							@csrf
								<input type="button" class="btn btn-info" v-on:click="post" value="追加">
								<table class="table table-striped table-bordered table-hover table-sm sp-wide-tabel">
									<thead>
										<th>対象日</th>
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
									</thead>
									<tbody>
									<tr>
										<td><input type="date" name="date0" class="form-control" v-bind:value="obj.date0" ></td>
										<td>
											<select class="form-control" name="asp0" @change="select_asp" >
												
												{!! asp_options() !!}
	
											</select>
										</td>
										<td><input type="text" id="imp0" name="imp0" v-bind:value="obj.imp0" class="form-control" ></td>
										<td><input type="text" id="ctr0" name="ctr0" v-bind:value="obj.ctr0" class="form-control" ></td>
										<td><input type="text" id="click0" name="click0" v-bind:value="obj.click0" class="form-control" ></td>
										<td><input type="text" id="cvr0" name="cvr0"  v-bind:value="obj.cvr0" class="form-control" ></td>
										<td><input type="text" id="cv0" name="cv0" v-bind:value="obj.cv0" class="form-control" ></td>
										<td><input type="text" id="active0" name="active0" v-bind:value="obj.active0" class="form-control" ></td>
										<td><input type="text" id="partner0" name="partner0" v-bind:value="obj.partner0" class="form-control"></td>
										<td><input type="text" id="cost0" name="cost0" v-bind:value="obj.cost0" class="form-control" ></td>
										<td><input type="text" id="price0" name="price0" v-bind:value="obj.price0" class="form-control"></td>
									</tr>
									</tbody>
								</table>
								<!-- <input type="hidden" v-bind:value="obj.asp0" value="0"> -->
								<input type="hidden" v-bind:value="obj.product0" name="product0">
							</form>
							<form action="{{ url('admin/daily/edit/'.$daily[0]->product->product_base_id ) }}" method="post" >
								@csrf
								<input type="submit" class="btn btn-success" value="編集" >
								<table class="table table-striped table-bordered table-hover table-sm sp-wide-tabel">
									<thead>
										<th>対象日</th>
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
										<th>削除</th>
									</thead>
									<tbody>
									@foreach($daily as $d)
										<tr>
											<td>{{ date('Y/m/d', strtotime($d->date)) }}</td>
											<td>{{ $d->asp->name }}</td>
											<td><input type="text" id="imp-{{ $d->id }}" name="imp{{ $d->id }}" class="form-control" value="{{ $d->imp }}" ></td>
											<td><input type="text" id="ctr-{{ $d->id }}" name="ctr{{ $d->id }}" class="form-control" value="{{ $d->ctr }}"></td>
											<td><input type="text" id="click-{{ $d->id }}" name="click{{ $d->id }}" class="form-control" value="{{ $d->click }}"></td>
											<td><input type="text" id="cvr-{{ $d->id }}" name="cvr{{ $d->id }}" class="form-control" value="{{ $d->cvr }}"></td>
											<td><input type="text" id="cv-{{ $d->id }}" name="cv{{ $d->id }}" class="form-control" value="{{ $d->cv }}"></td>
											<td><input type="text" id="active-{{ $d->id }}" name="active{{ $d->id }}" class="form-control" value="{{ $d->active }}"></td>
											<td><input type="text" id="partner-{{ $d->id }}" name="partner{{ $d->id }}" class="form-control" value="{{ $d->partner }}"></td>
											<td><input type="text" id="cost-{{ $d->id }}" name="cost{{ $d->id }}" class="form-control" value="{{ $d->cost }}"></td>
											<td><input type="text" id="price-{{ $d->id }}" name="price{{ $d->id }}" class="form-control" value="{{ $d->price }}"></td>
											<td><input type="checkbox" id="delete{{ $d->id }}" name="delete{{ $d->id }}" class="form-control" ></td>
										</tr>
									@endforeach
									</tbody>
								</table>
							
							</form>
						</div>		
					</div>
			</div>
		</div>
</div>
<!-- <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="https://unpkg.com/vue"></script>
<script src="https://unpkg.com/jquery"></script> -->
@endsection

@section('script')
<script>
		new Vue({
			el: '#app',
			data: {
				url : '',
				asp_option :[
					@foreach( $asps as $a )
						@php 
							echo '{id:'.$a->id.', name: "'.$a->name.'"},'; 
						@endphp
 					@endforeach
				],
				obj : {
					date0:'',
					asp0: '',
					imp0: '',   
					ctr0: '',   
					click0: '',   
					cvr0: '',   
					cv0: '',   
					active0: '',   
					partner0: '',   
					cost:'',
					price0:'',
				}
			},
			methods:{
				post: function(){
					config = {
						headers:{
							'X-Requested-With': 'XMLHttpRequest',
							'Content-Type':'application / x-www-form-urlencoded'
						},
						withCredentials:true,
					}
					// オブジェクトデータをJSON化
					var json = JSON.stringify( obj );
					//vueでバインドされた値はmethodの中ではthisで取得できる
					param = JSON.parse(json);

					axios.post(this.url,param,config)
					.then(function(res){
						//vueにバインドされている値を書き換えると表示に反映される
						app.result = res.data
						console.log(res)
					})
					.catch(function(res){
						//vueにバインドされている値を書き換えると表示に反映される
						app.result = res.data
						console.log(res)
					})
				},
				select_asp: function(e){
					// this.obj.asp0 = 
					console.log(e);
				}
			}
		});
		
	</script>
@endsection