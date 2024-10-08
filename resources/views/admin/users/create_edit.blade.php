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
                        {!! Form::open(['url' => isset($row) ? route('admin.users.update') : route('admin.users.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('name', trans('frontend.form.name')) !!}

                                {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control', 'placeholder' => trans('frontend.form.name')]) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('login', trans('frontend.form.login')) !!}

                                {!! Form::text('login', old('login', $row->login ?? null), [ 'placeholder' => trans('frontend.form.login'), 'class' => 'form-control']) !!}

                                @if ($errors->has('login'))
                                    <p class="text-danger">{{ $errors->first('login') }}</p>
                                @endif

                            </div>

                            <div class="form-group">

                                {!! Form::label('description', trans('frontend.form.description')) !!}

                                {!! Form::textarea('description', old('description', $row->description ?? null), [ 'placeholder' => trans('frontend.form.description'), 'rows' => 3, 'class' => 'form-control']) !!}

                                @if ($errors->has('description'))
                                    <p class="text-danger">{{ $errors->first('description') }}</p>
                                @endif

                            </div>

                            @if ((isset($row->id) && $row->id != Auth::user()->id) || !isset($row->id))

                                <div class="form-group">

                                    {!! Form::label('role', trans('frontend.form.role')) !!}

                                    {!! Form::select('role', $options, $row->role ?? 'admin', ['placeholder' => trans('frontend.form.select_role'), 'class' => 'custom-select']) !!}

                                    @if ($errors->has('role'))
                                        <p class="text-danger">{{ $errors->first('role') }}</p>
                                    @endif

                                </div>

                                <div class="form-group">

                                    {!! Form::label('password', trans('frontend.form.password')) !!}

                                    {!! Form::password('password', ['class' => 'form-control']) !!}

                                    @if ($errors->has('password'))
                                        <p class="text-danger">{{ $errors->first('password') }}</p>
                                    @endif

                                </div>

                                <div class="form-group">

                                    {!! Form::label('password_again', trans('frontend.form.password_again')) !!}

                                    {!! Form::password('password_again', ['class' => 'form-control']) !!}

                                    @if ($errors->has('password_again'))
                                        <p class="text-danger">{{ $errors->first('password_again') }}</p>
                                    @endif

                                </div>

                            @endif

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? trans('frontend.form.edit') : trans('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.users.index') }}">
                                {{ trans('frontend.form.back') }}
                            </a>
                        </div>

                        {!! Form::close() !!}

                    </header>

                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')


@endsection

