<?php
/** @var array<string, array<string, mixed>> $sections */
/** @var array<string, string> $values */
/** @var array<int, array<string, string>> $summary */
/** @var array<int, string> $departments */
/** @var array<int, string> $positions */
/** @var array<int, string> $roles */
?>

<section class="report-page system-info-page">
    <header class="report-hero">
        <div>
            <span>Quản lý hệ thống</span>
            <h1>Thông tin hệ thống</h1>
            <p>Cấu hình thông tin công ty, ngân hàng, vận hành, chính sách và dữ liệu nền tảng cho NovaOne.</p>
        </div>
        <div class="report-hero-actions">
            <a class="report-btn soft" href="?route=permissions"><?= ui_icon('settings') ?> Phân quyền</a>
            <a class="report-btn primary" href="?route=dashboard"><?= ui_icon('pie') ?> Dashboard</a>
        </div>
    </header>

    <?php if (! empty($_SESSION['flash_success'])): ?>
        <div class="flash success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (! empty($_SESSION['flash_error'])): ?>
        <div class="flash error"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div class="report-summary-grid">
        <?php foreach ($summary as $item): ?>
            <article class="report-kpi tone-<?= e($item['tone']) ?>">
                <span><?= e($item['label']) ?></span>
                <strong><?= e($item['value']) ?></strong>
                <small><?= e($item['hint']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>

    <form method="post" action="?route=settings.save" class="system-info-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

        <?php foreach ($sections as $section): ?>
            <section class="report-section system-info-section">
                <div class="report-section-head">
                    <div class="system-section-title">
                        <span class="system-section-icon"><?= ui_icon((string) $section['icon']) ?></span>
                        <div>
                            <h2><?= e($section['title']) ?></h2>
                            <p><?= e($section['description']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="system-info-grid">
                    <?php foreach ($section['fields'] as $field): ?>
                        <?php
                            $key = (string) $field['key'];
                            $type = (string) $field['type'];
                            $current = $values[$key] ?? '';
                        ?>
                        <label class="field <?= $type === 'textarea' ? 'span-2' : '' ?>">
                            <span><?= e($field['label']) ?><?= ! empty($field['required']) ? ' *' : '' ?></span>
                            <?php if ($type === 'textarea'): ?>
                                <textarea name="<?= e($key) ?>" rows="4" <?= ! empty($field['required']) ? 'required' : '' ?>><?= e($current) ?></textarea>
                            <?php elseif ($type === 'select'): ?>
                                <select name="<?= e($key) ?>" <?= ! empty($field['required']) ? 'required' : '' ?>>
                                    <?php foreach (($field['options'] ?? []) as $option): ?>
                                        <option value="<?= e($option) ?>" <?= $current === $option ? 'selected' : '' ?>>
                                            <?= e(($field['option_labels'][$option] ?? null) ?: label_value((string) $option)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input name="<?= e($key) ?>" type="<?= e($type) ?>" value="<?= e($current) ?>" <?= ! empty($field['required']) ? 'required' : '' ?>>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="system-savebar">
            <button class="employee-action teal" type="submit"><?= ui_icon('save') ?> Lưu thông tin hệ thống</button>
        </div>
    </form>

    <section class="report-section">
        <div class="report-section-head">
            <div>
                <h2>Danh mục nền tảng</h2>
                <p>Các dữ liệu dùng chung đang được hệ thống lấy từ danh sách nhân sự và phân quyền.</p>
            </div>
        </div>
        <div class="system-directory-grid">
            <article class="system-directory-card">
                <h3>Phòng ban</h3>
                <div class="system-chip-list">
                    <?php foreach (array_slice($departments, 0, 12) as $department): ?>
                        <span><?= e($department) ?></span>
                    <?php endforeach; ?>
                    <?php if ($departments === []): ?><em>Chưa có dữ liệu</em><?php endif; ?>
                </div>
            </article>
            <article class="system-directory-card">
                <h3>Chức danh</h3>
                <div class="system-chip-list">
                    <?php foreach (array_slice($positions, 0, 12) as $position): ?>
                        <span><?= e($position) ?></span>
                    <?php endforeach; ?>
                    <?php if ($positions === []): ?><em>Chưa có dữ liệu</em><?php endif; ?>
                </div>
            </article>
            <article class="system-directory-card">
                <h3>Vai trò người dùng</h3>
                <div class="system-chip-list">
                    <?php foreach ($roles as $role): ?>
                        <span><?= e($role) ?></span>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>
</section>
