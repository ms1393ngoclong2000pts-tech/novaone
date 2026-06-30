<?php
$customers = array_values(array_unique(array_filter(array_map(fn (array $item): string => (string) ($item['customer'] ?? ''), $contacts))));
$types = [
    '' => '--- Chọn Loại Khách Hàng ---',
    'customer' => 'Khách hàng',
    'supplier' => 'Nhà cung cấp',
    'warehouse' => 'Kho máy',
    'internal' => 'Nội bộ',
];
?>

<section class="call-page">
    <?php if (! empty($_SESSION['flash_success'])): ?><div class="flash success call-flash"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
    <?php if (! empty($_SESSION['flash_error'])): ?><div class="flash error call-flash"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>

    <form id="call-form" class="call-grid" method="post" action="?route=calls.save">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="customer" id="call-customer-hidden">
        <input type="hidden" name="contact" id="call-contact-hidden">
        <input type="hidden" name="type" id="call-type-hidden">

        <article class="call-card call-dialer">
            <label class="call-number-label" for="call-number">Nhập số điện thoại</label>
            <input id="call-number" name="phone" inputmode="tel" autocomplete="tel" aria-label="Số điện thoại">

            <div class="dialpad" aria-label="Bàn phím số">
                <?php foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'] as $key): ?>
                    <button type="button" data-dial="<?= e($key) ?>"><?= e($key) ?></button>
                <?php endforeach; ?>
                <button type="button" class="dial-secondary" id="call-clear" title="Xóa toàn bộ"><?= ui_icon('headset') ?></button>
                <button type="button" class="dial-call" id="call-now" title="Gọi điện"><?= ui_icon('phone') ?></button>
                <button type="button" class="dial-secondary" id="call-backspace" title="Xóa một ký tự"><?= ui_icon('backspace') ?></button>
            </div>
        </article>

        <article class="call-card call-content">
            <header><h2>Nội dung cuộc gọi</h2></header>
            <div class="call-body">
                <label>
                    <span>Tiêu đề</span>
                    <input name="title" id="call-title">
                </label>
                <label>
                    <span>Nội dung</span>
                    <textarea name="content" id="call-content" rows="13"></textarea>
                </label>
                <button class="call-save" type="submit">Gửi Nội Dung Cuộc Gọi</button>
            </div>
        </article>

        <aside class="call-card call-actions">
            <header><h2>Thao tác</h2></header>
            <div class="call-body">
                <select id="call-template">
                    <option value="">--- Chọn mẫu ---</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?= e($template['id']) ?>" data-title="<?= e($template['title']) ?>" data-content="<?= e($template['content']) ?>"><?= e($template['label']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="call-type">
                    <?php foreach ($types as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="call-customer">
                    <option value="">--- Chọn Khách Hàng ---</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= e($customer) ?>"><?= e($customer) ?></option>
                    <?php endforeach; ?>
                </select>

                <section class="contact-box">
                    <h3>Danh Sách Người Liên Hệ</h3>
                    <div id="contact-list">
                        <?php if (count($contacts) === 0): ?>
                            <p>Chưa có người liên hệ</p>
                        <?php endif; ?>
                        <?php foreach ($contacts as $contact): ?>
                            <button type="button" class="contact-row" data-phone="<?= e($contact['phone']) ?>" data-customer="<?= e($contact['customer']) ?>" data-name="<?= e($contact['name']) ?>" data-type="<?= e($contact['type']) ?>">
                                <strong><?= e($contact['name']) ?></strong>
                                <span><?= e($contact['customer']) ?> · <?= e($contact['phone']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="contact-box call-history">
                    <h3>Lịch Sử Cuộc Gọi</h3>
                    <?php if (count($logs) === 0): ?>
                        <p>Chưa có lịch sử cuộc gọi</p>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                        <button type="button" class="history-row" data-phone="<?= e($log['phone'] ?? '') ?>" data-title="<?= e($log['title'] ?? '') ?>" data-content="<?= e($log['content'] ?? '') ?>">
                            <strong><?= e($log['title'] ?? 'Cuộc gọi') ?></strong>
                            <span><?= e($log['phone'] ?? '') ?> · <?= e($log['created_at'] ?? '') ?></span>
                        </button>
                    <?php endforeach; ?>
                </section>
            </div>
        </aside>
    </form>
</section>

<script>
(() => {
  const numberInput = document.getElementById('call-number');
  const titleInput = document.getElementById('call-title');
  const contentInput = document.getElementById('call-content');
  const templateSelect = document.getElementById('call-template');
  const typeSelect = document.getElementById('call-type');
  const customerSelect = document.getElementById('call-customer');
  const contactRows = Array.from(document.querySelectorAll('.contact-row'));
  const customerHidden = document.getElementById('call-customer-hidden');
  const contactHidden = document.getElementById('call-contact-hidden');
  const typeHidden = document.getElementById('call-type-hidden');

  const syncHidden = () => {
    customerHidden.value = customerSelect.value || '';
    typeHidden.value = typeSelect.value || '';
  };

  document.querySelectorAll('[data-dial]').forEach((button) => {
    button.addEventListener('click', () => {
      numberInput.value += button.dataset.dial;
      numberInput.focus();
    });
  });

  document.getElementById('call-backspace')?.addEventListener('click', () => {
    numberInput.value = numberInput.value.slice(0, -1);
    numberInput.focus();
  });

  document.getElementById('call-clear')?.addEventListener('click', () => {
    numberInput.value = '';
    contactHidden.value = '';
    numberInput.focus();
  });

  document.getElementById('call-now')?.addEventListener('click', () => {
    const phone = numberInput.value.replace(/[^0-9+*#]/g, '');
    if (!phone) {
      numberInput.focus();
      return;
    }
    window.location.href = `tel:${phone}`;
  });

  templateSelect?.addEventListener('change', () => {
    const option = templateSelect.selectedOptions[0];
    if (!option || !option.value) return;
    titleInput.value = option.dataset.title || '';
    contentInput.value = option.dataset.content || '';
  });

  const filterContacts = () => {
    const customer = customerSelect.value;
    const type = typeSelect.value;
    let visible = 0;
    contactRows.forEach((row) => {
      const okCustomer = !customer || row.dataset.customer === customer;
      const okType = !type || row.dataset.type === type;
      const show = okCustomer && okType;
      row.hidden = !show;
      if (show) visible += 1;
    });
    syncHidden();
    document.getElementById('contact-list')?.classList.toggle('empty-filter', visible === 0);
  };

  customerSelect?.addEventListener('change', filterContacts);
  typeSelect?.addEventListener('change', filterContacts);

  contactRows.forEach((row) => {
    row.addEventListener('click', () => {
      numberInput.value = row.dataset.phone || '';
      customerSelect.value = row.dataset.customer || '';
      typeSelect.value = row.dataset.type || '';
      contactHidden.value = row.dataset.name || '';
      filterContacts();
    });
  });

  document.querySelectorAll('.history-row').forEach((row) => {
    row.addEventListener('click', () => {
      numberInput.value = row.dataset.phone || '';
      titleInput.value = row.dataset.title || '';
      contentInput.value = row.dataset.content || '';
    });
  });

  document.getElementById('call-form')?.addEventListener('submit', syncHidden);
})();
</script>
