@extends('admin.app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
    {!! Html::style('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') !!}

@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">

                            @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                                <div class="row">
                                    <div class="col-lg-12">
                                        <p class="text-center">
                                            <a class="btn btn-outline btn-danger btn-lg" onclick="confirmation()"
                                               title="{{ trans('frontend.str.log_clear') }}">
                                                <span class="fa fa-trash fa-2x"></span> {{ trans('frontend.str.log_clear') }}
                                            </a>
                                        </p>
                                    </div>
                                </div>

                            @endif

                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>{{ trans('frontend.str.time') }}</th>
                                    <th>{{ trans('frontend.str.total') }}</th>
                                    <th>{{ trans('frontend.str.sent') }}</th>
                                    <th>{{ trans('frontend.str.unsent') }}</th>
                                    <th>{{ trans('frontend.str.read') }}</th>
                                    <th>{{ trans('frontend.str.excel_report') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>{{ trans('frontend.str.time') }}</th>
                                    <th>{{ trans('frontend.str.total') }}</th>
                                    <th>{{ trans('frontend.str.sent') }}</th>
                                    <th>{{ trans('frontend.str.unsent') }}</th>
                                    <th>{{ trans('frontend.str.read') }}</th>
                                    <th>{{ trans('frontend.str.excel_report') }}</th>
                                </tr>
                                </tfoot>
                            </table>

                            <div class="pt-3">
                                <table id="logList" class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th>{{ trans('frontend.str.newsletter') }}</th>
                                        <th>E-mail</th>
                                        <th>{{ trans('frontend.str.time') }}</th>
                                        <th>{{ trans('frontend.str.status') }}</th>
                                        <th>{{ trans('frontend.str.read') }}</th>
                                        <th>{{ trans('frontend.str.error') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <th>{{ trans('frontend.str.newsletter') }}</th>
                                        <th>E-mail</th>
                                        <th>{{ trans('frontend.str.time') }}</th>
                                        <th>{{ trans('frontend.str.status') }}</th>
                                        <th>{{ trans('frontend.str.read') }}</th>
                                        <th>{{ trans('frontend.str.error') }}</th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
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

    <!-- DataTables  & Plugins -->
    {!! Html::script('/plugins/datatables/jquery.dataTables.min.js') !!}
    {!! Html::script('/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/datatables-responsive/js/dataTables.responsive.min.js') !!}
    {!! Html::script('/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/dataTables.buttons.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/pdfmake/pdfmake.min.js') !!}
    {!! Html::script('/plugins/pdfmake/vfs_fonts.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.html5.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.print.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.colVis.min.js') !!}

    <script>
        $(function () {
            $('#itemList').dataTable({
                "oLanguage": {
                    "sLengthMenu": "Отображено _MENU_ записей на страницу",
                    "sZeroRecords": "Ничего не найдено - извините",
                    "sInfo": "Показано с _START_ по _END_ из _TOTAL_ записей",
                    "sInfoEmpty": "Показано с 0 по 0 из 0 записей",
                    "sInfoFiltered": "(отфильтровано  _MAX_ всего записей)",
                    "oPaginate": {
                        "sFirst": "Первая",
                        "sLast": "Посл.",
                        "sNext": "След.",
                        "sPrevious": "Пред.",
                    },
                },
                "sDom": "lrtip",
                "autoWidth": true,
                'createdRow': function (row, data, dataIndex) {
                    $(row).attr('id', 'rowid_' + data['id']);
                },
                aaSorting: [[0, 'asc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ URL::route('admin.datatable.logs') }}'
                },
                columns: [
                    {data: 'start_date', name: 'start_date'},
                    {data: 'count', name: 'count', searchable: false},
                    {data: 'sent', name: 'sent', searchable: false},
                    {data: 'unsent', name: 'unsent', searchable: false},
                    {data: 'read_mail', name: 'read_mail', searchable: false},
                    {data: 'report', name: 'report', orderable: false, searchable: false},
                ],
            });

            $('#logList').dataTable({
                "oLanguage": {
                    "sLengthMenu": "Отображено _MENU_ записей на страницу",
                    "sZeroRecords": "Ничего не найдено - извините",
                    "sInfo": "Показано с _START_ по _END_ из _TOTAL_ записей",
                    "sInfoEmpty": "Показано с 0 по 0 из 0 записей",
                    "sInfoFiltered": "(отфильтровано  _MAX_ всего записей)",
                    "oPaginate": {
                        "sFirst": "Первая",
                        "sLast": "Посл.",
                        "sNext": "След.",
                        "sPrevious": "Пред.",
                    },
                    "sSearch": '<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>'
                },
                "sDom": "flrtip",
                "autoWidth": true,
                'createdRow': function (row, data, dataIndex) {
                    $(row).attr('id', 'rowid_' + data['id']);
                    if (data['status'] === 0) $(row).attr('class', 'danger');
                    else if (data['status'] === 1) $(row).attr('class', 'success');
                },
                aaSorting: [[2, 'desc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ URL::route('admin.datatable.info_log') }}'
                },
                columns: [
                    {data: 'template', name: 'template'},
                    {data: 'email', name: 'email'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'success', name: 'success', searchable: false},
                    {data: 'readMail', name: 'readMail', searchable: false},
                    {data: 'errorMsg', name: 'errorMsg', orderable: false, searchable: false},
                ],
            });
        })

        function confirmation(event) {
            Swal.fire({
                title: "{{ trans('frontend.str.clear_confirmation') }}",
                text: "{{ trans('frontend.str.want_to_log_clear') }}",
                showCancelButton: true,
                icon: 'warning',
                cancelButtonText: "{{ trans('frontend.str.cancel') }}",
                confirmButtonText: "{{ trans('frontend.str.yes') }}",
                reverseButtons: true,
                confirmButtonColor: "#DD6B55",
                customClass: {
                    actions: 'my-actions',
                    cancelButton: 'order-1',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ URL::route('admin.log.clear') }}";
                }
            })
        }

    </script>

@endsection
