@extends('admin.app')

@section('title', $title)

@section('css')

    {!! Html::style('/plugins/fullcalendar/main.css') !!}

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
                                <a href="{{ URL::route('admin.schedule.create') }}"
                                   class="btn btn-info btn-sm pull-left">
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

    {!! Html::script('/plugins/moment/moment.min.js') !!}
    {!! Html::script('/plugins/fullcalendar/main.js') !!}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var initialTimeZone = 'UTC';
            var timeZoneSelectorEl = document.getElementById('timezone-selector');
            var loadingEl = document.getElementById('loading');
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
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
                events: '',

                loading: function(bool) {
                    if (bool) {
                        loadingEl.style.display = 'inline'; // show
                    } else {
                        loadingEl.style.display = 'none'; // hide
                    }
                },

                eventTimeFormat: { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' }
            });

            calendar.render();

            fetch('https://fullcalendar.io/api/demo-feeds/timezones.json')
                .then((response) => response.json())
                .then((timeZones) => {
                    timeZones.forEach(function(timeZone) {
                        var optionEl;

                        if (timeZone !== 'UTC') { // UTC is already in the list
                            optionEl = document.createElement('option');
                            optionEl.value = timeZone;
                            optionEl.innerText = timeZone;
                            timeZoneSelectorEl.appendChild(optionEl);
                        }
                    });
                });

            // when the timezone selector changes, dynamically change the calendar option
            timeZoneSelectorEl.addEventListener('change', function() {
                calendar.setOption('timeZone', this.value);
            });

        });
    </script>


@endsection

