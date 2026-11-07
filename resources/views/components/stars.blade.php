@props(['value'])
@php $n = (int) round((float) $value); @endphp
@if ($value)
    <span class="stars" title="{{ number_format((float) $value, 1, ',', '') }} de 5">{{ str_repeat('★', $n) }}<span class="off">{{ str_repeat('★', 5 - $n) }}</span></span>
@else
    <span class="muted">—</span>
@endif
