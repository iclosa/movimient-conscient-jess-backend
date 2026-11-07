@extends('admin.layouts.app')
@section('title', 'Resum')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 13 ? 'Bon dia' : ($hour < 20 ? 'Bona tarda' : 'Bona nit');
        $firstName = explode(' ', auth()->user()->name)[0];
    @endphp

    <x-page-header :title="$greeting.', '.$firstName" :subtitle="ucfirst(now()->isoFormat('dddd D MMMM'))">
        <x-slot:actions>
            <a href="{{ route('admin.calendar') }}" class="btn ghost"><x-icon name="calendar" class="sm" /> Calendari</a>
            <a href="{{ route('admin.sessions.create') }}" class="btn primary"><x-icon name="plus" class="sm" /> Nova classe</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid cols-4">
        <div class="card stat" style="--tint:var(--purple-100);--accent:var(--purple-600)">
            <x-icon name="clock" class="ico" />
            <div class="label">Classes d'avui</div>
            <div class="value">{{ $stats['classes_today'] }}</div>
            <div class="sub">{{ $stats['bookings_today'] }} persones apuntades</div>
        </div>
        <div class="card stat" style="--tint:var(--teal-100);--accent:var(--teal-600)">
            <x-icon name="users" class="ico" />
            <div class="label">Ocupació d'aquesta setmana</div>
            <div class="value">{{ $stats['occupancy'] }}%</div>
            <div class="progress" style="margin-top:8px;--c:var(--teal-500)"><i style="width:{{ $stats['occupancy'] }}%"></i></div>
        </div>
        <div class="card stat" style="--tint:#fdf1da;--accent:var(--warning)">
            <x-icon name="user-plus" class="ico" />
            <div class="label">Alumnes nous (30 dies)</div>
            <div class="value">{{ $stats['new_students'] }}</div>
            <div class="sub">Valoració mitjana: {{ $stats['rating'] ? number_format($stats['rating'], 1, ',', '') : '—' }} / 5</div>
        </div>
        <div class="card stat" style="--tint:#e3f4ea;--accent:var(--success)">
            <x-icon name="euro" class="ico" />
            <div class="label">Cobrat aquest mes</div>
            <div class="value">@money($stats['revenue_month'])</div>
            <div class="sub">{{ $pendingPayments }} cobraments pendents</div>
        </div>
    </div>

    <div class="grid main-side" style="margin-top:20px">
        <div class="card">
            <div class="card-head">
                <h2>Properes classes</h2>
                <a href="{{ route('admin.sessions.index') }}" class="btn ghost sm">Veure-les totes</a>
            </div>
            @if ($upcoming->count())
                <ul class="list">
                    @foreach ($upcoming as $session)
                        @php $pct = $session->capacity ? min(100, (int) round($session->occupying_bookings_count / $session->capacity * 100)) : 0; @endphp
                        <li>
                            <span class="dot" style="--c:{{ $session->activity->color }};width:12px;height:12px"></span>
                            <span class="time">{{ $session->starts_at->format('H:i') }}</span>
                            <a href="{{ route('admin.sessions.show', $session) }}" class="grow" style="color:inherit">
                                <span class="title">{{ $session->display_title }}</span>
                                <span class="sub">{{ ucfirst($session->starts_at->isoFormat('dddd D')) }} · {{ $session->instructor?->name ?? 'Sense professor/a' }}</span>
                            </a>
                            @if ($session->waitlist_bookings_count)
                                <x-badge tone="warning">{{ $session->waitlist_bookings_count }} en espera</x-badge>
                            @endif
                            <div style="width:130px;text-align:right">
                                <div class="sub">{{ $session->occupying_bookings_count }} / {{ $session->capacity }}</div>
                                <div class="progress" style="--c:{{ $pct >= 100 ? 'var(--danger)' : $session->activity->color }}"><i style="width:{{ $pct }}%"></i></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="empty">
                    <div class="ring"><x-icon name="calendar" /></div>
                    <h3>No hi ha classes properes</h3>
                    <p>Genera-les des de l'horari setmanal.</p>
                    <a href="{{ route('admin.templates.index') }}" class="btn primary">Anar a l'horari</a>
                </div>
            @endif
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2>Cal tenir un ull</h2></div>
                <ul class="list">
                    <li>
                        <x-badge tone="{{ $waitlistCount ? 'warning' : 'muted' }}">{{ $waitlistCount }}</x-badge>
                        <span class="grow">Persones en llista d'espera</span>
                    </li>
                    @forelse ($expiringPasses as $pass)
                        <li>
                            <x-icon name="ticket" class="sm" />
                            <a href="{{ route('admin.students.show', $pass->user) }}" class="grow" style="color:inherit">
                                <span class="title">{{ $pass->user->name }}</span>
                                <span class="sub">
                                    {{ $pass->name }} ·
                                    @if (! $pass->isUnlimited() && $pass->classes_remaining <= 1) {{ $pass->classes_remaining }} classe restant
                                    @else caduca {{ $pass->expires_on->format('d/m') }}
                                    @endif
                                </span>
                            </a>
                        </li>
                    @empty
                        <li><x-icon name="check" class="sm" /><span class="grow muted">Cap bo a punt d'acabar.</span></li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-head"><h2>Últimes reserves</h2></div>
                <ul class="list">
                    @forelse ($latestBookings as $booking)
                        <li>
                            <span class="dot" style="--c:{{ $booking->classSession->activity->color }}"></span>
                            <span class="grow">
                                <span class="title">{{ $booking->attendee_name }}</span>
                                <span class="sub">{{ $booking->classSession->activity->name }} · {{ $booking->classSession->starts_at->format('d/m H:i') }}</span>
                            </span>
                            <x-badge tone="{{ ['booked' => 'success', 'waitlist' => 'warning', 'cancelled' => 'danger'][$booking->status] ?? 'muted' }}">{{ $booking->status_label }}</x-badge>
                        </li>
                    @empty
                        <li><span class="muted">Encara no hi ha reserves.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
