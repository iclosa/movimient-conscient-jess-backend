@extends('admin.layouts.app')
@section('title', 'Ajustos')

@section('content')
    <x-page-header title="Ajustos" subtitle="Dades del centre i normes de reserva que s'apliquen a tota l'app." />

    @if ($errors->any())
        <div class="alert error" role="alert"><x-icon name="alert" />Revisa els camps marcats en vermell.</div>
    @endif

    <form method="post" action="{{ route('admin.settings.update') }}" class="card">
        @csrf @method('put')
        <div class="card-pad">
            <div class="form-grid">
                <div class="section-title">El centre</div>
                @foreach ([
                    ['center_name', 'Nom del centre', 'text', true],
                    ['center_address', 'Adreça', 'text', false],
                    ['center_phone', 'Telèfon', 'text', false],
                    ['center_email', 'Correu de contacte', 'email', false],
                    ['center_instagram', 'Instagram', 'text', false],
                    ['center_website', 'Pàgina web', 'url', false],
                    ['center_latitude', 'Latitud (per a la sortida del sol)', 'text', true],
                    ['center_longitude', 'Longitud (per a la sortida del sol)', 'text', true],
                    ['center_timezone', 'Zona horària', 'text', true],
                ] as [$key, $label, $type, $required])
                    <div class="field {{ $errors->has($key) ? 'has-error' : '' }}">
                        <label class="lbl" for="{{ $key }}">{{ $label }}@if ($required) <span class="req">*</span>@endif</label>
                        <input id="{{ $key }}" type="{{ $type }}" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" @required($required)>
                        @error($key)<span class="error">{{ $message }}</span>@enderror
                    </div>
                @endforeach

                <div class="section-title">Reserves</div>
                <div class="field {{ $errors->has('cancellation_hours') ? 'has-error' : '' }}">
                    <label class="lbl" for="cancellation_hours">Termini per cancel·lar (hores)</label>
                    <input id="cancellation_hours" type="number" min="0" name="cancellation_hours" value="{{ old('cancellation_hours', $values['cancellation_hours']) }}" required>
                    <span class="hint">Cancel·lant amb menys antelació, el bo no es retorna.</span>
                    @error('cancellation_hours')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field {{ $errors->has('booking_open_days') ? 'has-error' : '' }}">
                    <label class="lbl" for="booking_open_days">Es pot reservar amb (dies d'antelació)</label>
                    <input id="booking_open_days" type="number" min="1" name="booking_open_days" value="{{ old('booking_open_days', $values['booking_open_days']) }}" required>
                    @error('booking_open_days')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field {{ $errors->has('default_capacity') ? 'has-error' : '' }}">
                    <label class="lbl" for="default_capacity">Places per defecte</label>
                    <input id="default_capacity" type="number" min="1" name="default_capacity" value="{{ old('default_capacity', $values['default_capacity']) }}" required>
                    @error('default_capacity')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <span class="lbl" style="font-weight:600;font-size:14px">Llista d'espera</span>
                    <label class="toggle">
                        <input type="checkbox" name="waitlist_enabled" value="1" @checked(old('waitlist_enabled', $values['waitlist_enabled']) == '1')>
                        <span class="track"></span>
                        <span><strong>Activa</strong><span class="hint" style="display:block">Quan una classe és plena, es pot entrar a la cua.</span></span>
                    </label>
                </div>

                <div class="section-title">A l'app</div>
                <div class="field full">
                    <label class="lbl" for="welcome_message">Missatge de benvinguda</label>
                    <input id="welcome_message" name="welcome_message" value="{{ old('welcome_message', $values['welcome_message']) }}">
                </div>
                <div class="field full {{ $errors->has('terms_url') ? 'has-error' : '' }}">
                    <label class="lbl" for="terms_url">Enllaç de les condicions d'ús</label>
                    <input id="terms_url" type="url" name="terms_url" value="{{ old('terms_url', $values['terms_url']) }}">
                    @error('terms_url')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn primary"><x-icon name="check" class="sm" /> Desa els ajustos</button>
        </div>
    </form>
@endsection
