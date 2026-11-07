@props(['tone' => 'neutral'])
<span {{ $attributes->merge(['class' => 'badge' . ($tone !== 'neutral' ? ' badge-' . $tone : '')]) }}>{{ $slot }}</span>
