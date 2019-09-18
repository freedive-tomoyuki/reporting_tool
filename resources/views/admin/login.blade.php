@extends('app')

@section('content')

    <div id="login">
        <h3 class="text-center text-white pt-5">Admin Login form</h3>
        <div class="container">
            <div id="login-row" class="row justify-content-center align-items-center">
                <div id="login-column" class="col-md-6">
                    <div id="login-box" class="col-md-12">
                        <form id="login-form" class="form" action="{{ route('admin.login') }}" method="post">
                            <h3 class="text-center text-info">Login</h3>
                            @csrf
                            <div class="form-group">
                                <label for="username" class="text-info">Username:</label><br>
                                <!-- <input type="text" name="username" id="username" class="form-control"> -->
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="password" class="text-info">Password:</label><br>
                                <!-- <input type="text" name="password" id="password" class="form-control"> -->
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="remember" class="text-info">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>Remember me</label><br>
                                <!-- <input id="remember-me" name="remember-me" type="checkbox"> -->
                                        
                                <input type="submit" name="submit" class="btn btn-info btn-md" value="{{ __('Login') }}">
                            </div>
                            <div id="register-link" class="text-right">
                                <!-- <a class="text-info" href="#" >Register here</a> -->
                                <a class="text-info" href="{{ route('password.request') }}">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script type="text/javascript">
    let target = document.getElementById("body");
    target.className = "admin";
</script>
@endsection
