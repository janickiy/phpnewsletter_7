<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>PHP Newsletter | {{ trans('install.str.installation') }}</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap Core CSS -->

    {!! Html::style('/css/bootstrap.min.css') !!}

    {!! Html::style('/css/style.css') !!}

    {!! Html::style('/css/vendor.css') !!}

    {!! HTML::style('/css/install.css') !!}

    {!! Html::style('/plugins/bootstrap-select/css/bootstrap-select.min.css') !!}

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    {!! Html::style('/plugins/fontawesome-free/css/all.min.css') !!}

    @yield('css')

    <script type="text/javascript">
        var SITE_URL = "{{ url('/') }}";
    </script>

</head>
<body>

<div class="container-fluid">

    <div class="row">
        <div class="col-md-6 offset-3">
            <div class="pull-right form-group">
                <select id="lang" class="selectpicker">
                    <option value="ru" {{ Config::get('app.locale') == 'ru' ? 'selected="selected"':'' }}>Русский (Russian)</option>
                    <option value="en" {{ Config::get('app.locale') == 'en' ? 'selected="selected"':'' }}>English</option>
                </select>
            </div>
        </div>

        <div class="col-md-6 offset-3 logo-wrapper">
            <img src="{{ url('../../dist/img/logo.png') }}" alt="PHP Newsletter" class="logo">
        </div>
    </div>
    <div class="wizard col-md-6 offset-3">

        @yield('content')

    </div>
</div>

<!-- jQuery -->
{!! Html::script('/plugins/jquery/jquery.min.js') !!}

<!-- Bootstrap 4 -->
{!! Html::script('/plugins/bootstrap/js/bootstrap.bundle.min.js') !!}

{!! Html::script('/plugins/bootstrap-select/js/bootstrap-select.min.js') !!}

@yield('js')

<script>

    $(document).ready(function () {
        $('.selectpicker').selectpicker();

        $('#lang').on('change', function () {
            let Lng = $(this).val();

            let request = $.ajax({
                url: '{{ route('install.ajax.action') }}',
                method: "POST",
                data: {
                    action: "change_lng",
                    locale: Lng,
                },
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                dataType: "json"
            });

            request.done(function (data) {
                if (data.result != null && data.result === true) {
                    location.reload();
                }
            });
        });
    });

</script>

</body>
</html>
