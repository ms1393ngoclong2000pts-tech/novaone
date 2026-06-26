<?php
$schema = [
    'fields' => [
        ['name' => 'title', 'label' => 'Tên công việc', 'type' => 'text'],
        ['name' => 'project', 'label' => 'Dự án', 'type' => 'text'],
        ['name' => 'assignee', 'label' => 'Phụ trách', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => ['pending', 'in_progress', 'completed', 'canceled']],
        ['name' => 'deadline', 'label' => 'Deadline', 'type' => 'date'],
    ],
];
$name = 'tasks';
$columns = [
    'pending' => 'Chờ xử lý',
    'in_progress' => 'Đang làm',
    'completed' => 'Hoàn tất',
    'canceled' => 'Đã hủy',
];
?>
<section class="panel">
    <div class="panel-head">
        <div><h2>Quản lý dự án và công việc</h2><p>Giao việc, deadline, trạng thái và tiến độ</p></div>
        <a class="btn primary" href="#form">Thêm việc</a>
    </div>
    <div class="panel-body">
        <div class="kanban">
            <?php foreach ($columns as $status => $label): ?>
                <div class="kanban-col">
                    <h3><?= e($label) ?></h3>
                    <?php foreach ($items as $item): ?>
                        <?php if ($item['status'] === $status): ?>
                            <article class="task-card">
                                <strong><?= e($item['title']) ?></strong>
                                <div class="muted"><?= e($item['project']) ?> · <?= e($item['assignee']) ?></div>
                                <div class="actions task-actions">
                                    <span class="badge"><?= e($item['deadline']) ?></span>
                                    <details class="inline-edit">
                                        <summary class="btn">Sửa</summary>
                                        <?php $editing = $item; require BASE_PATH . '/app/Views/resources/form.php'; ?>
                                    </details>
                                    <form method="post" action="?route=resource.delete" class="inline">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="_resource" value="tasks">
                                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                        <button class="btn danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel" id="form">
    <div class="panel-head"><h2>Thêm công việc</h2><p>Dự án, người phụ trách và deadline</p></div>
    <div class="panel-body">
        <?php $editing = null; require BASE_PATH . '/app/Views/resources/form.php'; ?>
    </div>
</section>
