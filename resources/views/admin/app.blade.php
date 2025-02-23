<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('frontend.str.admin_panel') }} | @yield('title')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    {!! Html::style('/plugins/fontawesome-free/css/all.min.css') !!}

    {!! Html::style('/plugins/sweetalert2/sweetalert2.min.css') !!}

    <!-- Theme style -->
    {!! Html::style('/dist/css/adminlte.min.css') !!}

    {!! Html::style('/plugins/toastr/toastr.min.css') !!}

    {!! Html::style('/plugins/flag-icon-css/css/flag-icon.min.css') !!}

    <!-- Custom style -->
    {!! Html::style('/dist/css/admin.css?v=3') !!}

    @yield('css')

    <script type="text/javascript">
        let SITE_URL = "{{ URL::to('/') }}";
    </script>
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" title="{{ trans('frontend.str.expand_full_screen') }}"
                   href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">

                @if( Config::get('app.locale') == 'ru')
                    <a class="nav-link" data-toggle="dropdown" href="javascript:void(0);">
                        <i class="flag-icon flag-icon-ru"></i>
                    </a>
                @else
                    <a class="nav-link" data-toggle="dropdown" href="javascript:void(0);">
                        <i class="flag-icon flag-icon-us"></i>
                    </a>
                @endif

                <div class="dropdown-menu dropdown-menu-right p-0">
                    <a data-id="en" href="javascript:void(0);" class="dropdown-item select-lang ">
                        <i class="flag-icon flag-icon-us mr-2"></i> English
                    </a>
                    <a data-id="ru" href="javascript:void(0);" class="dropdown-item select-lang " alt="Русский (Russian)">
                        <i class="flag-icon flag-icon-ru mr-2"></i> Русский (Russian)
                    </a>
                </div>
            </li>

            <!-- Notifications Dropdown Menu -->
            <li class="nav-item">
                <a class="nav-link" title="{{ trans('frontend.str.signout') }}" href="{{ route('logout') }}"
                   role="button">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('admin.templates.index') }}" class="brand-link">
            <img src="{{ url('/dist/img/logo.png') }}" alt="PHP Newsletter" class="brand-image">
            <span style="font-size: 0.7rem">{{ env('VERSION') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="{{ route('admin.users.edit', ['id' => Auth::user()->id ]) }}"
                       class="d-block">{{ Auth::user()->login }} @if(!empty(Auth::user()->name))
                            ({{ Auth::user()->name }})
                        @endif</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->

                    <li class="nav-item">
                        <a href="{{ route('admin.templates.index') }}" class="nav-link{{ Request::is('template*') || Request::is('/') ? ' active' : '' }}"
                           title="{{ trans('frontend.menu.templates') }}">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>{{ trans('frontend.menu.templates') }}</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission('admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ route('admin.subscribers.index') }}" class="nav-link{{ Request::is('subscribers*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.subscribers') }}">
                                <i class="nav-icon fas fa-user-friends"></i>
                                <p>{{ trans('frontend.menu.subscribers') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.macros.index') }}" class="nav-link{{ Request::is('macros*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.macros') }}">
                                <i class="nav-icon fas fa-scroll"></i>
                                <p>{{ trans('frontend.menu.macros') }}</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item">
                        <a href="{{ route('admin.schedule.index') }}" class="nav-link{{ Request::is('schedule*') ? ' active' : '' }}"
                           title="{{ trans('frontend.menu.schedule') }}">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>{{ trans('frontend.menu.schedule') }}</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission('admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ route('admin.category.index') }}" class="nav-link{{ Request::is('category*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.subscribers') }}">
                                <i class="nav-icon fas fa-list"></i>
                                <p>{{ trans('frontend.menu.subscribers_category') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission('admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.smtp.index') }}" class="nav-link{{ Request::is('smtp*') ? ' active' : '' }}" title="SMTP">
                                <i class="nav-icon fas fa-inbox"></i>
                                <p>SMTP</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item{{ Request::is('log*') || Request::is('redirect*') ? ' menu-open' : '' }}">
                        <a href="#" class="nav-link{{ Request::is('log*') || Request::is('redirect*') ? ' active' : '' }}" title="{{ trans('frontend.menu.logs') }}">
                            <i class="nav-icon fas fa-chart-area"></i>
                            <p>
                                {{ trans('frontend.menu.logs') }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('admin.log.index') }}" class="nav-link{{ Request::is('log*') ? ' active' : '' }}"
                                   title="{{ trans('frontend.menu.mailing_log') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ trans('frontend.menu.mailing_log') }}</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.redirect.index') }}" class="nav-link{{ Request::is('redirect*') ? ' active' : '' }}"
                                   title="{{ trans('frontend.menu.referrens_log') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ trans('frontend.menu.referrens_log') }}</p>
                                </a>
                            </li>

                        </ul>
                    </li>

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}" class="nav-link{{  Request::is('settings*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.settings') }}">
                                <i class="nav-icon fa fa-cogs"></i>
                                <p>{{ trans('frontend.menu.settings') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link{{ Request::is('users*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.users') }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>{{ trans('frontend.menu.users') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.update.index') }}" class="nav-link{{ Request::is('update*') ? ' active' : '' }}" title="{{ trans('frontend.menu.update') }}">
                                <i class="nav-icon fas fa-sync-alt"></i>
                                <p>{{ trans('frontend.menu.update') }}</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item">
                        <a href="{{ route('admin.pages.faq') }}" class="nav-link{{ Request::is('faq*') ? ' active' : '' }}" title="FAQ">
                            <i class="nav-icon fas fa-question-circle"></i>
                            <p>FAQ</p>
                        </a>
                    </li>

                    <li class="nav-item{{ Request::is('pages*') ? ' menu-open' : '' }}">
                        <a href="#" class="nav-link{{ Request::is('pages*') ? ' active' : '' }}" title="{{ trans('frontend.menu.miscellaneous') }}">
                            <i class="nav-icon fas fa-bookmark"></i>
                            <p>
                                {{ trans('frontend.menu.miscellaneous') }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('admin.pages.subscription_form') }}" class="nav-link{{ Request::is('pages/subscription-form*') ? ' active' : '' }}"
                                   title="{{ trans('frontend.menu.subscription_form') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ trans('frontend.menu.subscription_form') }}</p>
                                </a>
                            </li>

                            @if(PermissionsHelper::has_permission(Auth::user()->role,'admin|moderator'))

                                <li class="nav-item">
                                    <a href="{{ route('admin.pages.cron_job_list') }}" class="nav-link{{ Request::is('pages/cron-job-list*') ? ' active' : '' }}"
                                       title="{{ trans('frontend.menu.cron_job_list') }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>{{ trans('frontend.menu.cron_job_list') }}</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('admin.pages.phpinfo') }}" class="nav-link{{ Request::is('pages/phpinfo*') ? ' active' : '' }}" title="PHP Info">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>PHP Info</p>
                                    </a>
                                </li>

                            @endif

                        </ul>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>{{ $title }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ trans('frontend.str.admin_panel') }}</a></li>
                            <li class="breadcrumb-item active">{{ $title }}</li>
                        </ol>
                    </div>
                </div>

                @include('admin.notifications')

            </div><!-- /.container-fluid -->
        </section>

        @yield('content')

    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <strong>&copy; 2006-{{ date('Y') }} <a href="https://janickiy.com">PHP Newsletter</a>, {{ trans('frontend.str.author') }}</strong>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
{!! Html::script('/plugins/jquery/jquery.min.js') !!}
<!-- Bootstrap 4 -->
{!! Html::script('/plugins/bootstrap/js/bootstrap.bundle.min.js') !!}

{!! Html::script('/plugins/sweetalert2/sweetalert2.min.js') !!}
{!! Html::script('/plugins/toastr/toastr.min.js') !!}

<!-- Cookie -->
{!! Html::script('/plugins/cookie/jquery.cookie.js') !!}

<!-- AdminLTE App -->
{!! Html::script('/dist/js/adminlte.min.js') !!}

<script>

    $(function () {
        $.ajax({
            cache: false,
            url: '{{ route('admin.ajax.action') }}',
            method: "POST",
            data: {
                action: "alert_update",
            },
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            dataType: "json",
            success: function (data) {
                if (data.msg !== null && $.cookie('alertshow') !== 'no') {
                    $('#alert_msg_block').fadeIn('700');
                    $("#alert_warning_msg").append(data.msg);
                }
                console.log(data);
            },
            error: function(xhr, textStatus, error) {
                console.log(textStatus);
                console.log(error);
            }
        });

        $('a.select-lang').on('click', function () {
          //  $(this).parent().find('li.active').removeClass('active');
          //  $(this).addClass('active');

            let Lng = $(this).attr('data-id');

            let request = $.ajax({
                url: '{{ route('admin.ajax.action') }}',
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

        $(document).on("click", "a.opislink:not(.active)", function () {
            $(this).addClass('active');
            $(this).parent().find('div.opis').slideDown(760);
            return false;
        });

        $(document).on("click", "a.opislink.active", function () {
            $(this).removeClass('active');
            $(this).parent().find('div.opis').slideUp(760);
            return false;
        });

        setTimeout(function () {
            setTimeout(function () {
                $('.alert-success').fadeOut('700')
            }, 5000);
        });
    });

</script>

@yield('js')

</body>
</html>
