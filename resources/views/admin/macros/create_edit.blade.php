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
                        {!! Form::open(['url' => isset($row) ? URL::route('admin.macros.update') : URL::route('admin.macros.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($category) ? Form::hidden('id', $category->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('name', trans('frontend.form.name') . '*') !!}

                                {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control', 'placeholder' => trans('frontend.form.name')]) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('value', trans('frontend.form.value')) !!}

                                {!! Form::textarea('value', old('value', $user->value ?? null), [ 'placeholder' => trans('frontend.form.description'), 'rows' => 3, 'class' => 'form-control']) !!}

                                @if ($errors->has('value'))
                                    <p class="text-danger">{{ $errors->first('value') }}</p>
                                @endif

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? trans('frontend.form.edit') : trans('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ URL::route('admin.macros.index') }}">
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

