@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'placeholder' => 'اختر...', 'required' => false, 'hint' => null])
@php($current = (string) old($name, $selected))
<div class="field {{ $attributes->get('wrapper') }}">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required)<span class="req">*</span>@endif</label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" @required($required)
            {{ $attributes->except(['wrapper'])->merge(['class' => 'control' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
        @if ($placeholder !== false)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected($current !== '' && $current === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
    @error($name)<div class="error">{{ $message }}</div>@enderror
</div>
