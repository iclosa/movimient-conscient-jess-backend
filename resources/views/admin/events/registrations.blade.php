@extends('admin.layouts.app')
@section('title', 'Inscrits · '.$event->title)

@section('content')
    <x-page-header :title="$event->title"
        :subtitle="ucfirst($event->starts_at->isoFormat('dddd D MMMM · HH:mm')).' · '.$event->confirmed_registrations_count.($event->capacity ? ' / '.$event->capacity : '').' places ocupades'"
        :back="route('admin.events.index')" backLabel="Tallers i retirs">
        <x-slot:actions>
            <a href="{{ route('admin.events.edit', $event) }}" class="btn ghost"><x-icon name="edit" class="sm" /> Edita</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid main-side">
        <div class="card">
            <div class="card-head"><h2>Inscrits ({{ $registrations->where('status', '!=', 'cancelled')->count() }})</h2></div>
            @if ($registrations->count())
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Persona</th><th>Estat</th><th>Pagament</th><th>Inscrita</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($registrations as $registration)
                            <tr>
                                <td><a class="cell-main" href="{{ route('admin.students.show', $registration->user) }}">{{ $registration->user->name }}<small>{{ $registration->user->email }}</small></a></td>
                                <td><x-badge tone="{{ ['confirmed' => 'success', 'waitlist' => 'warning', 'cancelled' => 'danger'][$registration->status] }}">{{ ['confirmed' => 'Confirmada', 'waitlist' => 'En espera', 'cancelled' => 'Cancel·lada'][$registration->status] }}</x-badge></td>
                                <td>
                                    @if ($registration->payment)
                                        <a href="{{ route('admin.payments.edit', $registration->payment) }}" class="cell-main">
                                            <x-badge :tone="$registration->payment->status_tone">{{ $registration->payment->status_label }}</x-badge>
                                            <small>{{ $registration->payment->amount_label }} · {{ $registration->payment->method_label }}</small>
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $registration->created_at->format('d/m/Y') }}</td>
                                <td class="actions">
                                    @if ($registration->status !== 'cancelled')
                                        <form method="post" action="{{ route('admin.events.unregister', $registration) }}" data-confirm="Cancel·lar aquesta inscripció?">@csrf @method('delete')
                                            <button class="btn danger icon-only" type="submit" aria-label="Cancel·la"><x-icon name="x" class="sm" /></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty"><div class="ring"><x-icon name="users" /></div><h3>Encara no hi ha inscrits</h3></div>
            @endif
        </div>

        <div class="card">
            <div class="card-head"><h2>Inscriu algú</h2></div>
            <form method="post" action="{{ route('admin.events.register', $event) }}" class="card-pad" style="display:grid;gap:16px">
                @csrf
                <div class="field">
                    <label class="lbl" for="user_id">Alumne/a</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Tria una persona…</option>
                        @foreach ($students as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn primary" type="submit"><x-icon name="plus" class="sm" /> Inscriu</button>
            </form>
        </div>
    </div>
@endsection
