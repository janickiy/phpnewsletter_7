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
                        {!! Form::open(['url' => isset($row) ? URL::route('admin.subscribers.update') : URL::route('admin.subscribers.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($subscriber) ? Form::hidden('id', $subscriber->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('name', trans('frontend.form.name')) !!}

                                {!! Form::text('name', old('name', isset($subscriber) ? $subscriber->name : null), ['class' => 'form-control']) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('email', 'Email*') !!}

                                {!! Form::text('email', old('email', isset($subscriber) ? $subscriber->email : null), ['class' => 'form-control']) !!}

                                @if ($errors->has('email'))
                                    <p class="text-danger">{{ $errors->first('email') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]',  trans('frontend.form.subscribers_category')) !!}

                                {!! Form::select('categoryId[]', $options, isset($subscriber) ? $subscriberCategoryId : null, ['multiple' => 'multiple', 'placeholder' => trans('frontend.form.select_category'), 'class' => 'form-control']) !!}

                                @if ($errors->has('categoryId'))
                                    <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                                @endif
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($smtp) ? trans('frontend.form.edit') : trans('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default" href="{{ URL::route('admin.subscribers.index') }}">
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
