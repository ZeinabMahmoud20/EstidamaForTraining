@props(['name', 'label', 'checked' => false])
<div class="field {{ $attributes->get('wrapper') }}">
    <label class="check" for="{{ $name }}" style="font-weight:400">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" @checked((bool) old($name, $checked))>
        {{ $label }}
    </label>
</div>
