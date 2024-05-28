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
                        {!! Form::open(['url' => URL::route('admin.subscribers.export_subscribers'), 'method' =>'post']) !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('export_type', trans('frontend.form.format')) !!}

                                <div class="inline-group">
                                    <label class="radio">

                                        {{ Form::radio('export_type', 'text', true) }}

                                        <i></i>{{ trans('frontend.form.text') }}
                                    </label>
                                    <label class="radio">

                                        {{ Form::radio('export_type', 'excel', false) }}

                                        <i></i>MS Excel
                                    </label>
                                </div>

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('compress', trans('frontend.form.format')) !!}

                                <div class="inline-group">
                                    <label class="radio">

                                        {{ Form::radio('compress', 'none', true) }}

                                        <i></i>{{ trans('frontend.str.no') }}
                                    </label>
                                    <label class="radio">

                                        {{ Form::radio('compress', 'zip', true) }}

                                        <i></i>zip
                                    </label>
                                </div>

                            </div>


                            <div class="form-group">

                                {!! Form::label('categoryId[]', trans('frontend.form.subscribers_category')) !!}

                                {!! Form::select('categoryId[]', $options, null, ['multiple'=>'multiple', 'placeholder' => trans('frontend.form.select_category'), 'class' => 'form-control']) !!}

                                @if ($errors->has('categoryId'))
                                    <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                                @endif

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ trans('frontend.form.send') }}
                            </button>
                            <a class="btn btn-default" href="{{ URL::route('admin.subscribers.index') }}">
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
