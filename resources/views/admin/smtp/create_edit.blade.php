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

                    <!-- general form elements -->
                    <header class="card card-primary">

                        <!-- form start -->
                        {!! Form::open(['url' => isset($row) ? URL::route('admin.smtp.update') : URL::route('admin.category.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($smtp) ? Form::hidden('id', $smtp->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('host', trans('frontend.form.smtp_server') . '*') !!}

                                {!! Form::text('host', old('host', isset($smtp) ? $smtp->host : null), [ 'placeholder' => trans('frontend.form.smtp_server'), 'class' => 'form-control']) !!}

                                @if ($errors->has('host'))
                                    <p class="text-danger">{{ $errors->first('host') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('email', 'E-mail*', ['class' => 'label']) !!}

                                {!! Form::text('email', old('email', isset($smtp) ? $smtp->email : null), [ 'placeholder' => 'E-mail', 'class' => 'form-control']) !!}

                                @if ($errors->has('email'))
                                    <p class="text-danger">{{ $errors->first('email') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('username', trans('frontend.form.login') . '*') !!}

                                {!! Form::text('username', old('username', isset($smtp) ? $smtp->username : null), [ 'placeholder' => trans('frontend.form.login'), 'class' => 'form-control']) !!}

                                @if ($errors->has('username'))
                                    <p class="text-danger">{{ $errors->first('username') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('password', trans('frontend.form.password')) !!}

                                {!! Form::text('password', old('password', isset($smtp) ? $smtp->password : null), [ 'placeholder' => trans('frontend.form.password'), 'class' => 'form-control']) !!}

                                @if ($errors->has('password'))
                                    <p class="text-danger">{{ $errors->first('password') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('port', trans('frontend.form.port') . '*') !!}

                                {!! Form::text('port', old('port', isset($smtp) ? $smtp->port : 25), [ 'placeholder' => trans('frontend.form.port'), 'class' => 'form-control']) !!}

                                @if ($errors->has('port'))
                                    <p class="text-danger">{{ $errors->first('port') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('timeout', trans('frontend.form.timeout') . '*') !!}

                                {!! Form::text('timeout', old('timeout', isset($smtp) ? $smtp->timeout : 5), [ 'placeholder' => trans('frontend.form.timeout'), 'class' => 'form-control']) !!}

                                @if ($errors->has('timeout'))
                                    <p class="text-danger">{{ $errors->first('timeout') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('secure', trans('frontend.form.secure_connection')) !!}

                                <div class="inline-group">
                                    <label class="radio">

                                        {!! Form::radio('secure', 'no', old('secure', (isset($smtp) && ($smtp->secure == 'no') or !isset($smtp)) ? true : false )) !!}

                                        <i></i>{{ trans('frontend.str.no') }}</label>
                                    <label class="radio">

                                        {!! Form::radio('secure', 'ssl', old('secure', (isset($smtp) && ($smtp->secure == 'ssl')) ? true : false )) !!}

                                        <i></i>ssl</label>
                                    <label class="radio">

                                        {!! Form::radio('secure', 'tls', old('secure', (isset($smtp) && ($smtp->secure == 'tls')) ? true : false )) !!}

                                        <i></i>tls</label>
                                </div>

                            </div>

                            <div class="form-group">

                                {!! Form::label('authentication', trans('frontend.form.authentication_method')) !!}

                                <div class="inline-group">
                                    <label class="radio">

                                        {!! Form::radio('authentication', 'no', old('authentication', (isset($smtp) && ($smtp->authentication == 'no') or !isset($smtp)) ? true : false )) !!}

                                        <i></i>LOGIN ({{ trans('frontend.form.low_secrecy') }})</label>
                                    <label class="radio">

                                        {!! Form::radio('authentication', 'plain', old('authentication' , (isset($smtp) && ($smtp->authentication == 'plain')) ? true : false )) !!}

                                        <i></i>PLAIN ({{ trans('frontend.form.medium_secrecy') }})</label>
                                    <label class="radio">

                                        {!! Form::radio('authentication', 'crammd5', old('authentication' , (isset($smtp) && ($smtp->authentication == 'crammd5')) ? true : false )) !!}

                                        <i></i>CRAM-MD5 ({{ trans('frontend.form.high_secrecy') }})</label>
                                </div>

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($smtp) ? trans('frontend.form.edit') : trans('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default" href="{{ URL::route('admin.smtp.index') }}">
                                {{ trans('frontend.form.back') }}
                            </a>
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


@endsection
