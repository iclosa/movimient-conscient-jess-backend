@extends('admin.layouts.app')
@section('title', 'Calendari')

@section('content')
    <x-page-header title="Calendari" :subtitle="$start->isoFormat('D MMM').' – '.$end->isoFormat('D MMM YYYY')">
        <x-slot:actions>
            <a href="{{ route('admin.calendar', ['data' => $previous]) }}" class="btn ghost icon-only" aria-label="Setmana anterior"><x-icon name="chevron-left" class="sm" /></a>
            <a href="{{ route('admin.calendar') }}" class="btn ghost" @if ($isCurrentWeek) aria-current="page" @endif>Aquesta setmana</a>
            <a href="{{ route('admin.calendar', ['data' => $next]) }}" class="btn ghost icon-only" aria-label="Setmana següent"><x-icon name="chevron-right" class="sm" /></a>
            <a href="{{ route('admin.sessions.create') }}" class="btn primary"><x-icon name="plus" class="sm" /> Nova classe</a>
        </x-slot:actions>
    </x-page-header>

    <div class="week">
        @foreach ($days as $day)
            @php $daySessions = $sessions->get($day->toDateString(), collect()); @endphp
            <section class="day {{ $day->isToday() ? 'today' : '' }}" aria-label="{{ $day->isoFormat('dddd D') }}">
                <div class="day-head">
                    <b>{{ $day->isoFormat('ddd') }}</b>
                    <span>{{ $day->format('j') }}</span>
                </div>
                @forelse ($daySessions as $session)
                    <a href="{{ route('admin.sessions.show', $session) }}" class="slot {{ $session->isCancelled() ? 'cancelled' : '' }}" style="--c:{{ $session->activity->color }}">
                        <div class="t">{{ $session->starts_at->format('H:i') }}</div>
                        <div class="n">{{ $session->display_title }}</div>
                        <div class="o">
                            <span>{{ $session->instructor?->name ? explode(' ', $session->instructor->name)[0] : '' }}</span>
                            <span>{{ $session->occupying_bookings_count }}/{{ $session->capacity }}</span>
                        </div>
                    </a>
                @empty
                    <div class="none">Sense classes</div>
                @endforelse
            </section>
        @endforeach
    </div>
@endsection
