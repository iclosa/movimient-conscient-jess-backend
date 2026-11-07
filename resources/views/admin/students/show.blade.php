@extends('admin.layouts.app')
@section('title', $student->name)

@section('content')
    @php
        $initials = collect(explode(' ', $student->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
        $statusTones = ['booked' => 'info', 'waitlist' => 'warning', 'cancelled' => 'danger', 'attended' => 'success', 'no_show' => 'danger'];
    @endphp

    <x-page-header :title="$student->name"
        :subtitle="trim($student->email.($student->phone ? ' · '.$student->phone : ''))"
        :back="route('admin.students.index')" backLabel="Alumnes">
        <x-slot:actions>
            @unless ($student->is_active)<x-badge tone="danger">Compte desactivat</x-badge>@endunless
            <a href="{{ route('admin.payments.create', ['user' => $student->id]) }}" class="btn ghost"><x-icon name="euro" class="sm" /> Registra cobrament</a>
            <a href="{{ route('admin.students.edit', $student) }}" class="btn primary"><x-icon name="edit" class="sm" /> Edita</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid cols-4">
        <div class="card stat" style="--tint:var(--teal-100)">
            <div class="label">Classes fetes</div>
            <div class="value">{{ $streak['total_classes'] }}</div>
            <div class="sub">{{ $streak['classes_this_week'] }} aquesta setmana</div>
        </div>
        <div class="card stat" style="--tint:#fdf1da">
            <div class="label">Ratxa de pràctica</div>
            <div class="value">{{ $streak['weeks'] }} <span class="muted" style="font-size:18px">setm.</span></div>
            <div class="sub">Setmanes seguides amb classe</div>
        </div>
        <div class="card stat">
            <div class="label">Bo actiu</div>
            <div class="value" style="font-size:26px;margin-top:10px">{{ $student->userPasses->filter(fn ($p) => $p->isUsable())->isEmpty() ? 'Cap' : $student->load('userPasses')->pass_summary }}</div>
            <div class="sub">Pot reservar amb bo</div>
        </div>
        <div class="card stat" style="--tint:#e3f4ea">
            <div class="label">Total cobrat</div>
            <div class="value">@money($paidTotal)</div>
            <div class="sub">Des de {{ $student->created_at->format('m/Y') }}</div>
        </div>
    </div>

    <div class="grid main-side" style="margin-top:20px">
        <div class="stack">
            <div class="card">
                <div class="card-head"><h2>Properes reserves</h2></div>
                @if ($upcoming->count())
                    <ul class="list">
                        @foreach ($upcoming as $booking)
                            <li>
                                <span class="dot" style="--c:{{ $booking->classSession->activity->color }}"></span>
                                <a class="grow" href="{{ route('admin.sessions.show', $booking->classSession) }}" style="color:inherit">
                                    <span class="title">{{ $booking->classSession->activity->name }}@if ($booking->child) · {{ $booking->child->name }}@endif</span>
                                    <span class="sub">{{ ucfirst($booking->classSession->starts_at->isoFormat('ddd D MMM · HH:mm')) }}</span>
                                </a>
                                <x-badge :tone="$statusTones[$booking->status] ?? 'muted'">{{ $booking->status_label }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty" style="padding:28px"><p style="margin:0">No té cap reserva pròxima.</p></div>
                @endif
            </div>

            <div class="card">
                <div class="card-head"><h2>Historial recent</h2></div>
                @if ($history->count())
                    <ul class="list">
                        @foreach ($history as $booking)
                            <li>
                                <span class="dot" style="--c:{{ $booking->classSession->activity->color }}"></span>
                                <span class="grow">
                                    <span class="title">{{ $booking->classSession->activity->name }}@if ($booking->child) · {{ $booking->child->name }}@endif</span>
                                    <span class="sub">{{ $booking->classSession->starts_at->format('d/m/Y H:i') }}</span>
                                </span>
                                <x-stars :value="$booking->rating" />
                                <x-badge :tone="$statusTones[$booking->status] ?? 'muted'">{{ $booking->status_label }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty" style="padding:28px"><p style="margin:0">Encara no ha fet cap classe.</p></div>
                @endif
            </div>

            <div class="card">
                <div class="card-head"><h2>Últims cobraments</h2><a href="{{ route('admin.payments.index', ['q' => $student->email]) }}" class="btn ghost sm">Veure tots</a></div>
                @if ($payments->count())
                    <div class="table-wrap">
                        <table class="table">
                            <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $payment->concept }}</td>
                                    <td>{{ \App\Models\Payment::METHODS[$payment->method] ?? $payment->method }}</td>
                                    <td><strong>@money($payment->amount_cents)</strong></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty" style="padding:28px"><p style="margin:0">Cap cobrament registrat.</p></div>
                @endif
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2>Bons</h2></div>
                <ul class="list">
                    @forelse ($student->userPasses->sortByDesc('id') as $userPass)
                        <li style="align-items:flex-start">
                            <x-icon name="ticket" />
                            <span class="grow">
                                <span class="title">{{ $userPass->name }}</span>
                                <span class="sub">
                                    {{ $userPass->remaining_label }} classes
                                    · caduca {{ $userPass->expires_on?->format('d/m/Y') ?? 'mai' }}
                                </span>
                            </span>
                            @php
                                $passStatus = $userPass->status === 'active' && ! $userPass->isUsable() ? 'expired' : $userPass->status;
                                $passTones = ['active' => ['Actiu', 'success'], 'expired' => ['Caducat', 'muted'], 'exhausted' => ['Esgotat', 'muted'], 'cancelled' => ['Anul·lat', 'danger']];
                            @endphp
                            <x-badge :tone="$passTones[$passStatus][1]">{{ $passTones[$passStatus][0] }}</x-badge>
                            @if ($userPass->status === 'active')
                                <form method="post" action="{{ route('admin.user-passes.destroy', $userPass) }}" data-confirm="Anul·lar aquest bo?">@csrf @method('delete')
                                    <button class="btn ghost icon-only" type="submit" aria-label="Anul·la el bo"><x-icon name="x" class="sm" /></button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li><span class="muted">Aquesta persona encara no té cap bo.</span></li>
                    @endforelse
                </ul>

                <form method="post" action="{{ route('admin.user-passes.store', $student) }}" class="card-pad" style="display:grid;gap:14px;border-top:1px solid var(--line)">
                    @csrf
                    <strong style="font-family:var(--font-head)">Assigna un bo</strong>
                    <div class="field">
                        <label class="lbl" for="pass_id">Bo</label>
                        <select id="pass_id" name="pass_id" required>
                            @foreach ($catalog as $pass)
                                <option value="{{ $pass->id }}">{{ $pass->name }} · {{ $pass->price_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-grid" style="gap:14px">
                        <div class="field">
                            <label class="lbl" for="starts_on">Comença</label>
                            <input id="starts_on" type="date" name="starts_on" value="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label class="lbl" for="method">Pagament</label>
                            <select id="method" name="method">
                                @foreach (['cash' => 'Efectiu', 'bizum' => 'Bizum', 'transfer' => 'Transferència', 'card' => 'Targeta', 'other' => 'Altres', 'none' => 'Sense cobrar'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="amount">Import cobrat</label>
                        <div class="input-addon"><input id="amount" type="text" inputmode="decimal" name="amount" placeholder="Preu del bo"><span>€</span></div>
                        <span class="hint">Buit = el preu del catàleg.</span>
                    </div>
                    <button class="btn primary" type="submit" @disabled($catalog->isEmpty())><x-icon name="plus" class="sm" /> Assigna</button>
                </form>
            </div>

            <div class="card">
                <div class="card-head"><h2>Fills i filles</h2></div>
                <ul class="list">
                    @forelse ($student->children as $child)
                        <li>
                            <x-icon name="heart" class="sm" />
                            <span class="grow"><span class="title">{{ $child->name }}</span><span class="sub">{{ $child->birth_date ? $child->birth_date->age.' anys' : 'Edat sense indicar' }}</span></span>
                            <form method="post" action="{{ route('admin.children.destroy', $child) }}" data-confirm="Eliminar el perfil de {{ $child->name }}?">@csrf @method('delete')
                                <button class="btn ghost icon-only" type="submit" aria-label="Elimina"><x-icon name="trash" class="sm" /></button>
                            </form>
                        </li>
                    @empty
                        <li><span class="muted">Sense perfils d'infants.</span></li>
                    @endforelse
                </ul>
                <form method="post" action="{{ route('admin.students.children.store', $student) }}" class="card-pad" style="display:grid;gap:12px;border-top:1px solid var(--line)">
                    @csrf
                    <div class="form-grid" style="gap:12px">
                        <div class="field"><label class="lbl" for="child-name">Nom</label><input id="child-name" name="name" required></div>
                        <div class="field"><label class="lbl" for="child-birth">Naixement</label><input id="child-birth" type="date" name="birth_date"></div>
                    </div>
                    <button class="btn" type="submit"><x-icon name="plus" class="sm" /> Afegeix</button>
                </form>
            </div>

            @if ($student->notes)
                <div class="card card-pad">
                    <h2 style="font-size:17px;margin-bottom:8px">Notes</h2>
                    <p style="margin:0;white-space:pre-line">{{ $student->notes }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
