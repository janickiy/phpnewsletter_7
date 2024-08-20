@extends('admin.app')

@section('title', $title)

@section('css')

{!! Html::style('/plugins/fullcalendar/main.css') !!}
{!! Html::style('/plugins/sweetalert2/sweetalert2.min.css') !!}

<style>
    .test
    {
        color: #fff !important;
        /* font-size: 1.5rem; */
    }

    .test:hover
    {
        color: #4f57f7 !important;
        /* font-size: 1.5rem; */
    }

    .fc-time-grid .fc-event {
        overflow: auto;
    }
    .event-class .event-actions {
        display: none;
    }
    .fc-day-today  {
        color: #fff !important;
        background-color: #0f0 !important;
    }

    .event-class:hover .event-actions {
        display: flex;
        justify-content: space-around;
        font-size: 1.75rem;
        padding-top: 4px;
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
                        <div class="pb-3">
                            <a href="{{ URL::route('admin.schedule.create') }}" class="btn btn-info btn-sm pull-left">
                                <span class="fa fa-plus"> &nbsp;</span> {{ trans('frontend.str.add_schedule') }}
                            </a>
                        </div>

                        <div id='calendar'></div>

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

{!! Html::script('/plugins/sweetalert2/sweetalert2.min.js') !!}
{!! Html::script('/plugins/moment/moment.min.js') !!}
{!! Html::script('/plugins/fullcalendar/main.js') !!}
{!! Html::script('/plugins/fullcalendar/locales/ru.js') !!}

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#calendar').on('click', '.delete-event', function () {
            let rowid = $(this).attr('data-id');

            Swal.fire({
                title: "Вы действительно хотите удалить этот событие?",
                showCancelButton: true,
                confirmButtonText: "Удалить",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ URL::route('admin.schedule.destroy') }}',
                        type: "POST",
                        data: {id: rowid},
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success: function () {
                            $("#rowid_" + rowid).remove();
                            Swal.fire("{{ trans('frontend.msg.done') }}", "{{ trans('frontend.msg.data_successfully_deleted') }}", 'success');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            Swal.fire("{{ trans('frontend.msg.error_deleting') }}", "{{ trans('frontend.msg.try_again') }}", 'error');
                            console.log(ajaxOptions);
                            console.log(thrownError);
                        }
                    });
                }
            });
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        let initialTimeZone = 'UTC';
        let timeZoneSelectorEl = document.getElementById('timezone-selector');
        let loadingEl = true;
        let calendarEl = document.getElementById('calendar');
        let calendar = new FullCalendar.Calendar(calendarEl, {
            eventMouseEnter: function(info) {
                info.el.innerHTML = '<span class = "test"> '+ info.event.title +'<div class="event-actions">'+
                    '<a href="schedule/edit/'+info.event.id+'" class=" btn btn-info btn-sm"  ><i class="fa fa-edit"></i></a>'+
                    '<button class=" btn btn-danger btn-sm delete-event" data-id='+info.event.id+'><i class="fa fa-trash"></i></button></div></span>';
            },
            eventMouseLeave: function(info) {

                // console.log(info.event.start.getHours());
                // console.log(info.event.start.getMinutes());
                // console.log(info.event.start.getUTCHours());
                info.el.innerHTML = '<span><div class="event-actions"> &#x25CF;'+info.event.start.getUTCHours() + ':' + info.event.start.getMinutes() + ' UTC <b>'  +info.event.title+'</b></span>';
            },
            timeZone: initialTimeZone,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            navLinks: true, // can click day/week names to navigate views
            editable: true,
            selectable: true,
            dayMaxEvents: true, // allow "more" link when too many events
            events: "{{ route('admin.schedule.list') }}",

            locale: 'ru', // the initial locale

            eventTimeFormat: { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' }
        });

        calendar.render();
    });
</script>
@endsection
