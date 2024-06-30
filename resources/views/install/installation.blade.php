@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected done',
        'installation' => 'selected'
    ]])

    {!! Form::open(['route' => 'install.install']) !!}

    <div class="step-content">
        <h3>{{ trans('install.str.install') }}</h3>
        <hr>
        <p>{{ trans('install.str.ready_to_install') }}</p>
        <p>{{ trans('install.str.administration') }}:</p>
        <div class="form-group">
            <label for="app_name">{{ trans('install.str.login') }}</label>

            {!! Form::text('login', old('login'), ['class' => "form-control", 'id' => "login"]) !!}

            @if ($errors->has('login'))
                <span class="text-danger">{{ $errors->first('login') }}</span>
            @endif

        </div>
        <div class="form-group">

            {!! Form::label('app_name', trans('install.str.password')) !!}

            {!! Form::password('password', ['class' => "form-control", 'id' => "password"]) !!}

            @if ($errors->has('password'))
                <span class="text-danger">{{ $errors->first('password') }}</span>
            @endif
        </div>
        <div class="form-group">

            {!! Form::label('app_name', trans('install.str.confirm_password')) !!}

            {!! Form::password('confirm_password', ['class' => "form-control", 'id' => "confirm_password"]) !!}

            @if ($errors->has('confirm_password'))
                <span class="text-danger">{{ $errors->first('confirm_password') }}</span>
            @endif
        </div>
        <button class="btn btn-green pull-right" data-toggle="loader" data-loading-text="Installing" type="submit">
            <i class="fa fa-play"></i>
            {{ trans('install.button.install') }}
        </button>
        <div class="clearfix"></div>
    </div>

    {!! Form::close() !!}

@endsection

@section('js')

@endsection
