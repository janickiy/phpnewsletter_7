<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Newsletter | Авторизация</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    {!! Html::style('plugins/fontawesome-free/css/all.min.css') !!}

    <!-- icheck bootstrap -->
    {!! Html::style('plugins/icheck-bootstrap/icheck-bootstrap.min.css') !!}

    <!-- Theme style -->
    {!! Html::style('dist/css/adminlte.min.css') !!}


</head>
<body class="hold-transition login-page">
<div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <b>Admin</b>LTE
        </div>
        <div class="card-body">
            <p class="login-box-msg">Sign in to start your session</p>


            {!! Form::open(['url' => URL::route('login'), 'method' => 'post']) !!}


                <div class="input-group mb-3">

                    {!! Form::text('login', old('login'), [ 'placeholder' => 'логин', 'class' => 'form-control']) !!}

                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>

                    @if ($errors->has('login'))
                        <p class="text-danger">{{ $errors->first('login') }}</p>
                    @endif

                </div>
                <div class="input-group mb-3">

                    {!! Form::password('password',['class' => 'form-control', 'placeholder' => "Password", 'type' => 'password']) !!}

                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>

                    @if ($errors->has('password'))
                        <p class="text-danger">{{ $errors->first('password') }}</p>
                    @endif
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">

                            {!! Form::checkbox('remember', 1, old('remember') ? true : false , ['id' => "remember"]) !!}


                            <label for="remember">
                                Remember Me
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        {!! Form::submit('Войти', ['class' => 'btn btn-primary btn-block']) !!}
                    </div>
                    <!-- /.col -->
                </div>

            {!! Form::close() !!}




        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
{!! Html::script('plugins/jquery/jquery.min.js') !!}
<!-- Bootstrap 4 -->
{!! Html::script('plugins/bootstrap/js/bootstrap.bundle.min.js') !!}
<!-- AdminLTE App -->
{!! Html::script('dist/js/adminlte.min.js') !!}
</body>
</html>
