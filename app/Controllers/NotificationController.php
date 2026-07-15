<?php

declare(strict_types=1);

final class NotificationController
{
    public function read(DataStore $store): void
    {
        require_auth();

        $id = (string) ($_GET['id'] ?? '');
        $data = $store->all();
        $redirect = '?route=dashboard';
        $data['_notifications'] = $data['_notifications'] ?? [];

        foreach ($data['_notifications'] as &$notification) {
            if (($notification['id'] ?? '') !== $id) {
                continue;
            }

            if (empty($notification['read_at'])) {
                $notification['read_at'] = date('Y-m-d H:i:s');
            }
            $redirect = safe_internal_href((string) ($notification['href'] ?? ''), $redirect);
            break;
        }
        unset($notification);

        $store->save($data);
        header('Location: ' . $redirect, true, 303);
        exit;
    }

    public function readAll(DataStore $store): void
    {
        require_auth();

        $data = $store->all();
        $data['_notifications'] = $data['_notifications'] ?? [];

        foreach ($data['_notifications'] as &$notification) {
            if (empty($notification['read_at'])) {
                $notification['read_at'] = date('Y-m-d H:i:s');
            }
        }
        unset($notification);

        $store->save($data);
        redirect('dashboard');
    }

    public function feed(DataStore $store): never
    {
        require_auth();

        $data = $store->all();
        $items = array_slice((array) ($data['_notifications'] ?? []), 0, 20);
        $unread = count(array_filter($items, fn (array $item): bool => empty($item['read_at'])));

        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'unread' => $unread,
            'total' => count((array) ($data['_notifications'] ?? [])),
            'items' => array_map(fn (array $item): array => [
                'id' => $item['id'] ?? '',
                'title' => $item['title'] ?? '',
                'message' => $item['message'] ?? '',
                'type' => $item['type'] ?? 'info',
                'read_at' => $item['read_at'] ?? null,
                'created_at' => $item['created_at'] ?? '',
                'href' => safe_internal_href((string) ($item['href'] ?? ''), '?route=dashboard'),
            ], $items),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
