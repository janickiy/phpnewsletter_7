@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected done',
        'installation' => 'selected done',
        'complete' => 'selected'
    ]])

    <div class="step-content">
        <h3>{{ trans('install.str.complete') }}!</h3>
        <hr>
        <p><strong>{{ trans('install.str.well_done') }}!</strong></p>
        <p>{{ trans('install.str.app_is_successfully_installed') }}</p>

        @if (is_writable(base_path()))
            <p>{!! trans('install.str.important') !!}</p>
        @endif

        <a class="btn btn-primary float-right" href="{{ url('login') }}">
            <i class="fa fa-sign-in"></i>
            {{ trans('install.str.log_in') }}
        </a>
        <div class="clearfix"></div>
    </div>

@endsection

@section('js')

@endsection
