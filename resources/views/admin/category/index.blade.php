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
                            <div class="pb-3">
                                <a href="{{ route('admin.category.create') }}"
                                   class="btn btn-info btn-sm pull-left">
                                    <span class="fa fa-plus"> &nbsp;</span> {{ trans('frontend.str.add_category') }}
                                </a>
                            </div>
                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>{{ trans('frontend.str.name') }}</th>
                                    <th>{{ trans('frontend.str.subscribers_number') }}</th>
                                    <th style="width: 10%">{{ trans('frontend.str.action') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>{{ trans('frontend.str.name') }}</th>
                                    <th>{{ trans('frontend.str.subscribers_number') }}</th>
                                    <th style="width: 10%">{{ trans('frontend.str.action') }}</th>
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

        $(function () {
            $("#itemList").DataTable({
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
                },
                "processing": true,
                "responsive": true,
                "autoWidth": true,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.category') }}'
                },
                'columns': [
                    {data: 'name', name: 'name'},
                    {data: 'subcount', name: 'subcount', searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ]
            });

            $('#itemList').on('click', 'a.deleteRow', function () {
                let rowid = $(this).attr('id');
                Swal.fire({
                    title: "{{ trans('frontend.msg.are_you_sure') }}",
                    text: "{{ trans('frontend.msg.will_not_be_able_to_ecover_information') }}",
                    showCancelButton: true,
                    icon: 'warning',
                    cancelButtonText: "{{ trans('frontend.str.cancel') }}",
                    confirmButtonText: "{{ trans('frontend.msg.yes_remove') }}",
                    reverseButtons: true,
                    confirmButtonColor: "#DD6B55",
                    customClass: {
                        actions: 'my-actions',
                        cancelButton: 'order-1',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('admin.category.destroy') }}',
                            type: "POST",
                            dataType: "html",
                            data: {id: rowid},
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function () {
                                $("#rowid_" + rowid).remove();
                                Swal.fire("{{ trans('frontend.msg.done') }}", "{{ trans('frontend.msg.data_successfully_deleted') }}", 'success');
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                Swal.fire("{{ trans('frontend.msg.error_deleting') }}", "{{ trans('frontend.msg.try_again') }}", 'error');
                                console.log(ajaxOptions);
                                console.log(thrownError);
                            }
                        });
                    }
                });
            });
        });

    </script>
@endsection
