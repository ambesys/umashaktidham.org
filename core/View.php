<?php
namespace App\Views;

/**
 * Minimal view renderer that captures a view's output into the main layout.
 * Usage: \App\Views\View::render('src/Views/about.php', ['title' => 'About']);
 */
class View
{
    /**
     * Render a view inside the main layout (by default).
     * @param string $viewPath Path relative to project root, e.g. 'src/Views/about.php'
     * @param array $data Variables to extract into the view
     * @param array $options ['layout' => 'src/Views/layouts/main.php']
     */
    public static function render(string $viewPath, array $data = [], array $options = [])
    {
        $projectRoot = dirname(__DIR__);
        $fullView = $projectRoot . '/' . ltrim($viewPath, '/');

        if (!file_exists($fullView)) {
            http_response_code(500);
            echo "View not found: $fullView";
            return;
        }

        // Make data available to view as variables
        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }

        // Capture view output
        ob_start();
        include $fullView;
        $content = ob_get_clean();

        // Layout selection
        $layout = $options['layout'] ?? 'src/Views/layouts/main.php';
        $fullLayout = $projectRoot . '/' . ltrim($layout, '/');

        // Provide content and optional page-specific scripts to layout
        $pageScripts = $options['pageScripts'] ?? [];

        if (file_exists($fullLayout)) {
            // The layout expects $content or $contentFile
            include $fullLayout;
            return;
        }

        // Fallback: echo raw content
        echo $content;
    }
}
