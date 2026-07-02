<div>
    {{ $calendarView }}

    @include('filament.ns-conseil.widgets.calendar-widget', [
        'showEventModal' => $showEventModal,
        'selectedEvent' => $selectedEvent,
    ])
</div>