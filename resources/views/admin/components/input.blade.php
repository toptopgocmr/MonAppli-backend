<div>
    <label class="block text-sm mb-1">{{ $label }}</label>

    <input type="{{ $type ?? 'text' }}"
           name="{{ $name }}"
           value="{{ old($name) }}"
           class="border p-2 rounded w-full
           @error($name) border-red-500 @enderror">

    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>