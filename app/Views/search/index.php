<section class="panel search-panel">
    <div class="panel-head">
        <div>
            <h2>Tìm kiếm tính năng</h2>
            <p>Tìm nhanh các ô chức năng trên màn hình quản lý ứng dụng.</p>
        </div>
    </div>
    <div class="panel-body">
        <form class="toolbar" method="get">
            <input type="hidden" name="route" value="search">
            <input class="search-input" name="q" value="<?= e($query) ?>" placeholder="Nhập tên tính năng: nhân sự, kho, bán hàng...">
            <button class="btn primary" type="submit">Tìm kiếm</button>
        </form>

        <?php if ($query === ''): ?>
            <div class="search-summary">Hiển thị tất cả tính năng.</div>
        <?php elseif (count($results) === 0): ?>
            <div class="empty">Không tìm thấy tính năng phù hợp với "<?= e($query) ?>".</div>
        <?php else: ?>
            <div class="search-summary">Tìm thấy <?= count($results) ?> tính năng cho "<?= e($query) ?>".</div>
        <?php endif; ?>

        <?php if (count($results) > 0): ?>
            <div class="feature-results">
                <?php foreach ($results as $result): ?>
                    <a class="feature-result" href="<?= e($result['href']) ?>">
                        <?= ui_icon($result['icon']) ?>
                        <span>
                            <strong><?= e($result['label']) ?></strong>
                            <small><?= e($result['group']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
