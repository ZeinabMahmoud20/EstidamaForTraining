@props(['name', 'label' => null, 'value' => null, 'required' => false, 'hint' => null])
<div class="field {{ $attributes->get('wrapper') }}">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required)<span class="req">*</span>@endif</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" @required($required)
              {{ $attributes->except(['wrapper'])->merge(['class' => 'control' . ($errors->has($name) ? ' is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
    @error($name)<div class="error">{{ $message }}</div>@enderror
</div>
