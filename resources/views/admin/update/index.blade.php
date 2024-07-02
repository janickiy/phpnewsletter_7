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
                        {!! Form::open(['url' => URL::route('admin.update.update'), 'method' => 'put']) !!}

                        <div class="card-body">

                            <div class="form-group">
                                {!! Form::label('name', trans('frontend.form.name') . '*') !!}

                                {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control', 'placeholder' => trans('frontend.form.name')]) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ trans('frontend.form.edit') }}
                            </button>
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
