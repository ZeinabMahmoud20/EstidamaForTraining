@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])
<div class="field {{ $attributes->get('wrapper') }}">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required)<span class="req">*</span>@endif</label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required)
           {{ $attributes->except(['wrapper'])->merge(['class' => 'control' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
    @error($name)<div class="error">{{ $message }}</div>@enderror
</div>
