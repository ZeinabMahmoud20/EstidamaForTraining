@props(['title' => null, 'footer' => null, 'flush' => false])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="card__head">
            <h3>{{ $title }}</h3>
            @isset($actions)<div class="actions">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'card__body' }}">{{ $slot }}</div>
    @if ($footer)<div class="card__foot">{{ $footer }}</div>@endif
</section>
