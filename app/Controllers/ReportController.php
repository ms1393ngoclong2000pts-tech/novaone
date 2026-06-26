<?php

declare(strict_types=1);

final class ReportController
{
    public function index(DataStore $store): void
    {
        require_auth();

        View::render('reports/index', [
            'active' => 'reports',
            'title' => 'Báo cáo',
            'data' => $store->all(),
        ]);
    }
}
