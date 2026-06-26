<?php

declare(strict_types=1);

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require self::path($view);
        $content = ob_get_clean();

        require self::path($layout);
    }

    private static function path(string $view): string
    {
        if (str_starts_with($view, '@')) {
            [$module, $moduleView] = array_pad(explode('/', substr($view, 1), 2), 2, '');
            if (! preg_match('/^[A-Za-z]+$/', $module) || $moduleView === '' || str_contains($moduleView, '..')) {
                throw new InvalidArgumentException('Invalid module view: ' . $view);
            }

            return BASE_PATH . '/app/Modules/' . $module . '/Views/' . $moduleView . '.php';
        }

        if (str_contains($view, '..')) {
            throw new InvalidArgumentException('Invalid view: ' . $view);
        }

        return BASE_PATH . '/app/Views/' . $view . '.php';
    }
}
