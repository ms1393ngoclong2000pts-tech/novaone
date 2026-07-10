<?php
/** @var string $name */
/** @var string $query */
/** @var array<string, mixed> $schema */
/** @var array<int, array<string, mixed>> $items */
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
            <input type="hidden" name="route" value="resource">
            <input type="hidden" name="name" value="<?= e($name) ?>">
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
                            <details class="inline-edit">
                                <summary class="btn">Sửa</summary>
                                <?php $editing = $item; require BASE_PATH . '/app/Views/resources/form.php'; ?>
                            </details>
                            <form method="post" action="?route=resource.delete" class="inline">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="_resource" value="<?= e($name) ?>">
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

<section class="panel" id="form">
    <div class="panel-head"><h2>Thêm mới</h2><p><?= e($schema['title']) ?></p></div>
    <div class="panel-body">
        <?php $editing = null; require BASE_PATH . '/app/Views/resources/form.php'; ?>
    </div>
</section>
