@php
    $type = $f['type'] ?? 'text';
@endphp

@if ($type === 'section')
    <div class="section-title">{{ $f['label'] }}</div>
@else
    @php
        $name = $f['name'];
        $id = 'f-'.$name;
        $raw = old($name, $model->exists ? $model->getAttribute($name) : ($model->getAttribute($name) ?? ($f['default'] ?? null)));

        $value = match ($type) {
            'money' => is_numeric($raw) && ! old($name) ? str_replace('.', ',', number_format($raw / 100, 2, '.', '')) : $raw,
            'datetime' => $raw instanceof \Carbon\CarbonInterface ? $raw->format('Y-m-d\TH:i') : $raw,
            'date' => $raw instanceof \Carbon\CarbonInterface ? $raw->format('Y-m-d') : $raw,
            'password' => '',
            default => is_array($raw) ? json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $raw,
        };
        $inputType = match ($type) {
            'datetime' => 'datetime-local',
            'money' => 'text',
            default => $type,
        };
        $attrs = $f['attrs'] ?? [];
    @endphp

    <div class="field {{ ($f['full'] ?? false) || in_array($type, ['textarea', 'image']) ? 'full' : '' }} {{ $errors->has($name) ? 'has-error' : '' }}">
        @if ($type !== 'toggle')
            <label class="lbl" for="{{ $id }}">{{ $f['label'] }}@if ($f['required'] ?? false) <span class="req" aria-hidden="true">*</span>@endif</label>
        @endif

        @switch($type)
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $f['rows'] ?? 4 }}" @required($f['required'] ?? false)>{{ $value }}</textarea>
                @break

            @case('select')
                <select id="{{ $id }}" name="{{ $name }}" @required($f['required'] ?? false)>
                    @if ($f['nullable'] ?? false)
                        <option value="">{{ $f['placeholder'] ?? '—' }}</option>
                    @endif
                    @foreach ($f['options'] as $optValue => $optLabel)
                        <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                    @endforeach
                </select>
                @break

            @case('color')
                <div class="color-field">
                    <input type="color" id="{{ $id }}" name="{{ $name }}" value="{{ $value ?: '#7c5cbf' }}" data-hex="hex-{{ $name }}">
                    <code id="hex-{{ $name }}"></code>
                </div>
                @break

            @case('toggle')
                <label class="toggle">
                    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) $value)>
                    <span class="track"></span>
                    <span><strong>{{ $f['label'] }}</strong>@isset($f['hint'])<span class="hint" style="display:block">{{ $f['hint'] }}</span>@endisset</span>
                </label>
                @break

            @case('image')
                @php $current = $model->exists ? $model->getAttribute($name) : null; @endphp
                <div class="image-field">
                    <img id="prev-{{ $name }}" src="{{ $current ? \Illuminate\Support\Facades\Storage::disk(config('filesystems.media_disk'))->url($current) : '' }}" alt="" @unless($current) hidden @endunless>
                    <div style="display:grid;gap:8px;flex:1">
                        <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/*" data-preview="prev-{{ $name }}">
                        @if ($current)
                            <label style="display:flex;gap:8px;align-items:center;font-size:14px"><input type="checkbox" name="{{ $name }}_remove" value="1"> Treu la imatge actual</label>
                        @endif
                    </div>
                </div>
                @break

            @case('money')
                <div class="input-addon">
                    <input type="text" inputmode="decimal" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" placeholder="0,00" @required($f['required'] ?? false)>
                    <span>€</span>
                </div>
                @break

            @default
                <input type="{{ $inputType }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                       @if (isset($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif
                       @foreach ($attrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach
                       @required($f['required'] ?? false)
                       @if ($type === 'password') autocomplete="new-password" @endif>
        @endswitch

        @if (isset($f['hint']) && $type !== 'toggle')
            <span class="hint">{{ $f['hint'] }}</span>
        @endif
        @error($name)<span class="error">{{ $message }}</span>@enderror
    </div>
@endif
