@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected'
    ]])

    @include('layouts.notifications')

    {!! Form::open(['route' => 'install.installation']) !!}

    <div class="step-content">
        <h3>{{ trans('install.str.database_information') }}</h3>
        <hr>
        <div class="form-group">

            {!! Form::label('host', trans('install.str.database_host')) !!}

            {!! Form::text('host', old('host'), ['class' => "form-control", 'placeholder' => "",'id' => "host"]) !!}

            <small>{{ trans('install.hint.database_host') }}</small>
            @if ($errors->has('host'))
                <p class="text-danger">{{ $errors->first('host') }}</p>
            @endif
        </div>
        <div class="form-group">

            {!! Form::label('username', trans('install.str.database_username')) !!}

            {!! Form::text('username', old('username'), ['class' => "form-control", 'placeholder' => "",'id' => "username"]) !!}

            <small>{{ trans('install.hint.database_username') }}</small>
            @if ($errors->has('host'))
                <p class="text-danger">{{ $errors->first('username') }}</p>
            @endif
        </div>
        <div class="form-group">

            {!! Form::label('password', trans('install.str.password')) !!}

            {!! Form::password('password', ['class' => "form-control", 'id' => "password"]) !!}

            <small>{{ trans('install.hint.database_password') }}</small>
            @if ($errors->has('password'))
                <p class="text-danger">{{ $errors->first('password') }}</p>
            @endif
        </div>
        <div class="form-group">
            {!! Form::label('database', trans('install.str.database_name')) !!}

            {!! Form::text('database', old('database'), ['class' => "form-control", 'placeholder' => "",'id' => "database"]) !!}

            <small>{{ trans('install.hint.database_name') }}</small>
            @if ($errors->has('database'))
                <p class="text-danger">{{ $errors->first('database') }}</p>
            @endif
        </div>
        <button class="btn btn-primary float-right mt-3">
            {{ trans('install.button.next') }}
            <i class="fa fa-arrow-right"></i>
        </button>
        <div class="clearfix"></div>
    </div>

    {!! Form::close() !!}

@endsection

@section('js')

@endsection
