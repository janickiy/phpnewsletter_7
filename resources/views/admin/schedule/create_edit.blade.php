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

                        {!! Form::open(['url' => isset($row) ? URL::route('admin.schedule.update') : URL::route('admin.schedule.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('templateId',  trans('frontend.form.template')) !!}

                                {!! Form::select('templateId', $options, old('templateId', isset($row) ? $row->template_id : null), ['placeholder' => trans('frontend.form.select'), 'class' => 'custom-select']) !!}

                                @if ($errors->has('templateId'))
                                    <p class="text-danger">{{ $errors->first('templateId') }}</p>
                                @endif

                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <div class="col-3">
                                        {!! Form::text('value_from_start_date', old('value_from_start_date', isset($row) ? date("d.m.Y H:i", strtotime($row->value_from_start_date)) : null), ['placeholder' => 'DD.MM.YYYY HH:MM', 'class' => 'form-control', 'data-datepicker' => "separateRange"]) !!}

                                        @if ($errors->has('value_from_start_date'))
                                            <p class="text-danger">{{ $errors->first('value_from_start_date') }}</p>
                                        @endif
                                    </div>

                                    <div class="col-3">
                                        {!! Form::text('value_from_end_date', old('value_from_end_date', isset($row) ? date("d.m.Y H:i", strtotime($row->value_from_end_date)) : null), ['placeholder' => 'DD.MM.YYYY HH:MM', 'class' => 'form-control', 'data-datepicker' => "separateRange"]) !!}

                                        @if ($errors->has('value_from_end_date'))
                                            <p class="text-danger">{{ $errors->first('value_from_end_date') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]',  trans('frontend.form.subscribers_category')) !!}

                                {!! Form::select('categoryId[]', $category_options, old('categoryId', isset($row) ? $categoryId : null), ['multiple' => 'multiple', 'placeholder' => trans('frontend.form.select_category'), 'class' => 'form-control']) !!}

                                @if ($errors->has('categoryId'))
                                    <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                                @endif
                            </div>

                            <!-- /.card-body -->
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? trans('frontend.form.edit') : trans('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default" href="{{ URL::route('admin.schedule.index') }}">
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



    {!! Html::script('/plugins/daterangepicker/daterangepicker.js') !!}


    <script>

        $(function () {

        })

    </script>

@endsection
