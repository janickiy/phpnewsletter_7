<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель управления | @yield('title')</title>

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
        var SITE_URL = "{{ URL::to('/') }}";
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
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="../../index3.html" class="brand-link">
            <img src="../../dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
                 style="opacity: .8">
            <span class="brand-text font-weight-light">AdminLTE 3</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->login }} @if(!empty(Auth::user()->name))({{ Auth::user()->name }})@endif</a>
                </div>
            </div>


            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->

                    <li class="nav-item">
                        <a href="../widgets.html" class="nav-link">
                            <i class="nav-icon fas fa-th"></i>
                            <p>Шаблоны</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ URL::route('admin.category.index') }}" class="nav-link">
                                <i class="nav-icon fas fa-th"></i>
                                <p>Категория подписчиков</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>
                                Charts
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="../charts/chartjs.html" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>ChartJS</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../charts/flot.html" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Flot</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../charts/inline.html" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Inline</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../charts/uplot.html" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>uPlot</p>
                                </a>
                            </li>
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
