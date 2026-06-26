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
}
