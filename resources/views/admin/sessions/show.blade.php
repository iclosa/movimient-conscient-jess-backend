@extends('admin.layouts.app')
@section('title', $session->display_title)

@section('content')
    @php
        $pct = $session->capacity ? min(100, (int) round($session->occupying_bookings_count / $session->capacity * 100)) : 0;
        $payBadges = ['pass' => ['Bo', 'primary'], 'free' => ['Gratuïta', 'teal'], 'cash' => ['Pagada en mà', 'success'], 'pending' => ['Pendent', 'warning']];
    @endphp

    <x-page-header
        :title="$session->display_title"
        :subtitle="ucfirst($session->starts_at->isoFormat('dddd D MMMM')).' · '.$session->starts_at->format('H:i').' – '.$session->ends_at->format('H:i')"
        :back="route('admin.sessions.index')" backLabel="Classes">
        <x-slot:actions>
            <a href="{{ route('admin.sessions.edit', $session) }}" class="btn ghost"><x-icon name="edit" class="sm" /> Edita</a>
            @if ($session->isCancelled())
                <form method="post" action="{{ route('admin.sessions.reactivate', $session) }}">@csrf
                    <button class="btn teal" type="submit"><x-icon name="check" class="sm" /> Torna a programar</button>
                </form>
            @else
                <form method="post" action="{{ route('admin.sessions.cancel', $session) }}" data-confirm="Cancel·lar la classe alliberarà totes les reserves i retornarà els bons. Continuar?">@csrf
                    <button class="btn danger" type="submit"><x-icon name="ban" class="sm" /> Cancel·la la classe</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($session->isCancelled())
        <div class="alert error"><x-icon name="ban" />Aquesta classe està cancel·lada i no es pot reservar.</div>
    @endif

    <div class="grid cols-4">
        <div class="card stat" style="--tint:{{ $session->activity->color }}22">
            <div class="label">Places ocupades</div>
            <div class="value">{{ $session->occupying_bookings_count }} <span class="muted" style="font-size:20px">/ {{ $session->capacity }}</span></div>
            <div class="progress" style="margin-top:8px;--c:{{ $pct >= 100 ? 'var(--danger)' : $session->activity->color }}"><i style="width:{{ $pct }}%"></i></div>
        </div>
        <div class="card stat">
            <div class="label">Llista d'espera</div>
            <div class="value">{{ $waitlist->count() }}</div>
            <div class="sub">{{ $session->waitlistBookings()->exists() ? 'Entren sols si s\'allibera una plaça' : 'Ningú esperant' }}</div>
        </div>
        <div class="card stat">
            <div class="label">Professor/a · sala</div>
            <div class="value" style="font-size:20px;margin-top:10px">{{ $session->instructor?->name ?? '—' }}</div>
            <div class="sub">{{ $session->room?->name ?? 'Sense sala' }} · <span class="dot" style="--c:{{ $session->activity->color }}"></span> {{ $session->activity->name }}</div>
        </div>
        <div class="card stat">
            <div class="label">Valoració</div>
            <div class="value" style="font-size:28px">{{ $averageRating ? number_format($averageRating, 1, ',', '') : '—' }}</div>
            <div class="sub">Classe solta: {{ $session->price_label }}</div>
        </div>
    </div>

    <div class="grid main-side" style="margin-top:20px">
        <div class="card">
            <div class="card-head"><h2>Alumnat apuntat ({{ $bookings->count() }})</h2></div>
            @if ($bookings->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Persona</th><th>Pagament</th><th>Estat</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($bookings as $booking)
                            @php [$payLabel, $payTone] = $payBadges[$booking->payment_mode] ?? [$booking->payment_mode, 'muted']; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.students.show', $booking->user) }}" class="cell-main">{{ $booking->attendee_name }}
                                        @if ($booking->child)<small>Fill/a de {{ $booking->user->name }}</small>@else<small>{{ $booking->user->phone ?: $booking->user->email }}</small>@endif
                                    </a>
                                </td>
                                <td><x-badge :tone="$payTone">{{ $payLabel }}</x-badge></td>
                                <td>
                                    <x-badge tone="{{ ['booked' => 'info', 'attended' => 'success', 'no_show' => 'danger'][$booking->status] ?? 'muted' }}">{{ $booking->status_label }}</x-badge>
                                    @if ($booking->rating)<x-stars :value="$booking->rating" />@endif
                                </td>
                                <td class="actions">
                                    @if ($booking->status !== 'attended')
                                        <form method="post" action="{{ route('admin.bookings.attendance', $booking) }}">@csrf @method('patch')
                                            <input type="hidden" name="status" value="attended">
                                            <button class="btn teal sm" type="submit" title="Marca que ha vingut"><x-icon name="check" class="sm" /> Ha vingut</button>
                                        </form>
                                    @endif
                                    @if ($booking->status !== 'no_show')
                                        <form method="post" action="{{ route('admin.bookings.attendance', $booking) }}">@csrf @method('patch')
                                            <input type="hidden" name="status" value="no_show">
                                            <button class="btn ghost sm" type="submit" title="Marca que no s'ha presentat">No ha vingut</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" data-confirm="Cancel·lar aquesta reserva? Es retornarà el bo.">@csrf
                                        <button class="btn danger icon-only" type="submit" aria-label="Cancel·la la reserva"><x-icon name="x" class="sm" /></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty">
                    <div class="ring"><x-icon name="users" /></div>
                    <h3>Encara no hi ha ningú apuntat</h3>
                    <p>Pots apuntar algú des del quadre de la dreta.</p>
                </div>
            @endif
        </div>

        <div class="stack">
            @unless ($session->isCancelled())
                <div class="card">
                    <div class="card-head"><h2>Apunta algú</h2></div>
                    <form method="post" action="{{ route('admin.bookings.store', $session) }}" class="card-pad" style="display:grid;gap:16px">
                        @csrf
                        <div class="field">
                            <label class="lbl" for="person">Persona</label>
                            <select id="person" name="person" required>
                                <option value="">Tria una persona…</option>
                                @foreach ($students as $student)
                                    <option value="user:{{ $student->id }}" @disabled(in_array('user:'.$student->id, $takenKeys))>{{ $student->name }}@if (in_array('user:'.$student->id, $takenKeys)) (ja apuntat/da)@endif</option>
                                    @if ($session->activity->is_family)
                                        @foreach ($student->children as $child)
                                            <option value="child:{{ $child->id }}" @disabled(in_array('child:'.$child->id, $takenKeys))>&nbsp;&nbsp;↳ {{ $child->name }} (fill/a de {{ $student->name }})</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                            @error('person')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label class="lbl" for="payment">Com paga</label>
                            <select id="payment" name="payment">
                                <option value="auto">Amb el seu bo (o gratuïta)</option>
                                <option value="free">Convidada</option>
                                <option value="pending">Pendent de pagar</option>
                            </select>
                        </div>
                        <button class="btn primary" type="submit"><x-icon name="plus" class="sm" /> Apunta-la</button>
                        <p class="hint" style="margin:0">Si la classe és plena, entrarà a la llista d'espera.</p>
                    </form>
                </div>
            @endunless

            @if ($waitlist->count())
                <div class="card">
                    <div class="card-head"><h2>Llista d'espera</h2></div>
                    <ul class="list">
                        @foreach ($waitlist as $i => $booking)
                            <li>
                                <x-badge tone="warning">{{ $i + 1 }}</x-badge>
                                <span class="grow"><span class="title">{{ $booking->attendee_name }}</span><span class="sub">Des de {{ $booking->created_at->format('d/m H:i') }}</span></span>
                                <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}">@csrf
                                    <button class="btn ghost icon-only" type="submit" aria-label="Treu-la de la llista"><x-icon name="x" class="sm" /></button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($cancelled->count())
                <div class="card">
                    <div class="card-head"><h2>Cancel·lacions</h2></div>
                    <ul class="list">
                        @foreach ($cancelled as $booking)
                            <li>
                                <span class="grow"><span class="title">{{ $booking->attendee_name }}</span><span class="sub">{{ $booking->cancelled_at?->format('d/m H:i') }}</span></span>
                                @if ($booking->late_cancellation)<x-badge tone="danger">Fora de termini</x-badge>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($session->notes)
                <div class="card card-pad"><h2 style="font-size:17px;margin-bottom:8px">Notes</h2><p style="margin:0;white-space:pre-line">{{ $session->notes }}</p></div>
            @endif
        </div>
    </div>
@endsection
