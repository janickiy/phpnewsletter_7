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
                                <a href="{{ route('admin.templates.create') }}" class="btn btn-info btn-sm pull-left">
                                    <span class="fa fa-plus"> &nbsp;</span> {{ trans('frontend.str.add_template') }}
                                </a>
                            </div>

                            {!! Form::open(['url' => route('admin.templates.status'), 'method' => 'post']) !!}

                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th style="width: 10px">
                                        <span>
                                            <input type="checkbox" title="{{ trans('frontend.str.check_uncheck_all') }}"
                                                   id="checkAll">
                                        </span>
                                    </th>
                                    <th style="width: 10px">ID</th>
                                    <th>{{ trans('frontend.str.template') }}</th>
                                    <th>{{ trans('frontend.str.importance') }}</th>
                                    <th>{{ trans('frontend.str.attachments') }}</th>
                                    <th>{{ trans('frontend.str.date') }}</th>
                                    <th style="width: 10%">{{ trans('frontend.str.action') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <th style="width: 10px"></th>
                                <th style="width: 10px">ID</th>
                                <th>{{ trans('frontend.str.template') }}</th>
                                <th>{{ trans('frontend.str.importance') }}</th>
                                <th>{{ trans('frontend.str.attachments') }}</th>
                                <th>{{ trans('frontend.str.date') }}</th>
                                <th style="width: 10%">{{ trans('frontend.str.action') }}</th>
                                </tfoot>
                            </table>

                            <div class="row">
                                <div class="col-sm-12 padding-bottom-10">
                                    <div class="form-inline">
                                        <div class="control-group">

                                            {!! Form::select('action',[
                                            '0' => trans('frontend.str.send'),
                                            '1' => trans('frontend.str.remove')
                                            ],null,['class' => 'span3 form-control', 'id' => 'select_action','placeholder' => '--' . trans('frontend.str.action') . '--'],[0 => ['data-id' => 'sendmail', 'class' => 'open_modal']]) !!}

                                            <span class="help-inline">

                                            {!! Form::submit(trans('frontend.str.apply'), ['class' => 'btn btn-success', 'disabled' => "", 'id' => 'apply']) !!}

                                            </span>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            {!! Form::close() !!}

                            <!-- /.card-body -->
                        </div>
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

    <div class="modal fade" id="modal-lg">
        <input id="logId" type="hidden" value="0">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ trans('frontend.str.online_newsletter_log') }}<span id="process"></span>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="onlinelog"></div>
                    <div class="row">
                        <div class="col-sm-12 padding-top-10 padding-bottom-10">
                            <div class="form-inline">
                                <div class="control-group">

                                    {!! Form::select('categoryId[]', $categoryOptions, null, ['id' => 'categoryId','multiple'=>'multiple', 'placeholder' => trans('frontend.form.select_category'), 'class' => 'form-control custom-scroll', 'style' => 'width: 100%']) !!}

                                </div>
                            </div>
                        </div>
                    </div>
                    <p><span id="leftsend">0</span>% {{ trans('frontend.str.left') }}: <span id="timer2">00:00:00</span>
                    </p>
                    <div class="progress progress-sm progress-striped active">
                        <div class="progress-bar bg-color-darken" role="progressbar" style="width: 1%"></div>
                    </div>
                    <div class="online_statistics">{{ trans('frontend.str.total') }}:
                        <span id="totalsendlog">0</span>
                        <span style="color: green">{{ trans('frontend.str.good') }}: </span>
                        <span style="color: green" id="successful">0</span>
                        <span style="color: red">{{ trans('frontend.str.bad') }}: </span>
                        <span style="color: red" id="unsuccessful">0</span><br><br>
                        <span id="divStatus" class="error"></span><br>
                        <button id="sendout" class="btn btn-default btn-circle btn-modal btn-lg"
                                style="margin-right: 15px;" title="{{ trans('frontend.str.send_out_newsletter') }}"><i
                                class="fa fa-play"></i></button>
                        <button id="stopsendout"
                                class="btn btn-danger btn-circle btn-lg disabled" disabled="disabled"
                                title="{{ trans('frontend.str.stop_newsletter') }}">
                            <i class="fa fa-stop"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal">{{ trans('frontend.str.close') }}</button>
                </div>
            </div>
        </div>
    </div>

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
            let open_modal = $('#apply');

            $("#sendout").on('click', function () {
                pausesend = false;
                completed = null;
                successful = 0;
                unsuccessful = 0;
                totalmail = 0;

                if ($('.check').is(':checked')) {
                    $('#timer2').text('00:00:00');
                    $("#stopsendout").removeClass('disabled').removeAttr('disabled');
                    $("#sendout").addClass('disabled').attr('disabled', 'disabled');
                    $("#process").removeClass().addClass('showprocess');

                    let request = $.ajax({
                        url: '{{ route('admin.ajax.action') }}',
                        method: "POST",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: {
                            action: "start_mailing",
                        },
                        dataType: "json"
                    });

                    request.fail(function( jqXHR, textStatus ) {
                        completeProcess();
                        $("#divStatus").html("{{ trans('frontend.str.error_server') }}");
                        console.log(jqXHR);
                        console.log(textStatus);
                    });

                    request.done(function (data) {
                        if (data.result != null && data.result === true) {
                            $('#logId').val(data.logId);

                            getCountProcess();
                            onlineLogProcess();

                            setTimeout(() => {
                                process();
                            }, 10000);
                        } else {
                            completeProcess();
                            $("#divStatus").html("{{ trans('frontend.str.error_server') }}");
                        }
                        console.log(data);
                    });
                } else {
                    $("#divStatus").html('{{ trans('frontend.str.no_newsletter_selected') }}');
                }
            });

            $("#stopsendout").on('click', function () {
                $.ajax({
                    type: 'POST',
                    url: '{{ route('admin.ajax.action') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "process",
                        command: "stop",
                    },
                    dataType: "json",
                    success: function (data) {
                        pausesend = true;
                        $("#process").removeClass();
                        $("#pausesendout").addClass('disabled').attr('disabled', 'disabled');
                        $("#stopsendout").addClass('disabled').attr('disabled', 'disabled');
                        $("#sendout").removeClass('disabled').removeAttr('disabled');
                        $("#refreshemail").addClass('disabled').attr('disabled', 'disabled');

                        $('#timer2').text('00:00:00');
                        $('.progress-bar').css('width', '0%');
                        $('#leftsend').text(0);
                        $("#process").removeClass();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        completeProcess();
                        $("#divStatus").html("{{ trans('frontend.str.error_server') }}");
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                });
            });

            open_modal.click(function (event) {
                let idSelect = $('#select_action').val();

                if (idSelect == '') {
                    event.preventDefault();
                    Swal.fire({
                        title: "Error",
                        text: "{{ trans('frontend.str.select_action') }}",
                        type: "error",
                        showCancelButton: false,
                        cancelButtonText: "{{ trans('frontend.str.cancel') }}",
                        confirmButtonColor: "#DD6B55",
                        closeOnConfirm: false
                    });
                } else {
                    if (idSelect == 1) {
                        event.preventDefault();
                        let form = $(this).parents('form');
                        Swal.fire({
                            title: "{{ trans('frontend.str.delete_confirmation') }}",
                            text: "{{ trans('frontend.str.confirm_remove') }}",
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "{{ trans('frontend.str.yes') }}",
                            cancelButtonText: "{{ trans('frontend.str.cancel') }}",
                            closeOnConfirm: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    }

                    if (idSelect == 0) {
                        event.preventDefault();

                        let myModal = new bootstrap.Modal(document.getElementById('modal-lg'), {});
                        myModal.show();
                    }
                }
            });

            $("#checkAll").click(function () {
                $('input:checkbox').not(this).prop('checked', this.checked);
                countChecked();
            });

            $("#checkAll").on('change', function () {
                countChecked();
            });

            $("#itemList").on('change', 'input.check', function () {
                countChecked();
            });

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
                aaSorting: [[1, 'asc']],
                "processing": true,
                "responsive": true,
                "autoWidth": true,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.templates') }}'
                },
                'columns': [
                    {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false},
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'prior', name: 'prior', searchable: false},
                    {data: 'attach', name: 'attach.id', searchable: false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
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
                            url: '{{ route('admin.templates.destroy') }}',
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

        function countChecked() {
            if ($('.check').is(':checked'))
                $('#apply').attr('disabled', false);
            else
                $('#apply').attr('disabled', true);
        }

        function getCountProcess() {
            let logId = $('#logId').val();

            if (logId != 0 && completed === null) {
                $.ajax({
                    url: '{{ route('admin.ajax.action') }}',
                    cache: false,
                    method: "POST",
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "count_send",
                        logId: $('#logId').val(),
                        categoryId: $('#categoryId').val(),
                    },
                    dataType: "json",
                    success: function (json) {
                        if (json.result === true) {
                            let totalmail = json.total;
                            let successful = json.success;
                            let unsuccessful = json.unsuccessful;
                            let timeleft = json.time;
                            let leftsend = json.leftsend;

                            $('#totalsendlog').text(totalmail);
                            $('#unsuccessful').text(unsuccessful);
                            $('#successful').text(successful);
                            $('#timer2').text(timeleft);

                            onlineLogProcess();

                            $('.progress-bar').css('width', leftsend + '%');
                            $('#leftsend').text(leftsend);

                            setTimeout(() => {
                                getCountProcess();
                            }, 2000);

                            // setTimeout('', 2000);
                        } else {
                            setTimeout(() => {
                                getCountProcess();
                            }, 1000);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        completeProcess();
                        $("#divStatus").html("{{ trans('frontend.str.error_server') }}");
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                });
            }
        }

        function onlineLogProcess() {
            if (completed === null) {
                $.ajax({
                    type: 'POST',
                    cache: false,
                    url: '{{ route('admin.ajax.action') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "log_online",
                    },
                    dataType: "json",
                    success: function (data) {
                        let msg = '';

                        for (let i = 0; i < data.item.length; i++) {
                            if (data.item[i].email != 'undefined') {
                                msg += data.item[i].email + ' - ' + data.item[i].status;
                                msg += '<br>';
                            }
                            $('#onlinelog').html(msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        completeProcess();
                        $("#divStatus").html("{{ trans('frontend.str.error_server') }}");
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                });
            }
        }

        function process() {
            if (pausesend === false) {
                let templateId = [];

                $('input:checkbox:checked').each(function () {
                    templateId.push($(this).val());
                });

                $.ajax({
                    type: 'POST',
                    url: '{{ route('admin.ajax.action') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "send_out",
                        categoryId: $('#categoryId').val(),
                        templateId: templateId,
                        logId: $('#logId').val(),
                    },
                    cache: false,
                    dataType: "json",
                    success: function (json) {
                        if (json.completed === true) {
                            $("#process").removeClass();
                            completeProcess();
                        }
                    },
                    timeout: 10000,
                    error: function (jqXHR, textStatus, errorThrown) {
                      //  completeProcess();
                      //  $("#divStatus").html("{{ trans('frontend.str.error_server') }}");
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                });
            }
        }

        function completeProcess() {
            completed = true;
            $("#pausesendout").addClass('disabled').attr('disabled', 'disabled');
            $("#stopsendout").addClass('disabled').attr('disabled', 'disabled');
            $("#sendout").removeClass('disabled').removeAttr('disabled');
            $("#process").removeClass();
            $("#timer2").text('00:00:00');
            $('#leftsend').text(100);
            $('.progress-bar').css('width', '0%');
            $("#process").removeClass();
        }

    </script>

@endsection
