<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('frontend.str.admin_panel') }} | @yield('title')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    {!! Html::style('/plugins/fontawesome-free/css/all.min.css') !!}

    {!! Html::style('/plugins/sweetalert2/sweetalert2.min.css') !!}

    <!-- Theme style -->
    {!! Html::style('/dist/css/adminlte.min.css') !!}

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
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item">
                <a class="nav-link" title="{{ trans('frontend.str.signout') }}" href="{{ URL::route('logout') }}"
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
        <a href="{{ URL::route('admin.templates.index') }}" class="brand-link">
            <img src="../../dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
                 style="opacity: .8">
            <span class="brand-text font-weight-light">AdminLTE 3</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="{{ URL::route('admin.users.edit', ['id' => Auth::user()->id ]) }}"
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
                        <a href="{{ URL::route('admin.templates.index') }}" class="nav-link{{ Request::is('template*') ? ' active' : '' }}"
                           title="{{ trans('frontend.menu.templates') }}">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>{{ trans('frontend.menu.templates') }}</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ URL::route('admin.schedule.index') }}" class="nav-link{{ Request::is('schedule*') ? ' active' : '' }}"
                           title="{{ trans('frontend.menu.schedule') }}">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>{{ trans('frontend.menu.schedule') }}</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ URL::route('admin.subscribers.index') }}" class="nav-link{{ Request::is('subscribers*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.subscribers') }}">
                                <i class="nav-icon fas fa-user-friends"></i>
                                <p>{{ trans('frontend.menu.subscribers') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ URL::route('admin.category.index') }}" class="nav-link{{ Request::is('category*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.subscribers') }}">
                                <i class="nav-icon fas fa-list"></i>
                                <p>{{ trans('frontend.menu.subscribers_category') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ URL::route('admin.smtp.index') }}" class="nav-link{{ Request::is('smtp*') ? ' active' : '' }}" title="SMTP">
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
                                <a href="{{ URL::route('admin.log.index') }}" class="nav-link{{ Request::is('log*') ? ' active' : '' }}"
                                   title="{{ trans('frontend.menu.mailing_log') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ trans('frontend.menu.mailing_log') }}</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ URL::route('admin.redirect.index') }}" class="nav-link{{ Request::is('redirect*') ? ' active' : '' }}"
                                   title="{{ trans('frontend.menu.referrens_log') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ trans('frontend.menu.referrens_log') }}</p>
                                </a>
                            </li>

                        </ul>
                    </li>

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ URL::route('admin.settings.index') }}" class="nav-link{{  Request::is('settings*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.settings') }}">
                                <i class="nav-icon fa fa-cogs"></i>
                                <p>{{ trans('frontend.menu.settings') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ URL::route('admin.users.index') }}" class="nav-link{{ Request::is('users*') ? ' active' : '' }}"
                               title="{{ trans('frontend.menu.users') }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>{{ trans('frontend.menu.users') }}</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item">
                        <a href="{{ URL::route('admin.pages.faq') }}" class="nav-link{{ Request::is('faq*') ? ' active' : '' }}" title="FAQ">
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
                                <a href="{{ URL::route('admin.pages.subscription_form') }}" class="nav-link{{ Request::is('pages/subscription-form*') ? ' active' : '' }}"
                                   title="{{ trans('frontend.menu.subscription_form') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ trans('frontend.menu.subscription_form') }}</p>
                                </a>
                            </li>

                            @if(PermissionsHelper::has_permission(Auth::user()->role,'admin|moderator'))

                                <li class="nav-item">
                                    <a href="{{ URL::route('admin.pages.cron_job_list') }}" class="nav-link{{ Request::is('pages/cron-job-list*') ? ' active' : '' }}"
                                       title="{{ trans('frontend.menu.cron_job_list') }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>{{ trans('frontend.menu.cron_job_list') }}</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ URL::route('admin.pages.phpinfo') }}" class="nav-link{{ Request::is('pages/phpinfo*') ? ' active' : '' }}" title="PHP Info">
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
                            <li class="breadcrumb-item"><a href="#">Панель управления</a></li>
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
        <div class="float-right d-none d-sm-block">
            <b>{{ env('VERSION') }}</b>
        </div>
        <strong>&copy; 2006-{{ date('Y') }} <a href="https://janicky.com">PHP Newsletter</a>.</strong> All rights
        reserved.
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

<!-- AdminLTE App -->
{!! Html::script('/dist/js/adminlte.min.js') !!}

{!! Html::script('/plugins/sweetalert2/sweetalert2.min.js') !!}

@yield('js')

</body>
</html>
