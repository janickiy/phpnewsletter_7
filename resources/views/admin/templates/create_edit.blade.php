@extends('admin.app')

@section('title', $title)

@section('css')

    <!-- summernote -->
    {!! Html::style('/plugins/summernote/summernote-bs4.min.css') !!}
    <!-- CodeMirror -->
    {!! Html::style('/plugins/codemirror/codemirror.css') !!}
    {!! Html::style('/plugins/codemirror/theme/monokai.css') !!}

@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <!-- general form elements -->
                    <header class="card card-primary">

                        <!-- form start -->
                        {!! Form::open(['url' => isset($template) ? URL::route('admin.templatesupdate') : URL::route('admin.templates.store'), 'files' => true, 'method' => isset($template) ? 'put' : 'post', 'id' => 'tmplForm']) !!}

                        {!! isset($template) ? Form::hidden('id', $template->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('name', trans('frontend.form.name') . '*') !!}

                                {!! Form::text('name', old('name', $template->name ?? null), ['class' => 'form-control']) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('body', trans('frontend.form.template') . '*') !!}

                                {!! Form::textarea('body', old('name', $template->body ?? null), ['rows' => "3", 'placeholder' => trans('frontend.form.template'), 'class' => 'form-control']) !!}

                                @if ($errors->has('body'))
                                    <p class="text-danger">{{ $errors->first('body') }}</p>
                                @endif

                                <blockquote class="quote-secondary">
                                    <small>{!! trans('frontend.note.personalization') !!}</small>
                                </blockquote>

                            </div>

                            <div class="form-group">

                                {!! Form::label('attachfile[]', trans('frontend.form.attach_files')) !!}

                                <div class="input-group">
                                    <div class="custom-file">

                                        {!! Form::file('attachfile[]',  ['id' => 'import', 'multiple' => "true", 'class' => "custom-file-input"]) !!}

                                        {!! Form::label('attachfile[]', trans('frontend.form.browse'), ['class' => 'custom-file-label']) !!}

                                    </div>
                                </div>

                                @if ($errors->has('attachfile'))
                                    <p class="text-danger">{{ $errors->first('attachfile') }}</p>
                                @endif

                            </div>

                            <div class="form-group">

                                {!! Form::label('prior', trans('frontend.form.prior')) !!}

                                <div class="inline-group">
                                    <label class="radio">

                                        {!! Form::radio('prior', 3, (isset($template) && $template->prior == 3) or !isset($template)) !!}

                                        <i></i>{{ trans('frontend.form.normal') }}
                                    </label>
                                    <label class="radio">

                                        {!! Form::radio('prior', 2, isset($template) && $template->prior == 2) !!}

                                        <i></i>{{ trans('frontend.form.low') }}
                                    </label>
                                    <label class="radio">

                                        {!! Form::radio('prior', 1, isset($template) && $template->prior == 1) !!}

                                        <i></i>{{ trans('frontend.form.high') }}
                                    </label>

                                    @if ($errors->has('prior'))
                                        <p class="text-danger">{{ $errors->first('prior') }}</p>
                                    @endif

                                </div>

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($template) ? trans('frontend.form.edit') : trans('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default" href="{{ URL::route('admin.templates.index') }}">
                                {{ trans('frontend.form.back') }}
                            </a>
                        </div>

                        <div class="well bg-color-blueLight">
                            <div id="resultSend"></div>
                            <h3>{{ trans('frontend.str.send_test_letter') }}<span id="process"></span></h3>
                            <div class="input-group">

                                {!! Form::text('email', null, ['class' => 'form-control', 'placeholder' => 'Email', 'id' => 'email']) !!}

                                <div class="input-group-btn">
                                    <button class="btn btn-default" id="send_test" type="button">
                                        <i class="fa fa-send"></i> {{ trans('frontend.str.send') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                    {!! Form::close() !!}
                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- Summernote -->
    {!! Html::script('/plugins/summernote/summernote-bs4.min.js') !!}

    <!-- CodeMirror -->
    {!! Html::script('/plugins/codemirror/codemirror.js') !!}
    {!! Html::script('/plugins/codemirror/mode/css/css.js') !!}
    {!! Html::script('/plugins/codemirror/mode/xml/xml.js') !!}
    {!! Html::script('/plugins/codemirror/mode/htmlmixed/htmlmixed.js') !!}
    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}
    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}

    <!-- Page specific script -->
    <script>
        $(document).ready(function () {
            // Summernote
            $('#body').summernote();
            bsCustomFileInput.init();

            $(document).on("click", ".remove_attach", function () {
                let idAttach = $(this).attr('data-num');
                let request = $.ajax({
                    url: '{{ URL::route('ajax.action') }}',
                    method: "POST",
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "remove_attach",
                        id: idAttach,
                    },

                    dataType: "json"
                });

                request.done(function (data) {
                    if (data.result != null && data.result == true) {
                        $("#attach_" + idAttach).remove();
                    }
                });
            });

            $(document).on("click", "#send_test", function () {

                let bodyContent = ('#body').val();
                let arr = $("#tmplForm").serializeArray();
                let aParams = [];
                let sParam;

                $("#process").removeClass().addClass('showprocess');
                $("#send_test").attr('disabled','disabled');

                for (var i = 0, count = arr.length; i < count; i++) {
                    sParam = encodeURIComponent(arr[i].name);

                    if (sParam == 'body') {
                        sParam += "=";
                        sParam += encodeURIComponent(bodyContent);
                    } else {
                        sParam += "=";
                        sParam += encodeURIComponent(arr[i].value);
                    }

                    aParams.push(sParam);
                }

                sParam = 'action';
                sParam += "=";
                sParam += encodeURIComponent('send_test_email');
                aParams.push(sParam);

                let sendData = aParams.join("&");
                let request = $.ajax({
                    url: '{{ URL::route('ajax.action') }}',
                    method: "POST",
                    data: sendData,
                    dataType: "json"
                });

                request.done(function (data) {
                    if (data.result != null) {
                        let alert_msg = '';

                        if (data.result == 'success'){
                            alert_msg += '<div class="alert alert-success fade in">';
                            alert_msg += '<button class="close" data-dismiss="alert">×</button>';
                            alert_msg += '<i class="fa-fw fa fa-check"></i>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else if (data.result == 'error'){
                            alert_msg += '<div class="alert alert-danger fade in">';
                            alert_msg += '<button class="close" data-dismiss="alert">×</button>';
                            alert_msg += '<strong>{{ trans('frontend.str.error_alert') }} </strong>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else if (data.result == 'errors'){
                            alert_msg += '<div class="alert alert-danger fade in">';
                            alert_msg += '<button class="close" data-dismiss="alert">×</button>';
                            alert_msg += '<strong>{{ trans('frontend.str.error_alert') }} </strong>';
                            alert_msg += '<ul>';

                            let arr = data.msg.split(',');

                            for (var i = 0; i < arr.length; i++){
                                alert_msg += '<li> ' + arr[i] + '</li>';
                            }

                            alert_msg += '</ul>';
                            alert_msg += '</div>';
                        }

                        $("#resultSend").html(alert_msg);
                        $("#process").removeClass();
                        $("#send_test").removeAttr('disabled');
                    }
                });
            });
        })

    </script>

@endsection

