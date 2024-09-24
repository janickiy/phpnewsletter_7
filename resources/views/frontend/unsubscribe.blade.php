<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PHP Newsletter</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    {!! Html::style('/css/bootstrap.min.css') !!}

</head>
<body>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="row">
            <div class="col-sm-12">
                <div class="text-center error-box">
                    <h2 class="error-text-2 bounceInDown animated"> {{ trans('frontend.str.unsubscribe') }}
                        <span class="particle particle--c"></span>
                        <span class="particle particle--a"></span>
                        <span class="particle particle--b"></span>
                    </h2>
                    <h3 class="font-xl">
                        <strong>
                            <i class="fa fa-fw fa-warning fa-lg text-warning"></i>
                            {!! $msg !!}
                        </strong>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
