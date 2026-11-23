<div class="mb-2">
    <label class="text-sm d-block" for="{{ $fieldId }}-photo">Image file</label>
    <input type="file" name="photo" id="{{ $fieldId }}-photo" class="form-control-file text-sm"
        accept="image/jpeg,image/png,image/webp" required>
</div>
@include('admin.events.photos.description-fields')
