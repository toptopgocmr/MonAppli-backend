<div class="col-span-2">
    <label class="block text-sm mb-1">{{ $label }}</label>

    <input type="file"
           name="{{ $name }}"
           accept="image/*"
           onchange="previewImage(event, 'preview_{{ $name }}')"
           class="border p-2 rounded w-full
           @error($name) border-red-500 @enderror">

    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror

    <img id="preview_{{ $name }}"
         class="mt-3 w-32 h-32 object-cover rounded hidden">
</div>