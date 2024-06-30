<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PHP Newsletter | @yield('title')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    {!! Html::style('/css/bootstrap.min.css') !!}

    @yield('css')

</head>
<body>
<div class="container">

    @include('layouts.notifications')

    @yield('content')

</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>

    if (!window.jQuery) {
        document.write('<script src="{{ url('/js/libs/jquery-3.2.1.min.js') }}"><\/script>');
    }

</script>

<!-- BOOTSTRAP JS -->

{!! Html::script('/admin/js/bootstrap/bootstrap.min.js') !!}

@yield('js')

</body>

</html>
