<div class="form-group">

    <div id="resultSub"></div>

    {!! Form::open(['url' => '#', 'id' => 'addsub', 'autocomplete' => "off"]) !!}

    <div class="form-group">
        {!! Form::label('name', trans('frontend.str.name')) !!}
        {!! Form::text('name',old('name'),['class'=>"form-control", 'autocomplete'=>"off"]) !!}
    </div>

    <div class="form-group">
        {!! Form::label('email', 'E-mail') !!}
        {!! Form::text('email',old('email'),['class'=>"form-control",'autocomplete'=>"off"]) !!}

        <div id="error-email" class="text-danger"></div>
    </div>

    {{ Form::button(trans('frontend.str.subscribe'), ['id' => "sub",'class' => 'btn btn-primary']) }}

    {!! Form::close() !!}

</div>
