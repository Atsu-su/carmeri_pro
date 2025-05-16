@if ($paginator->hasPages())
    <nav class="c-pagination">
        <ul class="pagination-container">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="disabled page-item arrow-left" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span aria-hidden="true"></span>
                </li>
            @else
                <li class="page-item arrow-left">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')"></a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif

                {{-- $elementは複数の要素から成り立っていない（foreachは使わなくてもいいがindexがほしいから恐らく使っている） --}}
                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active page-item" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item arrow-right">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')"></a>
                </li>
            @else
                <li class="disabled page-item arrow-right" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span aria-hidden="true"></span>
                </li>
            @endif
        </ul>
    </nav>
@endif
