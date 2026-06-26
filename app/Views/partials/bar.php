<?php $safe = max(0, min(100, (int) round($value))); ?>
<div class="bar-row">
    <span><?= e($label) ?></span>
    <div class="bar"><i style="width: <?= $safe ?>%"></i></div>
    <strong><?= $safe ?>%</strong>
</div>
