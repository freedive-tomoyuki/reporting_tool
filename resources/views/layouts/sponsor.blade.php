<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Report Tool</title>
	<link rel="shortcut icon" href="{{ asset('img/favicon/favicon.ico')}}">
	<link href="{{ asset('css/bootstrap.min.css')}}" rel="stylesheet">
	<!-- <link href="css/font-awesome.min.css" rel="stylesheet"> -->
	<link href="{{ asset('css/datepicker3.css')}}" rel="stylesheet">
	<link href="{{ asset('css/styles.css')}}" rel="stylesheet">
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
	<link href="{{ url('css/addons/datatables.min.css')}}" rel="stylesheet">

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
				<img src="http://placehold.it/50/30a5ff/fff" class="img-responsive" alt="">
			</div>
			<div class="profile-usertitle">
				<div class="profile-usertitle-name">{{$user->name}}</div>
				<div class="profile-usertitle-status"><span class="indicator label-success"></span>Online</div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="divider"></div>

		<ul class="nav menu">
			<li class="parent "><a  data-toggle="collapse" >
				<em class="far fa-chart-bar">&nbsp;</em> レポート<span data-toggle="collapse" href="#sub-item-1" class="icon pull-right"><em class="fa fa-plus"></em></span>
				</a>
				<ul class="children collapse in" id="sub-item-1">
					<li
					@if(strpos( Request::path() , "yearly_result" ) !== false )
						class="active"
					@endif><a class="" href="{{ url('yearly_result')}}">
						<span class="fas fa-angle-right">&nbsp;</span> 年間
					</a></li>
					<li 
					@if(strpos( Request::path() , "daily_result" ) !== false )
						class="active"
					@endif><a class="" href="{{ url('daily_result')}}">
						<span class="fa fa-angle-right">&nbsp;</span> 日次（案件別）
					</a></li>
					<li
					@if(strpos( Request::path() , "daily_result_site" ) !== false )
						class="active"
					@endif><a class="" href="{{ url('daily_result_site')}}">
						<span class="fa fa-angle-right">&nbsp;</span> 日次（サイト別）
					</a></li>
					<li
					@if(strpos( Request::path() , "monthly_result" ) !== false )
						class="active"
					@endif><a class="" href="{{ url('monthly_result')}}">
						<span class="fa fa-angle-right">&nbsp;</span> 月次（案件別）
					</a></li>
					<li
					@if(strpos( Request::path() , "monthly_result_site" ) !== false )
						class="active"
					@endif><a class="" href="{{ url('monthly_result_site')}}">
						<span class="fa fa-angle-right">&nbsp;</span> 月次（サイト）
					</a></li>
				</ul>
			</li>
			<li 
			@if( strpos( Request::path() ,'export') !== false)
				class="active"
			@endif
			><a href="{{ route('csv.export') }}"><em class="fas fa-file-export">&nbsp;</em> エクスポート</a></li>
			
			<li>
				<a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <em class="fa fa-power-off">&nbsp;</em>
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
            </li>
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