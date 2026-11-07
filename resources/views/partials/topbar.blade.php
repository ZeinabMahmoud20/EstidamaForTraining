@php
    $user = auth()->user();
    $roleLabels = config('menu.role_labels');
    $roleName = $user->getRoleNames()->map(fn ($r) => $roleLabels[$r] ?? $r)->implode('، ');
    $initial = mb_substr($user->full_name ?: $user->username, 0, 1);
@endphp
<header class="topbar">
    <button class="icon-btn" id="menuToggle" type="button" aria-label="فتح القائمة"><i class="fa-solid fa-bars"></i></button>
    <div class="topbar__spacer"></div>

    <div class="actions" role="group" aria-label="حجم الخط">
        <button class="icon-btn" type="button" data-font="down" title="تصغير الخط" aria-label="تصغير الخط">أ-</button>
        <button class="icon-btn" type="button" data-font="up" title="تكبير الخط" aria-label="تكبير الخط">أ+</button>
    </div>

    <details class="user-menu">
        <summary>
            <span class="avatar">{{ $initial }}</span>
            <span class="who">{{ $user->full_name ?: $user->username }}</span>
            <i class="fa-solid fa-chevron-down" style="font-size:.7rem"></i>
        </summary>
        <div class="user-menu__panel">
            <div class="user-menu__meta">
                <strong>{{ $user->full_name ?: $user->username }}</strong><br>
                <span class="muted">{{ $roleName ?: '—' }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</button>
            </form>
        </div>
    </details>
</header>
