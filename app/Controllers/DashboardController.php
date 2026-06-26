<?php

declare(strict_types=1);

final class DashboardController
{
    public function index(DataStore $store): void
    {
        require_auth();

        $data = $store->all();
        $sales = $data['sales'] ?? [];
        $pos = $data['pos'] ?? [];
        $tasks = $data['tasks'] ?? [];
        $inventory = $data['inventory'] ?? [];
        $kpis = $data['kpi'] ?? [];
        $tickets = $data['tickets'] ?? [];

        $revenue = array_sum(array_map(fn ($item) => (float) ($item['amount'] ?? 0), [...$sales, ...$pos]));
        $taskDone = count(array_filter($tasks, fn ($item) => ($item['status'] ?? '') === 'completed'));
        $lowStock = count(array_filter($inventory, fn ($item) => (float) ($item['quantity'] ?? 0) <= (float) ($item['min'] ?? 0)));
        $openTickets = count(array_filter($tickets, fn ($item) => ! in_array($item['status'] ?? '', ['completed', 'canceled'], true)));

        View::render('dashboard/index', [
            'active' => 'dashboard',
            'title' => 'Dashboard',
            'data' => $data,
            'revenue' => $revenue,
            'taskDone' => $taskDone,
            'lowStock' => $lowStock,
            'openTickets' => $openTickets,
        ]);
    }
}
