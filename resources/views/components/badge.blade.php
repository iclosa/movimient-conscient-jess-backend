@props(['tone' => 'muted'])
<span {{ $attributes->merge(['class' => 'badge '.$tone]) }}>{{ $slot }}</span>
