@extends('admin.app')

@section('title', $title)

@section('css')


@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                        @if (!empty($button_update))

                            <div id="btn_refresh">
                                <a id="start_update" class="btn btn-outline btn-default" href="#">
                                    <i class="fa fa-refresh"></i> {!! $button_update !!}
                                </a>
                            </div>

                        @endif

                        @if (!empty($msg_no_update))

                            <a class="btn btn-outline btn-default" disabled>
                                <i class="fa fa-refresh"></i> {!! $msg_no_update !!}
                            </a>

                        @endif

                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <script>

        $(function () {
            $("#start_update").on("click", function () {
                $("#btn_refresh").html('<div class="progress"><div id="progress_bar" class="progress-bar bg-primary progress-bar-striped" role="progressbar" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100" style="width: 1%"></div></div><span style="padding: 10px" id="status_process">{{ trans('frontend.str.start_update') }}</span>');
                $("#status_process").text('{{ trans('frontend.msg.downloading') }} update.zip ...');

                $.ajax({
                    type: "POST",
                    cache: false,
                    url: "{{ URL::route('admin.ajax.action') }}",
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "start_update",
                        p: "start",
                    },
                    success: function (data) {
                        if (data.result === true) {
                            $('.progress-bar').css('width', '15%');
                            $("#status_process").text(data.status);
                            uploapFiles2();
                        } else {
                            $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                        }
                    }
                });
            });
        });

        function uploapFiles2() {
            $("#status_process").text('{{ trans('frontend.msg.downloading') }} puplic.zip ...');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "uploap_files_2",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '30%');
                        $("#status_process").text(data.status);
                        uploadFiles3();
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

        function uploadFiles3() {
            $("#status_process").text('{{ trans('frontend.msg.downloading') }} vendor.zip ...');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "uploap_files_3",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '40%');
                        $("#status_process").text(data.status);
                        updateFiles();
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

        function updateFiles() {
            $("#status_process").text('{{ trans('frontend.msg.unzipping') }} update.zip ...');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "update_files",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '50%');
                        $("#status_process").text(data.status);
                        updateFiles2();
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

        function updateFiles2() {
            $("#status_process").text('{{ trans('frontend.msg.unzipping') }} puplic.zip ...');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "update_files_2",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '60%');
                        $("#status_process").text(data.status);
                        updateFiles3();
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

        function updateFiles3() {
            $("#status_process").text('{{ trans('frontend.msg.unzipping') }} vendor.zip ...');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "update_files_3",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '70%');
                        $("#status_process").text(data.status);
                        updateBD();
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

        function updateBD() {
            $("#status_process").text('{{ trans('frontend.msg.update_bd') }}');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "update_bd",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '90%');
                        $("#status_process").text(data.status);
                        clearCache();
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

        function clearCache() {
            $("#status_process").text('{{ trans('frontend.msg.completing_update') }}');

            $.ajax({
                type: "POST",
                cache: false,
                url: "{{ URL::route('admin.ajax.action') }}",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    action: "start_update",
                    p: "clear_cache",
                },
                success: function (data) {
                    if (data.result === true) {
                        $('.progress-bar').css('width', '100%');
                        $('#progress_bar').delay(3000).fadeOut();
                        $('#status_process').delay(3000).text('{{ trans('frontend.msg.update_completed') }}');
                    } else {
                        $("#btn_refresh").html('<a id="start_update" class="btn btn-outline btn-default" href="#"><i class="fa fa-refresh"></i> {!! $button_update !!}</a><span style="padding: 10px">' + data.status + '</span>');
                    }
                }
            });
        }

    </script>

@endsection
