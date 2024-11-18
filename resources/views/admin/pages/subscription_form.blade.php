@extends('admin.app')

@section('title', $title)

@section('css')

    {!! Html::style('/plugins/highlightjs/styles/github.css') !!}

    <style>

        pre {
            position: relative;
            border: none !important;
            padding: 0 !important;
            margin-bottom: 15px !important;
            font-size: 14px !important;
        }

        pre code {
            background: #FBFBFB !important;
            font-size: 13.5px !important;
            white-space: pre;
        }

    </style>

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

                            <pre>
                                <code class="lang-html" id="codebox">
{{ $subform }}
&lt;script src=&quot;//ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js&quot;&gt;&lt;/script&gt;
{{ $subformJs }}
                                </code>
                            </pre>


                            </div>

                            <button type="submit" class="btn btn-primary margin-bottom-10"
                                    onclick="copyToClipboard('#codebox')">
                                <span id="myTooltip">{{ trans('frontend.str.copy_to_clipboard') }}</span>
                            </button>

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

    <!-- {!! Html::script('/plugins/highlightjs/highlight.js') !!} -->
    <!-- {!! Html::script('/plugins/highlightjs/highlightjs-line-numbers.js') !!} -->

    {!! Html::script('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js') !!}
    {!! Html::script('https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.6.0/highlightjs-line-numbers.min.js') !!}

    <script>hljs.highlightAll();</script>
    <script>hljs.initLineNumbersOnLoad();</script>

    <script>
        async function copyToClipboard(element) {
            let $temp = $("<input>");
            $("body").append($temp);
            $temp.val($(element).text()).select();

            const type = "text/plain";
            const content = $temp.prop('value')
            const blob = new Blob([content], { type });
            const copyContent = [new ClipboardItem({ [ type ]: blob })];

            if (navigator.clipboard) {
                navigator.clipboard.write(copyContent);
            } else {
                document.execCommand("copy");
            }

            $temp.remove();
        }
    </script>

@endsection
