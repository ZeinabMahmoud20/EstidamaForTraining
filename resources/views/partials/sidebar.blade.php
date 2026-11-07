@php
    $user = auth()->user();
@endphp
<aside class="sidebar" id="sidebar" aria-label="القائمة الرئيسية">
    <a class="sidebar__brand" href="{{ route('dashboard') }}">
        <img src="{{ asset('images/logo.png') }}" alt="استدامة للتدريب والتطوير">
    </a>
    <nav class="sidebar__nav">
        @foreach (config('menu.groups') as $group)
            @php
                $items = collect($group['items'])->filter(fn ($i) => empty($i['can']) || $user->canAny($i['can']));
            @endphp
            @if ($items->isNotEmpty())
                <div class="nav-group">
                    @if ($group['title'])
                        <div class="nav-group__title">{{ $group['title'] }}</div>
                    @endif
                    @foreach ($items as $item)
                        @if (Route::has($item['route']))
                            @php
                                $pattern = str_contains($item['route'], '.') ? \Illuminate\Support\Str::before($item['route'], '.') . '.*' : $item['route'];
                            @endphp
                            <a class="nav-link {{ request()->routeIs($pattern) ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                                <i class="fa-solid {{ $item['icon'] }}"></i> {{ $item['label'] }}
                            </a>
                        @else
                            <span class="nav-link is-soon" title="قيد الإنشاء">
                                <i class="fa-solid {{ $item['icon'] }}"></i> {{ $item['label'] }}
                                <span class="nav-soon">قريبًا</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
</aside>
