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

                    @if (!empty($button_update))
                        <div id="btn_refresh">
                            <a id="start_update" class="btn btn-outline btn-default">
                                <i class="fa fa-sync-alt"></i> {!! $button_update !!}
                            </a>
                        </div>
                    @endif

                    @if (!empty($msg_no_update))
                        <a class="btn btn-outline btn-default" disabled>
                            <i class="fa fa-sync-alt"></i> {!! $msg_no_update !!}
                        </a>
                    @endif

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

        $(function () {
            $(document).on("click", "#start_update", function () {
                renderUpdateProgress();
                runUpdateStep(0);
            });
        });

        const updateSteps = @json($update_steps);
        const buttonUpdateLabel = @json(strip_tags($button_update));
        const ajaxUrl = @json(route('admin.ajax.action'));
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const failedToUpdateText = @json(__('frontend.msg.failed_to_update'));
        const startUpdateText = @json(__('frontend.str.start_update'));
        const updateCompletedText = @json(__('frontend.msg.update_completed'));

        function renderUpdateProgress() {
            const $progress = $('<div>', {class: 'progress'}).append(
                $('<div>', {
                    id: 'progress_bar',
                    class: 'progress-bar bg-primary progress-bar-striped',
                    role: 'progressbar',
                    'aria-valuenow': 1,
                    'aria-valuemin': 0,
                    'aria-valuemax': 100,
                    style: 'width: 1%'
                })
            );
            const $status = $('<p>', {class: 'text-muted', id: 'status_process'}).text(startUpdateText);

            $('#btn_refresh').empty().append($progress).append($status);
        }

        function renderRetryButton(message) {
            const $button = $('<a>', {id: 'start_update', class: 'btn btn-outline btn-default'}).append(
                $('<i>', {class: 'fa fa-sync-alt'})
            ).append(' ' + buttonUpdateLabel);
            const $status = $('<p>', {class: 'text-muted text-danger', id: 'status_process'}).text(message || failedToUpdateText);

            $('#btn_refresh').empty().append($button).append($status);
        }

        function runUpdateStep(index) {
            const step = updateSteps[index];

            if (!step) {
                return;
            }

            $('#status_process').text(step.status);

            $.ajax({
                type: 'POST',
                cache: false,
                timeout: 300000,
                url: ajaxUrl,
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: {
                    action: 'start_update',
                    p: step.p,
                },
                success: function (data) {
                    if (data && data.result === true) {
                        $('.progress-bar').css('width', step.progress + '%');
                        $('#status_process').text(data.status || step.status);

                        if (step.final === true) {
                            $('#progress_bar').delay(3000).fadeOut();
                            setTimeout(function () {
                                $('#status_process').text(updateCompletedText);
                            }, 3000);
                            return;
                        }

                        runUpdateStep(index + 1);
                        return;
                    }

                    renderRetryButton((data && (data.status || data.errors)) || failedToUpdateText);
                },
                error: function (xhr, textStatus, error) {
                    const data = xhr.responseJSON || {};

                    renderRetryButton(data.status || data.errors || error || textStatus || failedToUpdateText);
                }
            });
        }

    </script>

@endsection
