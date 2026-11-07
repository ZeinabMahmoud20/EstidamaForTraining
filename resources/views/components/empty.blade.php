@props(['icon' => 'fa-inbox', 'text' => 'لا توجد بيانات لعرضها.'])
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <i class="fa-regular {{ $icon }}"></i>
    <div>{{ $slot->isEmpty() ? $text : $slot }}</div>
</div>
