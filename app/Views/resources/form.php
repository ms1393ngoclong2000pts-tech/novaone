<?php
/** @var string $name */
/** @var array<string, mixed> $schema */
/** @var array<string, mixed>|null $editing */
?>
<form method="post" action="?route=resource.save" class="form-grid">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="_resource" value="<?= e($name) ?>">
    <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">

    <?php foreach ($schema['fields'] as $field): ?>
        <?php $current = $editing[$field['name']] ?? ''; ?>
        <label class="field">
            <span><?= e($field['label']) ?></span>
            <?php if ($field['type'] === 'select'): ?>
                <select name="<?= e($field['name']) ?>" required>
                    <?php foreach ($field['options'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $current === $option ? 'selected' : '' ?>><?= e(label_value($option)) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input name="<?= e($field['name']) ?>" type="<?= e($field['type']) ?>" value="<?= e($current) ?>" required>
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <div class="actions span-2">
        <button class="btn primary" type="submit">Lưu</button>
    </div>
</form>
