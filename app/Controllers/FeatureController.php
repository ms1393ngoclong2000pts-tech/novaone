<?php

declare(strict_types=1);

final class FeatureController
{
    public function index(): void
    {
        require_auth();

        $title = trim((string) ($_GET['title'] ?? 'Tính năng'));
        $parent = trim((string) ($_GET['parent'] ?? ''));

        View::render('features/show', [
            'active' => $parent,
            'title' => $title,
            'parent' => $parent,
        ]);
    }
}
