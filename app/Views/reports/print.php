<?php
/** @var array<int, array<string, mixed>> $summary */
/** @var array<int, array<string, mixed>> $miniReports */
/** @var array<string, string> $filters */
?>
<style>
body { background: #f8fafc; color: #111827; font-family: Arial, sans-serif; }
.print-report { max-width: 980px; margin: 24px auto; background: #fff; padding: 32px; border: 1px solid #e5e7eb; }
.print-report header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #0ea5e9; padding-bottom: 16px; }
.print-report h1 { margin: 0 0 8px; font-size: 28px; color: #0f172a; }
.print-report h2 { margin: 26px 0 12px; font-size: 18px; color: #334155; }
.print-report p { color: #64748b; }
.print-actions { display: flex; gap: 8px; align-items: flex-start; }
.print-actions button, .print-actions a { border: 0; border-radius: 6px; padding: 10px 14px; background: #0ea5e9; color: #fff; text-decoration: none; font-weight: 700; cursor: pointer; }
.summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 18px; }
.summary-grid article { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; }
.summary-grid span, table small { color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; }
.summary-grid strong { display: block; margin: 8px 0; font-size: 22px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; vertical-align: top; }
th { background: #f1f5f9; color: #334155; }
@media print {
  body { background: #fff; }
  .print-report { margin: 0; max-width: none; border: 0; padding: 0; }
  .print-actions { display: none; }
}
</style>
<main class="print-report">
    <header>
        <div>
            <h1>Báo cáo tổng hợp Novaone</h1>
            <p>Khoảng lọc: <?= e($filters['from'] ?: 'Tất cả') ?> - <?= e($filters['to'] ?: 'Tất cả') ?> · Nhóm: <?= e($filters['module'] ?: 'all') ?></p>
        </div>
        <div class="print-actions">
            <button type="button" onclick="window.print()">In / Lưu PDF</button>
            <a href="?route=reports">Quay lại</a>
        </div>
    </header>

    <section class="summary-grid">
        <?php foreach ($summary as $item): ?>
            <article>
                <span><?= e($item['label'] ?? '') ?></span>
                <strong><?= e($item['value'] ?? '') ?></strong>
                <small><?= e($item['note'] ?? '') ?></small>
            </article>
        <?php endforeach; ?>
    </section>

    <h2>Báo cáo theo phân hệ</h2>
    <table>
        <thead><tr><th>Phân hệ</th><th>Chỉ số</th><th>Giá trị</th></tr></thead>
        <tbody>
            <?php foreach ($miniReports as $section): ?>
                <?php foreach (($section['items'] ?? []) as $item): ?>
                    <tr>
                        <td><strong><?= e($section['title'] ?? '') ?></strong><br><small><?= e($section['subtitle'] ?? '') ?></small></td>
                        <td><?= e($item['label'] ?? '') ?></td>
                        <td><?= e((string) ($item['value'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
<script>setTimeout(() => window.print(), 400);</script>
