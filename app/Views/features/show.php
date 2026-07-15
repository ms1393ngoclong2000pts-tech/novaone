<?php
/** @var array<string, mixed> $hub */
/** @var string $key */
?>
<section class="feature-hub-panel">
    <header class="feature-hub-hero">
        <div>
            <a class="back-button" href="?route=home" aria-label="Quay lại"><?= ui_icon('arrow') ?></a>
            <span class="feature-hub-icon"><?= ui_icon((string) ($hub['icon'] ?? 'file')) ?></span>
            <h2><?= e($hub['title'] ?? '') ?></h2>
            <p><?= e($hub['subtitle'] ?? '') ?></p>
        </div>
        <a class="employee-action blue" href="?route=dashboard">Dashboard</a>
    </header>

    <div class="feature-hub-stats">
        <?php foreach (($hub['stats'] ?? []) as $stat): ?>
            <article>
                <span><?= e($stat['label'] ?? '') ?></span>
                <strong><?= e($stat['value'] ?? 0) ?></strong>
            </article>
        <?php endforeach; ?>
    </div>

    <?php foreach (($hub['groups'] ?? []) as $group => $items): ?>
        <section class="feature-section">
            <h3><?= e($group) ?></h3>
            <div class="feature-card-grid">
                <?php foreach ($items as $item): ?>
                    <?php
                    $href = (string) ($item['href'] ?? '?route=home');
                    $route = href_route($href);
                    $isAllowed = $route === 'features' || $route === '' || can_access_route($route);
                    ?>
                    <a class="feature-card <?= ! $isAllowed ? 'disabled' : '' ?>" href="<?= e($isAllowed ? $href : '?route=home') ?>">
                        <span class="feature-card-icon"><?= ui_icon((string) ($item['icon'] ?? 'file')) ?></span>
                        <span>
                            <strong><?= e($item['label'] ?? '') ?></strong>
                            <small><?= e($item['description'] ?? '') ?></small>
                        </span>
                        <em><?= ! empty($item['implemented']) ? 'Đã có' : 'Mở rộng' ?></em>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</section>
