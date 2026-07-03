<?php
$queryUrl = function (array $changes = []): string {
    $query = array_merge($_GET, $changes);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    return '?' . http_build_query($query);
};
$sortUrl = function (string $column) use ($queryUrl, $sort, $direction): string {
    return $queryUrl(['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
};
$treeColumns = $serviceTree['columns'] ?? [];
$selectedIds = array_map('strval', $serviceTree['selectedIds'] ?? []);
$allServiceItems = $allItems ?? $items;
?>

<section class="service-panel">
    <header class="service-head">
        <h2>DANH MỤC NGÀNH HÀNG</h2>
        <div class="service-head-actions">
            <?php foreach ($levelLabels as $levelNumber => $label): ?>
                <a class="employee-action service-level-action level-<?= $levelNumber ?>" href="?route=services.create&level=<?= $levelNumber ?>">Thêm <?= e($levelButtonLabels[$levelNumber]) ?></a>
            <?php endforeach; ?>
        </div>
    </header>

    <div class="service-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="service-layout">
            <div class="service-tree">
                <?php foreach ($levelLabels as $levelNumber => $label): ?>
                    <section class="service-column level-<?= $levelNumber ?>">
                        <h3><?= e($label) ?></h3>
                        <div class="service-column-list">
                            <?php foreach ($treeColumns[$levelNumber] ?? [] as $node): ?>
                                <?php
                                $nodeId = (string) ($node['id'] ?? '');
                                $nodeName = (string) ($node['name'] ?? '');
                                $childCount = count(array_filter($allServiceItems, fn (array $item): bool => (string) ($item['parent'] ?? '') === $nodeName));
                                $isSelected = in_array($nodeId, $selectedIds, true);
                                ?>
                                <a class="service-node <?= $isSelected ? 'current' : '' ?>" href="<?= e($queryUrl(['selected_service' => $nodeId, 'page' => 1])) ?>">
                                    <span><?= e($node['name'] ?? '') ?></span>
                                    <?php if ($childCount > 0): ?><b>›</b><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <aside class="service-list">
                <div class="service-tools">
                    <label>Hiển thị
                        <select name="per_page" form="service-search" onchange="this.form.submit()">
                            <?php foreach ([10, 25, 50, 100] as $size): ?>
                                <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                            <?php endforeach; ?>
                        </select>
                        trên 1 trang
                    </label>
                    <form id="service-search" method="get">
                        <input type="hidden" name="route" value="services">
                        <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
                    </form>
                </div>

                <div class="service-table-wrap">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th><a href="<?= e($sortUrl('name')) ?>">Ngành hàng <span><?= $sort === 'name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <a class="service-name <?= ($item['status'] ?? 'active') === 'inactive' ? 'inactive' : '' ?>" href="?route=services.show&id=<?= e($item['id'] ?? '') ?>">
                                            <?= e($item['name'] ?? '') ?>
                                            <?php if (($item['status'] ?? 'active') === 'active'): ?><span>✓</span><?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <details class="service-actions">
                                            <summary>Thao tác</summary>
                                            <div class="service-action-menu">
                                                <form method="post" action="?route=services.toggle">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                                    <button class="service-action-warning" type="submit"><?= ($item['status'] ?? 'active') === 'active' ? 'Tắt Hiển thị' : 'Hiển thị' ?></button>
                                                </form>
                                                <a class="service-action-edit" href="?route=services.edit&id=<?= e($item['id'] ?? '') ?>">Sửa</a>
                                                <a class="service-action-detail" href="?route=services.show&id=<?= e($item['id'] ?? '') ?>">Xem chi tiết</a>
                                                <form method="post" action="?route=services.delete" onsubmit="return confirm('Xóa ngành hàng này?')">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                                    <button class="service-action-delete" type="submit">Xóa</button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($total === 0): ?>
                                <tr><td class="service-empty" colspan="2">Không có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <footer class="employee-pagination service-pagination">
                    <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span>
                    <nav>
                        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                        <?php for ($number = 1; $number <= $pages; $number++): ?>
                            <a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a>
                        <?php endfor; ?>
                        <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
                    </nav>
                </footer>
            </aside>
        </div>
    </div>
</section>
