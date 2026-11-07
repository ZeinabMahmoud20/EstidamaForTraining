@props(['type' => 'info'])
@php($icons = ['success' => 'fa-circle-check', 'error' => 'fa-circle-exclamation', 'warning' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'])
<div {{ $attributes->merge(['class' => 'alert alert-' . $type, 'role' => 'alert']) }}>
    <i class="fa-solid {{ $icons[$type] ?? $icons['info'] }}" style="margin-top:4px"></i>
    <div>{{ $slot }}</div>
</div>
