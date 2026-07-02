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
$statusClass = ['approved' => 'approved', 'rejected' => 'rejected', 'pending' => 'pending'];
$formatDate = fn (string $date): string => $date !== '' ? date('d-m-Y', strtotime($date)) : '';
?>

<section class="request-panel recruitment-panel">
    <header class="request-head">
        <h2><?= back_link('dashboard') ?>DANH SÁCH PHIẾU TUYỂN DỤNG</h2>
        <div class="request-head-actions">
            <button class="request-action teal" type="button" data-recruitment-open>Tạo phiếu tuyển dụng</button>
        </div>
    </header>

    <div class="request-body">
        <?php if (! empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?= e($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (! empty($_SESSION['flash_error'])): ?>
            <div class="alert"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="request-tools">
            <label>Hiển thị
                <select name="per_page" form="recruitment-search" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                trên 1 trang
            </label>
            <form id="recruitment-search" method="get">
                <input type="hidden" name="route" value="recruitment-requests">
                <input name="q" value="<?= e($query) ?>" placeholder="Tìm kiếm">
            </form>
        </div>

        <div class="request-table-wrap recruitment-table-wrap">
            <table class="request-table recruitment-table">
                <thead>
                    <tr>
                        <th><a href="<?= e($sortUrl('request_no')) ?>">Phiếu <span><?= $sort === 'request_no' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('request_date')) ?>">Thời gian <span><?= $sort === 'request_date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('cost')) ?>">Chi phí <span><?= $sort === 'cost' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('status')) ?>">Trạng thái <span><?= $sort === 'status' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('approver')) ?>">Người duyệt <span><?= $sort === 'approver' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('candidate_total')) ?>">Tổng ứng viên <span><?= $sort === 'candidate_total' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th><a href="<?= e($sortUrl('candidate_passed')) ?>">Ứng viên đạt <span><?= $sort === 'candidate_passed' ? ($direction === 'asc' ? '↑' : '↓') : '↕' ?></span></a></th>
                        <th>Thông tin tin tức</th>
                        <th>Phê duyệt</th>
                        <th>Ứng viên</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['request_no'] ?? '') ?></td>
                            <td><?= e($formatDate((string) ($item['request_date'] ?? ''))) ?></td>
                            <td><?= e(number_format((float) ($item['cost'] ?? 0), 0, ',', '.')) ?></td>
                            <td><strong class="request-approval <?= e($statusClass[$item['status'] ?? 'pending'] ?? 'pending') ?>"><?= e($approvals[$item['status'] ?? 'pending'] ?? 'Chờ duyệt') ?></strong></td>
                            <td><?= e($item['approver'] ?? '') ?></td>
                            <td><?= e($item['candidate_total'] ?? 0) ?></td>
                            <td><?= e($item['candidate_passed'] ?? 0) ?></td>
                            <td><button class="recruitment-icon info" type="button" title="Thông tin tin tức" data-recruitment-edit='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('info') ?></button></td>
                            <td>
                                <form method="post" action="?route=recruitment-requests.approval">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                                    <input type="hidden" name="status" value="<?= ($item['status'] ?? 'pending') === 'approved' ? 'pending' : 'approved' ?>">
                                    <button class="recruitment-icon approve" type="submit" title="Đổi trạng thái phê duyệt"><?= ui_icon('arrow') ?></button>
                                </form>
                            </td>
                            <td><button class="recruitment-icon candidates" type="button" title="Ứng viên" data-recruitment-candidates='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'><?= ui_icon('users') ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($total === 0): ?>
                        <tr><td class="request-empty" colspan="10">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="employee-pagination request-pagination">
            <span>Hiển thị <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?> tới <?= min($page * $perPage, $total) ?> trên <?= $total ?> mục</span>
            <nav>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => max(1, $page - 1)])) ?>">Trước</a>
                <?php for ($number = 1; $number <= $pages; $number++): ?>
                    <a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e($queryUrl(['page' => $number])) ?>"><?= $number ?></a>
                <?php endfor; ?>
                <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e($queryUrl(['page' => min($pages, $page + 1)])) ?>">Sau</a>
            </nav>
        </footer>
    </div>
</section>

