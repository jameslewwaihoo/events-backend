<div class="mb-2">
    <label class="text-sm" for="{{ $fieldId }}-alt">Photo description (optional)</label>
    <input type="text" name="alt" id="{{ $fieldId }}-alt" class="form-control form-control-sm"
        maxlength="255" value="{{ $description ?? '' }}">
</div>
<details class="mb-2" @if (!empty($descriptionZh)) open @endif>
    <summary class="text-sm">Chinese description (optional)</summary>
    <label class="sr-only" for="{{ $fieldId }}-alt-zh">Chinese description</label>
    <input type="text" name="alt_zh" id="{{ $fieldId }}-alt-zh" lang="zh-Hans"
        class="form-control form-control-sm mt-2" maxlength="255" value="{{ $descriptionZh ?? '' }}">
</details>
