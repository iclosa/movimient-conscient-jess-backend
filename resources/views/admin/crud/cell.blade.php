@php
    $type = $col['type'] ?? 'text';
    $value = data_get($row, $col['key']);
    $url = isset($col['route']) ? route($col['route'], $row) : (($col['link'] ?? false) ? route($routeName.'.edit', $row) : null);
@endphp
@switch($type)
    @case('badge')
        @php [$label, $tone] = $col['map'][$value] ?? [$value, 'muted']; @endphp
        <x-badge :tone="$tone">{{ $label }}</x-badge>
        @break

    @case('bool')
        <x-badge :tone="$value ? 'success' : 'muted'">{{ $value ? ($col['yes'] ?? 'Sí') : ($col['no'] ?? 'No') }}</x-badge>
        @break

    @case('money')
        @money($value)
        @break

    @case('date')
        {{ $value?->format('d/m/Y') ?? '—' }}
        @break

    @case('datetime')
        @if ($url)<a href="{{ $url }}" class="cell-main">{{ $value?->format('d/m/Y H:i') ?? '—' }}</a>@else{{ $value?->format('d/m/Y H:i') ?? '—' }}@endif
        @break

    @case('color')
        <span style="display:inline-flex;align-items:center;gap:8px"><span class="dot" style="--c:{{ $value }};width:18px;height:18px"></span><span class="muted">{{ strtoupper((string) $value) }}</span></span>
        @break

    @case('image')
        @if ($value)
            <img class="thumb" src="{{ $value }}" alt="">
        @else
            <span class="muted">—</span>
        @endif
        @break

    @case('dot')
        <span style="display:inline-flex;align-items:center;gap:8px">
            <span class="dot" style="--c:{{ data_get($row, $col['color']) ?? '#7c5cbf' }}"></span>
            @if ($url)<a href="{{ $url }}" class="cell-main">{{ $value ?? '—' }}</a>@else{{ $value ?? '—' }}@endif
        </span>
        @break

    @case('stars')
        @if ($value)
            <span class="stars">{{ str_repeat('★', (int) $value) }}<span class="off">{{ str_repeat('★', 5 - (int) $value) }}</span></span>
        @else
            <span class="muted">—</span>
        @endif
        @break

    @default
        @php
            $text = is_scalar($value) ? (string) $value : '';
            $shown = $text === '' ? '—' : (($col['limit'] ?? null) ? \Illuminate\Support\Str::limit($text, $col['limit']) : $text);
        @endphp
        @if ($url)
            <a href="{{ $url }}" class="cell-main">{{ $shown }}</a>
        @else
            {{ $shown }}
        @endif
        @if (! empty($col['sub']) && data_get($row, $col['sub']))
            <small class="muted" style="display:block">{{ \Illuminate\Support\Str::limit((string) data_get($row, $col['sub']), 70) }}</small>
        @endif
@endswitch
