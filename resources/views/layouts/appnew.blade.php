<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Report Tool</title>
	<link rel="shortcut icon" href="/img/favicon/favicon.ico">
	<link href="/css/bootstrap.min.css" rel="stylesheet">
	<!-- <link href="css/font-awesome.min.css" rel="stylesheet"> -->
	<link href="/css/datepicker3.css" rel="stylesheet">
	<link href="/css/styles.css" rel="stylesheet">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

	<!--Custom Font-->
	<link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

	<!--[if lt IE 9]>
	<script src="js/html5shiv.js"></script>
	<script src="js/respond.min.js"></script>
	<![endif]-->
	<!-- MDBootstrap Datatables  -->
	<link href="/css/addons/datatables.min.css" rel="stylesheet">

	<style type="text/css">
	@media screen and (max-width: 768px) {
	  .sp-small{
		font-size: 85%;
	  }
	  .sp-wide-tabel{
	  	min-width: 1500px;
	  }

	}
	  .date-style{
	  	width: 49%;
	  	display: inline-block;
	  }
	</style>

</head>
<body>
	<nav class="navbar navbar-custom navbar-fixed-top" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#sidebar-collapse"><span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span></button>
				<a class="navbar-brand" href="#"><span>Report Tool</span></a>

			</div>
		</div><!-- /.container-fluid -->
	</nav>
	<div id="sidebar-collapse" class="col-sm-3 col-lg-2 sidebar">
		<div class="profile-sidebar">
			<div class="profile-userpic">
				<img src="/img/template/FREEDIVE_logo.jpg" class="img-responsive" alt="">
			</div>
			<div class="profile-usertitle">
				<div class="profile-usertitle-name">{{ $user->name }}</div>
				<div class="profile-usertitle-status"><span class="indicator label-success"></span>Online</div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="divider"></div>

		<ul class="nav menu">
			<!--<li 
			@if( Request::path() == "admin/daily_report" )
				class="active"
			@endif
			><a href="{{ route('admin.crawlerdaily') }}"><em class="fa fa-search">&nbsp;</em> 実装(※手動禁止)</a></li>-->

			<li class="parent "><a href="/admin/daily_result" data-toggle="collapse" >
				<em class="far fa-chart-bar">&nbsp;</em> レポート<span data-toggle="collapse" href="#sub-item-1" class="icon pull-right"><em class="fa fa-plus"></em></span>
				</a>
				<ul class="children collapse in" id="sub-item-1">
					<li
					@if(strpos( Request::path() , "yearly_result" ) !== false )
						class="active"
					@endif><a class="" href="">
						<span class="fas fa-angle-right">&nbsp;</span> 年間
					</a></li>
					<li 
					@if(strpos( Request::path() , "daily_result" )!== false)
						class="active"
					@endif><a class="" href="/admin/daily_result">
						<span class="fas fa-angle-right">&nbsp;</span> 日次（案件別）
					</a></li>
					<li 
					@if( strpos( Request::path() ,"daily_result") !== false && strpos( Request::path() ,'site') !== false )
						class="active"
					@endif><a class="" href="/admin/daily_result_site">
						<span class="fas fa-angle-right">&nbsp;</span> 日次（サイト別）
					</a></li>
					<li
					@if( strpos(Request::path() , "monthly_result" )!== false)
						class="active"
					@endif><a class="" href="/admin/monthly_result">
						<span class="fas fa-angle-right">&nbsp;</span> 月次（案件別）
					</a></li>
					<li
					@if(strpos( Request::path() ,"monthly_result") !== false && strpos( Request::path() ,'site') !== false )
						class="active"
					@endif><a class="" href="/admin/monthly_result_site">
						<span class="fas fa-angle-right">&nbsp;</span> 月次（サイト）
					</a></li>

				</ul>
			</li>
			<li 
			@if( strpos( Request::path() ,'admin/product') !== false)
				class="active"
			@endif
			><a href="{{ route('admin.product_list') }}"><em class="fas fa-users">&nbsp;</em> 広告主管理</a></li>
			<li 
			@if( strpos( Request::path() ,'admin/asp') !== false )
				class="active"
			@endif
			><a href="{{ route('admin.asp_list') }}"><em class="fab fa-adn">&nbsp;</em> ASP管理</a></li>
			<li 
			@if( strpos( Request::path() ,'csv/import') !== false)
				class="active"
			@endif
			><a href="{{ route('admin.csv.import') }}"><em class="fas fa-file-import">&nbsp;</em> インポート</a></li>
			<li 
			@if( strpos( Request::path() ,'export') !== false)
				class="active"
			@endif
			><a href="{{ route('admin.csv.export') }}"><em class="fas fa-file-export">&nbsp;</em> エクスポート</a></li>
			<li
			@if( Request::path() == "admin/register" )
				class="active"
			@endif
			><a href="{{ route('admin.register') }}"><em class="fa fa-user">&nbsp;</em> 新規登録</a></li>
			<li>
				<a class="dropdown-item" href="{{ route('admin.logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <em class="fa fa-power-off">&nbsp;</em>
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
		</ul>
	</div><!--/.sidebar-->
		
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
		@yield('content')
	</div>	<!--/.main-->
	
	<script src="/js/jquery-1.11.1.min.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/chart.min.js"></script>
	<script src="/js/chart-data.js"></script>
	<script src="/js/easypiechart.js"></script>
	<script src="/js/easypiechart-data.js"></script>
	<script src="/js/bootstrap-datepicker.js"></script>
	<script src="/js/custom.js"></script>
	<script src="/js/addons/datatables.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.13/dist/vue.min.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script>
		var ComponentA = {
		    template: "<font style='color:red'>*</font>",
		}
		var ComponentB = {
		    template: "<font style='color:red'>*</font>",
		}
        new Vue({
			el: '#app',
			data: {
	            selected: '',
	            show: false,
	            show1: false,
	            any: true,
	            required: false,
	        },
	        components: {
		      'component_sponsor': ComponentA,
		      'component_product': ComponentB,
		    },
			methods: {
	    		switchAsp:function() {
	                var id = this.selected ;
				    //console.log(id);
	    			axios.get('/api/getRequiredFlag/' + id).then((res)=>{
	    				if(res.data[0]['sponsor_id_require_flag'] == 1 ){
	    					this.show = true;
	    					this.any = false;
	    					this.required = true;
	    				}else{
	    					this.show = false;
	    					this.any = true;
	    					this.required = false;
	    				}
	    				if(res.data[0]['product_id_require_flag'] == 1 ){
	    					this.show1 = true;
	    					this.any = false;
	    					this.required = true;
	    				}else{
	    					this.show1 = false;
	    					this.any = true;
	    					this.required = false;
	    				}
                })
                    .catch(error => { 
                    	console.log(error)
                    })
                    .then(response => { 
						console.log(response)
					})
	    		}
	    	}
        })
    </script>
	<script>
		/*window.onload = function () {
			var chart1 = document.getElementById("line-chart").getContext("2d");
			window.myLine = new Chart(chart1).Line(lineChartData, {
			responsive: true,
			scaleLineColor: "rgba(0,0,0,.2)",
			scaleGridLineColor: "rgba(0,0,0,.05)",
			scaleFontColor: "#c5c7cc"
			});
		};*/
	</script>
	<script type="text/javascript">
	  $(document).ready(function () {
	    $('#dtBasicExample').DataTable();
	    $('.dataTables_length').addClass('bs-select');
	  });
	</script>
		
</body>
</html>