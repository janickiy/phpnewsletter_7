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
                                    <div class="col-lg-12"><p class="text-center">
                                            <a class="btn btn-outline btn-danger btn-lg"
                                               title="{{ trans('frontend.str.log_clear') }}" onclick="confirmation()">
                                                <span class="fa fa-trash fa-2x"></span> {{ trans('frontend.str.redirect_clear') }}
                                            </a>
                                        </p>
                                    </div>
                                </div>

                            @endif

                            <table id="itemList" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>{{ trans('frontend.str.redirect_number') }}</th>
                                    <th>{{ trans('frontend.str.excel_report') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>URL</th>
                                    <th>{{ trans('frontend.str.redirect_number') }}</th>
                                    <th>{{ trans('frontend.str.excel_report') }}</th>
                                </tr>
                                </tfoot>
                            </table>

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

        $(document).ready(function () {

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
                    "sSearch": ' <i class="fas fa-search" aria-hidden="true"></i>'
                },
                "sDom": "flrtip",
                "autoWidth": true,
                aaSorting: [[0, 'asc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ URL::route('admin.datatable.redirect') }}'
                },
                columns: [
                    {data: 'url', name: 'url'},
                    {data: 'count', name: 'count', searchable: false},
                    {data: 'report', name: 'report', orderable: false, searchable: false},
                ],
            });
        })

    </script>

@endsection
