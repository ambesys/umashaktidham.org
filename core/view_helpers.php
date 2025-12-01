<?php
// Global helper to render a view inside the main layout.
// This is intentionally in the global namespace to make it easy to call from routes.
function render_view(string $viewPath, array $data = [], array $options = [])
{
    $projectRoot = dirname(__DIR__);
    $fullView = $projectRoot . '/' . ltrim($viewPath, '/');

    if (!file_exists($fullView)) {
        http_response_code(500);
        echo "View not found: $fullView";
        return;
    }

    if (!empty($data)) {
        extract($data, EXTR_SKIP);
    }

    ob_start();
    include $fullView;
    $content = ob_get_clean();

    $layout = $options['layout'] ?? 'src/Views/layouts/main.php';
    $fullLayout = $projectRoot . '/' . ltrim($layout, '/');
    $pageScripts = $options['pageScripts'] ?? [];

    if (file_exists($fullLayout)) {
        include $fullLayout;
        return;
    }

    echo $content;
}
