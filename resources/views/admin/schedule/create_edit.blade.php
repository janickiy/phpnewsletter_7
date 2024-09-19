@extends('admin.app')

@section('title', $title)

@section('css')

    {!! Html::style('/plugins/daterangepicker/daterangepicker.css') !!}

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

                        {!! Form::open(['url' => isset($row) ? route('admin.schedule.update') : route('admin.schedule.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ trans('frontend.form.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('event_name', trans('frontend.form.name') . '*') !!}

                                {!! Form::text('event_name', old('event_name', $row->event_name ?? null), ['class' => 'form-control', 'placeholder' => trans('frontend.form.name')]) !!}

                                @if ($errors->has('event_name'))
                                    <p class="text-danger">{{ $errors->first('event_name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('template_id',  trans('frontend.form.template')) !!}

                                {!! Form::select('template_id', $options, old('template_id', $row->template_id ?? null), ['placeholder' => trans('frontend.form.select'), 'class' => 'custom-select']) !!}

                                @if ($errors->has('template_id'))
                                    <p class="text-danger">{{ $errors->first('template_id') }}</p>
                                @endif

                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <div class="col-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-calendar-alt"></i>
                                                </span>
                                            </div>

                                            {!! Form::text('date_interval', old('date_interval', $date_interval ?? null), ['placeholder' => 'DD.MM.YYYY HH:MM - DD.MM.YYYY HH:MM', 'class' => 'form-control', 'id' => 'date_interval']) !!}
                                        </div>
                                        @if ($errors->has('date_interval'))
                                            <p class="text-danger">{{ $errors->first('date_interval') }}</p>
                                        @endif
                                    </div>

                                </div>
                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]',  trans('frontend.form.subscribers_category')) !!}

                                {!! Form::select('categoryId[]', $category_options, old('categoryId', $categoryId ?? null), ['multiple' => 'multiple', 'placeholder' => trans('frontend.form.select_category'), 'class' => 'form-control']) !!}

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
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.schedule.index') }}">
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

    <!-- date-range-picker -->
    {!! Html::script('/plugins/moment/moment.min.js') !!}
    {!! Html::script('/plugins/daterangepicker/daterangepicker.js') !!}

    <script>

        $(function () {
            $('#date_interval').daterangepicker({
                timePickerIncrement: 30,
                timePicker24Hour: true,
                timePicker: true,
                locale: {
                    format: 'DD.MM.YYYY HH:mm',
                },
                minDate: moment().add(1, 'days'),
                maxDate: moment().add(359, 'days'),
            })
        })

    </script>

@endsection
