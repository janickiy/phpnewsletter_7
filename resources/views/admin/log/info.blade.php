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

                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">

                            <p>« <a href="{{ route('admin.log.index') }}">{{ trans('frontend.str.back') }}</a></p>

                            <table id="itemList" class="table table-striped table-bordered table-hover">
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
                "sDom": "flrtip",
                "autoWidth": true,
                "oLanguage": {
                    "sLengthMenu": "{{ trans('pagination.s_length_menu') }}",
                    "sZeroRecords": "{{ trans('pagination.s_zero_records') }}",
                    "sInfo": "{{ trans('pagination.s_info') }}",
                    "sInfoEmpty": "{{ trans('pagination.s_info_empty') }}",
                    "sInfoFiltered": "{{ trans('pagination.s_infofiltered') }}",
                    "oPaginate": {
                        "sFirst": "{{ trans('pagination.s_paginate.first') }}",
                        "sLast": "{{ trans('pagination.s_paginate.last') }}",
                        "sNext": "{{ trans('pagination.s_paginate.next') }}",
                        "sPrevious": "{{ trans('pagination.s_paginate.previous') }}",
                    },
                    "sSearch": ' <i class="fas fa-search" aria-hidden="true"></i>'
                },
                'createdRow': function (row, data, dataIndex) {
                    $(row).attr('id', 'rowid_' + data['id']);
                    if (data['status'] === 0) $(row).attr('class', 'table-danger');
                },
                aaSorting: [[2, 'desc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.info_log', ['id' => $id]) }}'
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

    </script>

@endsection



