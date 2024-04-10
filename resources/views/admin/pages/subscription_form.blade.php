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

                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">

                            @include('include.subform')

                            <div class="form-group">

                                <textarea rows="3" id="myInput" name="body" cols="50">

                                    @include('include.subform')
                                    &lt;script src=&quot;//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js&quot;&gt;&lt;/script&gt;
                                    @include('include.subform_js')
                                </textarea>

                            </div>

                            <button type="submit" class="btn btn-primary margin-bottom-10" onclick="myFunction()" onmouseout="outFunc()">
                                <span id="myTooltip">{{ trans('frontend.str.copy_to_clipboard') }}</span>
                            </button>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <script>
        function myFunction() {
            let copyText = document.getElementById("myInput");
            copyText.select();
            document.execCommand("copy");

            let tooltip = document.getElementById("myTooltip");
            tooltip.innerHTML = "Copied: " + copyText.value;
        }

        function outFunc() {
            let tooltip = document.getElementById("myTooltip");
            tooltip.innerHTML = "{{ trans('frontend.str.copy_to_clipboard') }}";
        }

    </script>

    @include('include.subform_js')

@endsection

