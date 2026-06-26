<?php

declare(strict_types=1);

final class TaskController
{
    public function index(DataStore $store): void
    {
        require_auth();

        View::render('@Work/tasks/index', [
            'active' => 'tasks',
            'title' => 'Công việc',
            'items' => $store->get('tasks'),
        ]);
    }
}
