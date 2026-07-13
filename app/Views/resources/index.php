<?php
/** @var string $name */
/** @var string $query */
/** @var string|null $routeName */
/** @var array<string, mixed> $schema */
/** @var array<int, array<string, mixed>> $items */
$routeName = $routeName ?? 'resource';
$useEditDialog = true;
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2><?= e($schema['title']) ?></h2>
            <p><?= e($schema['subtitle']) ?></p>
        </div>
        <a class="btn primary" href="#form">Thêm mới</a>
    </div>
    <div class="panel-body">
        <form class="toolbar" method="get">
            <input type="hidden" name="route" value="<?= e($routeName) ?>">
            <?php if ($routeName === 'resource'): ?><input type="hidden" name="name" value="<?= e($name) ?>"><?php endif; ?>
            <input class="search-input" name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm...">
            <button class="btn" type="submit">Lọc</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <?php foreach ($schema['columns'] as $column): ?>
                        <th><?= e($column['label']) ?></th>
                    <?php endforeach; ?>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <?php foreach ($schema['columns'] as $column): ?>
                            <td>
                                <?php
                                $raw = $item[$column['name']] ?? '';
                                echo match ($column['format'] ?? 'text') {
                                    'badge' => badge((string) $raw),
                                    'money' => e(money($raw)),
                                    default => e($raw),
                                };
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ($useEditDialog): ?>
                                <?php $dialogId = 'resource-edit-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($name . '-' . ($item['id'] ?? uid()))); ?>
                                <button class="btn resource-edit-trigger" type="button" data-resource-dialog="<?= e($dialogId) ?>">Sửa</button>
                                <dialog class="employee-dialog resource-dialog" id="<?= e($dialogId) ?>">
                                    <form method="post" action="?route=resource.save" class="employee-dialog-form resource-dialog-form">
                                        <header>
                                            <h3>Sửa <?= e($schema['title']) ?></h3>
                                            <button type="button" data-resource-dialog-close aria-label="Đóng">&times;</button>
                                        </header>
                                        <div class="employee-form-grid">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="_resource" value="<?= e($name) ?>">
                                            <input type="hidden" name="_return" value="<?= e($routeName) ?>">
                                            <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">

                                            <?php foreach ($schema['fields'] as $field): ?>
                                                <?php $current = $item[$field['name']] ?? ''; ?>
                                                <label>
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
                                        </div>
                                        <footer>
                                            <span></span>
                                            <button class="employee-action blue" type="button" data-resource-dialog-close>Hủy</button>
                                            <button class="employee-action teal" type="submit"><?= ui_icon('save') ?> Lưu</button>
                                        </footer>
                                    </form>
                                </dialog>
                            <?php else: ?>
                                <details class="inline-edit">
                                    <summary class="btn">Sửa</summary>
                                    <?php $editing = $item; require BASE_PATH . '/app/Views/resources/form.php'; ?>
                                </details>
                            <?php endif; ?>
                            <form method="post" action="?route=resource.delete" class="inline">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="_resource" value="<?= e($name) ?>">
                                <input type="hidden" name="_return" value="<?= e($routeName) ?>">
                                <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                <button class="btn danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($items) === 0): ?>
                    <tr><td colspan="<?= count($schema['columns']) + 1 ?>" class="empty">Không có dữ liệu phù hợp.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php if ($useEditDialog): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-resource-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.resourceDialog || '');
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-resource-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('.resource-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });
});
</script>
<?php endif; ?>

<section class="panel" id="form">
    <div class="panel-head"><h2>Thêm mới</h2><p><?= e($schema['title']) ?></p></div>
    <div class="panel-body">
        <?php $editing = null; require BASE_PATH . '/app/Views/resources/form.php'; ?>
    </div>
</section>