<dialog id="recruitment-dialog" class="employee-dialog request-dialog">
    <form method="post" action="?route=recruitment-requests.save" class="employee-dialog-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="">
        <header><h3>Tạo phiếu tuyển dụng</h3><button type="button" data-recruitment-close aria-label="Đóng">×</button></header>
        <div class="employee-form-grid">
            <label><span>Phiếu</span><input name="request_no" placeholder="Tự sinh nếu bỏ trống"></label>
            <label><span>Thời gian</span><input name="request_date" type="date" required></label>
            <label><span>Chi phí</span><input name="cost" type="number" min="0" step="1000"></label>
            <label><span>Trạng thái</span><select name="status"><?php foreach ($approvals as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Người duyệt</span><select name="approver"><option value="">--- ---</option><option value="bData co.,ltd">bData co.,ltd</option><?php foreach ($employees as $employee): ?><option value="<?= e($employee['name'] ?? '') ?>"><?= e($employee['name'] ?? '') ?></option><?php endforeach; ?></select></label>
            <label><span>Vị trí tuyển</span><input name="position"></label>
            <label><span>Tổng ứng viên</span><input name="candidate_total" type="number" min="0"></label>
            <label><span>Ứng viên đạt</span><input name="candidate_passed" type="number" min="0"></label>
            <label class="contract-note"><span>Thông tin tin tức</span><input name="news_title"></label>
            <label class="contract-note"><span>Mô tả</span><textarea name="description" rows="4"></textarea></label>
        </div>
        <footer class="request-dialog-actions">
            <button class="employee-action danger recruitment-delete" type="button">Xóa</button>
            <span></span>
            <button class="employee-action" type="button" data-recruitment-close>Hủy</button>
            <button class="employee-action teal" type="submit">Lưu phiếu</button>
        </footer>
    </form>
</dialog>

<dialog id="recruitment-candidates-dialog" class="employee-dialog request-dialog">
    <form method="dialog" class="employee-dialog-form">
        <header><h3>Ứng viên</h3><button type="button" data-recruitment-candidates-close aria-label="Đóng">×</button></header>
        <div class="recruitment-candidate-summary">
            <p><strong>Phiếu:</strong> <span data-candidate-field="request_no"></span></p>
            <p><strong>Vị trí:</strong> <span data-candidate-field="position"></span></p>
            <p><strong>Tổng ứng viên:</strong> <span data-candidate-field="candidate_total"></span></p>
            <p><strong>Ứng viên đạt:</strong> <span data-candidate-field="candidate_passed"></span></p>
        </div>
        <footer><button class="employee-action teal" type="button" data-recruitment-candidates-close>Đóng</button></footer>
    </form>
</dialog>

<form id="recruitment-delete-form" method="post" action="?route=recruitment-requests.delete" hidden>
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id">
</form>

<script>
(() => {
    const dialog = document.getElementById('recruitment-dialog');
    const candidatesDialog = document.getElementById('recruitment-candidates-dialog');
    if (!dialog || !candidatesDialog) return;
    const form = dialog.querySelector('form');
    const deleteForm = document.getElementById('recruitment-delete-form');
    const deleteButton = dialog.querySelector('.recruitment-delete');
    const today = new Date().toISOString().slice(0, 10);

    document.querySelector('[data-recruitment-open]')?.addEventListener('click', () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.request_date.value = today;
        form.elements.status.value = 'pending';
        deleteButton.hidden = true;
        dialog.querySelector('h3').textContent = 'Tạo phiếu tuyển dụng';
        dialog.showModal();
    });

    document.querySelectorAll('[data-recruitment-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

    document.querySelectorAll('[data-recruitment-edit]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.recruitmentEdit);
        form.reset();
        Object.entries(item).forEach(([key, value]) => {
            if (form.elements[key]) form.elements[key].value = value ?? '';
        });
        deleteButton.hidden = false;
        dialog.querySelector('h3').textContent = 'Chi tiết phiếu tuyển dụng';
        dialog.showModal();
    }));

    document.querySelectorAll('[data-recruitment-candidates]').forEach(button => button.addEventListener('click', () => {
        const item = JSON.parse(button.dataset.recruitmentCandidates);
        candidatesDialog.querySelector('[data-candidate-field="request_no"]').textContent = item.request_no || '';
        candidatesDialog.querySelector('[data-candidate-field="position"]').textContent = item.position || '';
        candidatesDialog.querySelector('[data-candidate-field="candidate_total"]').textContent = item.candidate_total || 0;
        candidatesDialog.querySelector('[data-candidate-field="candidate_passed"]').textContent = item.candidate_passed || 0;
        candidatesDialog.showModal();
    }));
    document.querySelectorAll('[data-recruitment-candidates-close]').forEach(button => button.addEventListener('click', () => candidatesDialog.close()));

    deleteButton?.addEventListener('click', () => {
        if (!form.elements.id.value || !confirm('Xóa phiếu tuyển dụng này?')) return;
        deleteForm.elements.id.value = form.elements.id.value;
        deleteForm.submit();
    });
})();
</script>
