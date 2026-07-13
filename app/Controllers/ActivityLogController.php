<?php

declare(strict_types=1);

final class ActivityLogController
{
    public function index(DataStore $store): void
    {
        require_auth();
        require_permission('activity_log', 'view');

        $filters = $this->filters();
        $items = $this->filterItems(activity_log_data(), $filters);

        View::render('activity/index', [
            'active' => 'activity_log',
            'title' => 'Lịch sử thao tác',
            'items' => array_slice($items, 0, 120),
            'filters' => $filters,
            'modules' => $this->modules(activity_log_data()),
            'actions' => ['create', 'update', 'delete', 'import', 'export', 'login', 'security', 'info', 'warning', 'danger', 'success'],
            'exportUrl' => '?route=activity-log.export&q=' . urlencode($filters['q']) . '&module=' . urlencode($filters['module']) . '&action=' . urlencode($filters['action']),
        ]);
    }

    public function export(): void
    {
        require_auth();
        require_permission('activity_log', 'view');

        $filters = $this->filters();
        $items = $this->filterItems(activity_log_data(), $filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="novaone-activity-log-' . date('Ymd-His') . '.csv"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Thời gian', 'Người dùng', 'Vai trò', 'Module', 'Hành động', 'Tiêu đề', 'Nội dung', 'Liên kết', 'IP']);
        foreach ($items as $item) {
            fputcsv($output, [
                $item['created_at'] ?? '',
                $item['user_name'] ?? '',
                $item['user_role'] ?? '',
                $item['module'] ?? '',
                $item['action'] ?? '',
                $item['title'] ?? '',
                $item['message'] ?? '',
                $item['href'] ?? '',
                $item['ip'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    private function filters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'module' => preg_replace('/[^a-zA-Z0-9_. -]/', '', (string) ($_GET['module'] ?? '')) ?: '',
            'action' => preg_replace('/[^a-zA-Z0-9_. -]/', '', (string) ($_GET['action'] ?? '')) ?: '',
        ];
    }

    private function filterItems(array $items, array $filters): array
    {
        $query = $this->normalize($filters['q']);

        return array_values(array_filter($items, function (array $item) use ($filters, $query): bool {
            if ($filters['module'] !== '' && (string) ($item['module'] ?? '') !== $filters['module']) {
                return false;
            }
            if ($filters['action'] !== '' && (string) ($item['action'] ?? '') !== $filters['action']) {
                return false;
            }
            if ($query === '') {
                return true;
            }

            $haystack = implode(' ', [
                $item['title'] ?? '',
                $item['message'] ?? '',
                $item['user_name'] ?? '',
                $item['module'] ?? '',
                $item['action'] ?? '',
            ]);

            return str_contains($this->normalize($haystack), $query);
        }));
    }

    private function modules(array $items): array
    {
        $modules = array_values(array_unique(array_filter(array_map(fn (array $item): string => (string) ($item['module'] ?? ''), $items))));
        sort($modules);
        return $modules;
    }

    private function normalize(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower(trim($value), 'UTF-8') : strtolower(trim($value));
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
