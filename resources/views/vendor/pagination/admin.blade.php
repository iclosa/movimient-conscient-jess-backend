@if ($paginator->hasPages())
    <div class="pager">
        <span>Mostrant {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
        <nav role="navigation" aria-label="Paginació">
            @if ($paginator->onFirstPage())
                <span class="pg disabled"><x-icon name="chevron-left" class="sm" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Pàgina anterior"><x-icon name="chevron-left" class="sm" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pg disabled">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pg current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Pàgina següent"><x-icon name="chevron-right" class="sm" /></a>
            @else
                <span class="pg disabled"><x-icon name="chevron-right" class="sm" /></span>
            @endif
        </nav>
    </div>
@endif
