<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Template renderer with layout support.
 */
class View
{
    /**
     * Render a view, optionally wrapped in a layout.
     *
     * @param string $template view name relative to app/Views (dot or slash separated)
     * @param array  $data     variables exposed to the view
     * @param string $layout   layout name in app/Views/layouts, or 'plain' to skip
     */
    public function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $file = APP_PATH . '/Views/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($file)) {
            App::getInstance()->response->abort(500, "View not found: $template");
        }

        extract($data, EXTR_SKIP);
        unset($data);

        ob_start();
        include $file;
        $content = ob_get_clean();

        if ($layout === 'plain') {
            echo $content;
            return;
        }

        $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        $title = $title ?? App::config('app.name');
        $active = $active ?? '';
        $scripts = $scripts ?? [];
        $styles = $styles ?? [];
        $bodyClass = $bodyClass ?? '';

        include $layoutFile;
    }
}
