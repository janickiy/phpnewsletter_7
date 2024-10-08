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
                        {!! Form::open(['url' => route('admin.subscribers.import_subscribers'), 'files' => true, 'method' => 'post']) !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('import', trans('frontend.form.attach_files') . '*') !!}

                                <div class="input-group">
                                    <div class="custom-file">

                                        {!! Form::file('import',  ['id' => 'import', 'class' => "custom-file-input"]) !!}

                                        {!! Form::label('import', trans('frontend.form.browse'),  ['class' => "custom-file-label"]) !!}

                                    </div>
                                </div>

                                @if ($errors->has('import'))
                                    <p class="text-danger">{{ $errors->first('import') }}</p>
                                @endif

                                <blockquote class="quote-secondary">
                                    <small>{{ trans('frontend.form.maximum_size') }}: <cite
                                            title="Source Title">{{ $maxUploadFileSize }}</cite></small>
                                </blockquote>

                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]', trans('frontend.form.charset')) !!}

                                {!! Form::select('charset', $charsets, null, ['placeholder' => '--' . trans('frontend.form.select') . '--', 'class' => 'form-control']) !!}

                                @if ($errors->has('charset'))
                                    <p class="text-danger">{{ $errors->first('charset') }}</p>
                                @endif

                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]', trans('frontend.form.subscribers_category')) !!}

                                {!! Form::select('categoryId[]', $category_options, null, ['multiple' => 'multiple', 'placeholder' => trans('frontend.form.select_category'), 'class' => 'form-control']) !!}

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
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.subscribers.index') }}">
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

    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}

    <script>

        $(function () {
            bsCustomFileInput.init();
        });

    </script>

@endsection
