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

                                <div class="input input-file">
                                    <span class="button">
                                       {!! Form::file('attachfile[]', ['multiple' => "true", 'id' => 'attachfile', 'onchange' => "this.parentNode.nextSibling.value = this.value", 'readonly' => ""]) !!}{{ trans('frontend.form.browse') }}
                                    </span>
                                    <input type="text" placeholder="{{ trans('frontend.form.select_files') }}" readonly="">
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

