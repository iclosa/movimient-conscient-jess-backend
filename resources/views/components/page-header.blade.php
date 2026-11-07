@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Tornar'])
<div class="page-header">
    <div>
        @if ($back)
            <div class="crumb"><x-icon name="arrow-left" class="sm" /><a href="{{ $back }}">{{ $backLabel }}</a></div>
        @endif
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="actions">{{ $actions }}</div>
    @endisset
</div>
