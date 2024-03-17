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
                        {!! Form::open(['url' => isset($row) ? URL::route('admin.category.update') : URL::route('admin.category.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        <div class="card-body">

                            <p>*-обязательные поля </p>

                            <div class="form-group">
                                {!! Form::label('name', 'Имя*') !!}

                                {!! Form::text('name', old('name', isset($row) ? $row->name : null), ['class' => 'form-control', 'placeholder' => "Название"]) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? 'Изменить' : 'Добавить' }}
                            </button>
                            <a class="btn btn-default" href="{{ URL::route('admin.category.index') }}">
                                Назад
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

