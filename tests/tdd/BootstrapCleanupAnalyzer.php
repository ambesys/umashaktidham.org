<?php
/**
 * Bootstrap Cleanup Analysis Tool
 *
 * Analyzes the codebase for CSS/JS cleanup opportunities by identifying
 * custom styles that can be replaced with Bootstrap classes.
 */

class BootstrapCleanupAnalyzer
{
    private $projectRoot;
    private $results = [];

    public function __construct($projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * Run comprehensive Bootstrap cleanup analysis
     */
    public function analyze()
    {
        echo "🔍 Bootstrap Cleanup Analysis\n";
        echo "=============================\n\n";

        $this->analyzeCSSFiles();
        $this->analyzePHPViews();
        $this->analyzeJavaScriptFiles();

        $this->generateReport();
    }

    /**
     * Analyze CSS files for Bootstrap replacement opportunities
     */
    private function analyzeCSSFiles()
    {
        echo "📄 Analyzing CSS files...\n";

        $cssFiles = $this->findFiles('*.css');
        $bootstrapReplacements = $this->getBootstrapReplacements();

        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->projectRoot . '/', '', $file);

            foreach ($bootstrapReplacements as $custom => $bootstrap) {
                if (strpos($content, $custom) !== false) {
                    $this->results['css_replacements'][] = [
                        'file' => $relativePath,
                        'custom_property' => $custom,
                        'bootstrap_class' => $bootstrap,
                        'occurrences' => substr_count($content, $custom)
                    ];
                }
            }

            // Check for large CSS blocks that could be componentized
            if (strlen($content) > 5000) {
                $this->results['large_css_files'][] = [
                    'file' => $relativePath,
                    'size' => strlen($content),
                    'lines' => substr_count($content, "\n")
                ];
            }
        }
    }

    /**
     * Analyze PHP view files for inline styles and custom classes
     */
    private function analyzePHPViews()
    {
        echo "🐘 Analyzing PHP view files...\n";

        $phpFiles = $this->findFiles('*.php');
        $viewFiles = array_filter($phpFiles, function($file) {
            return strpos($file, '/Views/') !== false;
        });

        foreach ($viewFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->projectRoot . '/', '', $file);

            // Check for inline styles
            if (preg_match_all('/style\s*=\s*["\']([^"\']*)["\']/', $content, $matches)) {
                foreach ($matches[1] as $style) {
                    $this->results['inline_styles'][] = [
                        'file' => $relativePath,
                        'style' => $style,
                        'line' => $this->getLineNumber($content, $style)
                    ];
                }
            }

            // Check for custom CSS classes that could be Bootstrap
            $customClasses = $this->identifyCustomClasses($content);
            foreach ($customClasses as $class) {
                $bootstrapEquivalent = $this->findBootstrapEquivalent($class);
                if ($bootstrapEquivalent) {
                    $this->results['custom_classes'][] = [
                        'file' => $relativePath,
                        'custom_class' => $class,
                        'bootstrap_equivalent' => $bootstrapEquivalent
                    ];
                }
            }

            // Check for excessive CSS in <style> tags
            if (preg_match('/<style[^>]*>(.*?)<\/style>/s', $content, $match)) {
                $cssLength = strlen($match[1]);
                if ($cssLength > 1000) {
                    $this->results['embedded_styles'][] = [
                        'file' => $relativePath,
                        'css_length' => $cssLength,
                        'lines' => substr_count($match[1], "\n")
                    ];
                }
            }
        }
    }

    /**
     * Analyze JavaScript files for Bootstrap opportunities
     */
    private function analyzeJavaScriptFiles()
    {
        echo "📜 Analyzing JavaScript files...\n";

        $jsFiles = $this->findFiles('*.js');

        foreach ($jsFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->projectRoot . '/', '', $file);

            // Check for jQuery DOM manipulation that could use Bootstrap JS
            $jqueryPatterns = [
                '/\.show\(\)/' => 'Bootstrap collapse/show methods',
                '/\.hide\(\)/' => 'Bootstrap collapse/hide methods',
                '/\.toggle\(\)/' => 'Bootstrap collapse/toggle methods',
                '/\.modal\(.*\)/' => 'Bootstrap Modal',
                '/\.dropdown\(.*\)/' => 'Bootstrap Dropdown',
                '/\.tooltip\(.*\)/' => 'Bootstrap Tooltip',
                '/\.popover\(.*\)/' => 'Bootstrap Popover'
            ];

            foreach ($jqueryPatterns as $pattern => $bootstrapEquivalent) {
                if (preg_match($pattern, $content)) {
                    $this->results['jquery_bootstrap'][] = [
                        'file' => $relativePath,
                        'pattern' => $pattern,
                        'bootstrap_equivalent' => $bootstrapEquivalent
                    ];
                }
            }
        }
    }

    /**
     * Get Bootstrap replacement mappings
     */
    private function getBootstrapReplacements()
    {
        return [
            // Layout
            'display: flex' => 'd-flex',
            'justify-content: center' => 'justify-content-center',
            'justify-content: space-between' => 'justify-content-between',
            'align-items: center' => 'align-items-center',
            'flex-direction: column' => 'flex-column',

            // Spacing
            'margin: 0' => 'm-0',
            'margin: 1rem' => 'm-3',
            'margin-bottom: 1rem' => 'mb-3',
            'margin-top: 1rem' => 'mt-3',
            'padding: 1rem' => 'p-3',

            // Colors
            'color: white' => 'text-white',
            'background-color: #007bff' => 'bg-primary',
            'background-color: #28a745' => 'bg-success',
            'background-color: #dc3545' => 'bg-danger',
            'background-color: #ffc107' => 'bg-warning',
            'background-color: #17a2b8' => 'bg-info',

            // Borders
            'border: 1px solid' => 'border',
            'border-radius: 0.25rem' => 'rounded',
            'border-radius: 0.5rem' => 'rounded-3',

            // Shadows
            'box-shadow: 0 0.125rem 0.25rem' => 'shadow-sm',
            'box-shadow: 0 0.5rem 1rem' => 'shadow',

            // Typography
            'font-weight: bold' => 'fw-bold',
            'font-weight: 600' => 'fw-semibold',
            'text-align: center' => 'text-center',
            'text-transform: uppercase' => 'text-uppercase',

            // Display
            'display: block' => 'd-block',
            'display: none' => 'd-none',
            'display: inline-block' => 'd-inline-block',

            // Positioning
            'position: relative' => 'position-relative',
            'position: absolute' => 'position-absolute',
        ];
    }

    /**
     * Identify custom CSS classes in HTML
     */
    private function identifyCustomClasses($content)
    {
        $classes = [];

        // Extract class attributes
        if (preg_match_all('/class\s*=\s*["\']([^"\']*)["\']/', $content, $matches)) {
            foreach ($matches[1] as $classList) {
                $classArray = preg_split('/\s+/', $classList);
                foreach ($classArray as $class) {
                    // Skip Bootstrap classes and common HTML classes
                    if (!$this->isBootstrapClass($class) && !$this->isCommonHtmlClass($class)) {
                        $classes[] = $class;
                    }
                }
            }
        }

        return array_unique($classes);
    }

    /**
     * Check if a class is a Bootstrap class
     */
    private function isBootstrapClass($class)
    {
        $bootstrapPrefixes = [
            'container', 'row', 'col', 'd-', 'p-', 'm-', 'bg-', 'text-',
            'btn', 'card', 'nav', 'navbar', 'dropdown', 'modal', 'alert',
            'badge', 'breadcrumb', 'carousel', 'collapse', 'list-group',
            'pagination', 'progress', 'spinner', 'toast', 'tooltip'
        ];

        foreach ($bootstrapPrefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a class is a common HTML class
     */
    private function isCommonHtmlClass($class)
    {
        $commonClasses = [
            'active', 'disabled', 'hidden', 'visible', 'show', 'hide',
            'open', 'close', 'selected', 'checked', 'required', 'optional',
            'error', 'success', 'warning', 'info', 'primary', 'secondary'
        ];

        return in_array($class, $commonClasses);
    }

    /**
     * Find Bootstrap equivalent for a custom class
     */
    private function findBootstrapEquivalent($customClass)
    {
        $equivalents = [
            'user-dashboard' => 'container py-4',
            'dashboard-header' => 'bg-primary text-white py-5',
            'family-card' => 'card border-0 shadow-sm',
            'family-card-header' => 'card-header bg-primary text-white',
            'family-table' => 'table table-hover mb-0',
            'btn-add-member' => 'btn btn-success',
            'btn-edit' => 'btn btn-primary btn-sm',
            'btn-delete' => 'btn btn-danger btn-sm',
            'sidebar-cards' => 'mb-4',
            'event-card' => 'card border-0 shadow-sm',
            'ticket-card' => 'card border-0 shadow-sm',
            'add-member-form' => 'p-3 bg-light border rounded mt-3',
            'form-grid' => 'row g-3',
            'form-group' => 'col-md-6',
            'alert-success' => 'alert alert-success',
            'alert-danger' => 'alert alert-danger'
        ];

        return $equivalents[$customClass] ?? null;
    }

    /**
     * Find files matching a pattern
     */
    private function findFiles($pattern)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                // Skip vendor, node_modules, and other third-party directories
                $path = $file->getPathname();
                if (!preg_match('/(vendor|node_modules|\.git)/', $path)) {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    /**
     * Get line number for a string in content
     */
    private function getLineNumber($content, $search)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, $search) !== false) {
                return $lineNum + 1;
            }
        }
        return 0;
    }

    /**
     * Generate comprehensive analysis report
     */
    private function generateReport()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 BOOTSTRAP CLEANUP ANALYSIS REPORT\n";
        echo str_repeat("=", 60) . "\n\n";

        // CSS Replacements
        if (!empty($this->results['css_replacements'])) {
            echo "🎨 CSS PROPERTIES TO REPLACE WITH BOOTSTRAP:\n";
            echo "-------------------------------------------\n";
            foreach ($this->results['css_replacements'] as $replacement) {
                echo "📁 {$replacement['file']}\n";
                echo "  Custom: {$replacement['custom_property']}\n";
                echo "  Bootstrap: {$replacement['bootstrap_class']}\n";
                echo "  Occurrences: {$replacement['occurrences']}\n\n";
            }
        }

        // Large CSS Files
        if (!empty($this->results['large_css_files'])) {
            echo "📈 LARGE CSS FILES TO REVIEW:\n";
            echo "------------------------------\n";
            foreach ($this->results['large_css_files'] as $file) {
                echo "📁 {$file['file']} - {$file['size']} bytes ({$file['lines']} lines)\n";
            }
            echo "\n";
        }

        // Inline Styles
        if (!empty($this->results['inline_styles'])) {
            echo "🏷️ INLINE STYLES TO CONVERT:\n";
            echo "----------------------------\n";
            $inlineCount = count($this->results['inline_styles']);
            echo "Found $inlineCount inline style attributes\n\n";
            // Show first 10 as examples
            $examples = array_slice($this->results['inline_styles'], 0, 10);
            foreach ($examples as $style) {
                echo "📁 {$style['file']}:{$style['line']}\n";
                echo "  Style: {$style['style']}\n\n";
            }
            if ($inlineCount > 10) {
                echo "... and " . ($inlineCount - 10) . " more\n\n";
            }
        }

        // Custom Classes
        if (!empty($this->results['custom_classes'])) {
            echo "🏷️ CUSTOM CLASSES WITH BOOTSTRAP EQUIVALENTS:\n";
            echo "---------------------------------------------\n";
            foreach ($this->results['custom_classes'] as $class) {
                echo "📁 {$class['file']}\n";
                echo "  Custom: {$class['custom_class']}\n";
                echo "  Bootstrap: {$class['bootstrap_equivalent']}\n\n";
            }
        }

        // Embedded Styles
        if (!empty($this->results['embedded_styles'])) {
            echo "📄 EMBEDDED <STYLE> BLOCKS TO EXTERNALIZE:\n";
            echo "-----------------------------------------\n";
            foreach ($this->results['embedded_styles'] as $style) {
                echo "📁 {$style['file']} - {$style['css_length']} characters\n";
            }
            echo "\n";
        }

        // jQuery to Bootstrap JS
        if (!empty($this->results['jquery_bootstrap'])) {
            echo "⚡ JQUERY CODE TO REPLACE WITH BOOTSTRAP JS:\n";
            echo "--------------------------------------------\n";
            foreach ($this->results['jquery_bootstrap'] as $jquery) {
                echo "📁 {$jquery['file']}\n";
                echo "  Pattern: {$jquery['pattern']}\n";
                echo "  Bootstrap: {$jquery['bootstrap_equivalent']}\n\n";
            }
        }

        // Summary and Recommendations
        echo "💡 RECOMMENDATIONS:\n";
        echo "-------------------\n";

        $totalIssues = count($this->results['css_replacements'] ?? []) +
                      count($this->results['inline_styles'] ?? []) +
                      count($this->results['custom_classes'] ?? []) +
                      count($this->results['embedded_styles'] ?? []);

        if ($totalIssues > 0) {
            echo "• Found $totalIssues opportunities for Bootstrap optimization\n";
            echo "• Focus on converting custom CSS properties to Bootstrap utility classes\n";
            echo "• Move embedded <style> blocks to external CSS files\n";
            echo "• Replace inline styles with Bootstrap classes\n";
            echo "• Consider using Bootstrap's JavaScript components instead of custom jQuery\n";
        } else {
            echo "• Codebase is well-optimized for Bootstrap! 🎉\n";
        }

        echo "\n✅ Bootstrap cleanup analysis completed!\n";
    }

    /**
     * Get analysis results
     */
    public function getResults()
    {
        return $this->results;
    }
}

// Run the analysis
$projectRoot = dirname(__DIR__); // Go up one level from tests/
$analyzer = new BootstrapCleanupAnalyzer($projectRoot);
$analyzer->analyze();
?>