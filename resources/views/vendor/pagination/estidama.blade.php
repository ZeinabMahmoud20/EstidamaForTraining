@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقل بين الصفحات">
        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li class="disabled"><span>السابق</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a></li>
            @else
                <li class="disabled"><span>التالي</span></li>
            @endif
        </ul>
    </nav>
@endif
