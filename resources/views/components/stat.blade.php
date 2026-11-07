@props(['label', 'value', 'icon' => 'fa-chart-simple', 'tone' => ''])
<div {{ $attributes->merge(['class' => 'card stat' . ($tone ? ' stat--' . $tone : '')]) }}>
    <div class="stat__icon"><i class="fa-solid {{ $icon }}"></i></div>
    <div>
        <div class="stat__value">{{ $value }}</div>
        <div class="stat__label">{{ $label }}</div>
    </div>
</div>
