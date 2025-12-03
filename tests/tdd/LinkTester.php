<?php
/**
 * Comprehensive Link Testing Suite for Uma Shakti Dham Website
 *
 * This script tests all navigation links from header, footer, and internal pages
 * to ensure no broken links exist and all routes are properly configured.
 */

class LinkTester
{
    private $baseUrl;
    private $testResults = [];
    private $visitedUrls = [];
    private $maxDepth = 3; // Maximum crawl depth to prevent infinite loops

    public function __construct($baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Run comprehensive link testing
     */
    public function runTests()
    {
        echo "🧪 Starting Comprehensive Link Testing Suite\n";
        echo "==========================================\n\n";

        // Test all main navigation links
        $this->testMainNavigation();

        // Test footer links
        $this->testFooterLinks();

        // Test dropdown menu links
        $this->testDropdownMenus();

        // Test admin panel links (if accessible)
        $this->testAdminLinks();

        // Test user dashboard links (if accessible)
        $this->testUserDashboardLinks();

        // Test auth-related links
        $this->testAuthLinks();

        // Test API endpoints
        $this->testApiEndpoints();

        // Generate report
        $this->generateReport();
    }

    /**
     * Test main navigation links from header
     */
    private function testMainNavigation()
    {
        echo "📋 Testing Main Navigation Links...\n";

        $mainNavLinks = [
            '/' => 'Home',
            '/about' => 'About',
            '/events' => 'Events & Programs',
            '/gallery' => 'Photo Gallery',
            '/membership' => 'Community',
            '/indian-holidays' => 'Religion',
            '/contact' => 'Contact',
            '/donate' => 'Donate'
        ];

        foreach ($mainNavLinks as $url => $name) {
            $this->testUrl($url, "Main Nav: $name");
        }
    }

    /**
     * Test footer links
     */
    private function testFooterLinks()
    {
        echo "📄 Testing Footer Links...\n";

        $footerLinks = [
            '/' => 'Home',
            '/about' => 'Community History',
            '/events' => 'Events & Programs',
            '/gallery' => 'Photo Gallery',
            '/membership' => 'Membership',
            '/donate' => 'Support Us',
            '/contact' => 'Contact Us',
            '/direction' => 'Location & Hours',
            '/facilities' => 'Facilities & Rental'
        ];

        foreach ($footerLinks as $url => $name) {
            $this->testUrl($url, "Footer: $name");
        }
    }

    /**
     * Test dropdown menu links
     */
    private function testDropdownMenus()
    {
        echo "📂 Testing Dropdown Menu Links...\n";

        $dropdownLinks = [
            // About dropdown
            '/about' => 'Community History',
            '/kp-history' => 'Kadva Patidar Heritage',
            '/committee' => 'Leadership Committee',

            // Events dropdown
            '/events' => 'Upcoming Events',
            '/events/cultural' => 'Cultural Programs',
            '/events/festivals' => 'Festival Celebrations',

            // Gallery dropdown
            '/gallery' => 'Latest Photos',
            '/gallery/events' => 'Event Photos',
            '/gallery/community' => 'Community Photos',

            // Community dropdown
            '/membership' => 'Membership',
            '/members/families' => 'Family Directory',
            '/youth-corner' => 'Youth Corner',
            '/matrimonial' => 'Business Network',
            '/business-directory' => 'Business Directory',

            // Religion dropdown
            '/indian-holidays' => 'Hindu Festivals (Religion)',
            '/hindu-gods' => 'Hindu Gods',
            '/hindu-rituals' => 'Hindu Rituals',
            '/hindu-scriptures' => 'Hindu Scriptures',

            // Contact dropdown
            '/contact' => 'Contact Us',
            '/direction' => 'Location & Hours',
            '/facilities' => 'Facilities & Rental'
        ];

        foreach ($dropdownLinks as $url => $name) {
            $this->testUrl($url, "Dropdown: $name");
        }
    }

    /**
     * Test admin panel links
     */
    private function testAdminLinks()
    {
        echo "👑 Testing Admin Panel Links...\n";

        $adminLinks = [
            '/admin' => 'Admin Dashboard',
            '/admin/dashboard' => 'Admin Dashboard (alt)',
            '/admin/users' => 'Manage Users',
            '/admin/moderators' => 'Manage Moderators',
            '/admin/events' => 'Manage Events'
        ];

        foreach ($adminLinks as $url => $name) {
            $this->testUrl($url, "Admin: $name", true); // Admin routes may require auth
        }
    }

    /**
     * Test user dashboard links
     */
    private function testUserDashboardLinks()
    {
        echo "👤 Testing User Dashboard Links...\n";

        $userLinks = [
            '/user/dashboard' => 'User Dashboard',
            '/user/profile' => 'User Profile',
            '/user/family' => 'Family Management',
            '/dashboard' => 'Dashboard (legacy)',
            '/profile' => 'Profile (legacy)',
            '/family' => 'Family (legacy)'
        ];

        foreach ($userLinks as $url => $name) {
            $this->testUrl($url, "User: $name", true); // User routes may require auth
        }
    }

    /**
     * Test authentication-related links
     */
    private function testAuthLinks()
    {
        echo "🔐 Testing Authentication Links...\n";

        $authLinks = [
            '/login' => 'Login Page',
            '/register' => 'Registration Page',
            '/logout' => 'Logout',
            '/forgot-password' => 'Forgot Password',
            '/reset-password' => 'Reset Password'
        ];

        foreach ($authLinks as $url => $name) {
            $this->testUrl($url, "Auth: $name");
        }
    }

    /**
     * Test API endpoints
     */
    private function testApiEndpoints()
    {
        echo "🔌 Testing API Endpoints...\n";

        $apiLinks = [
            '/api/events' => 'Events API',
            '/api/events/my-registrations' => 'My Event Registrations API'
        ];

        foreach ($apiLinks as $url => $name) {
            $this->testUrl($url, "API: $name", true, 'GET'); // API endpoints may require auth
        }
    }

    /**
     * Test a single URL
     */
    private function testUrl($url, $description, $requiresAuth = false, $method = 'GET')
    {
        $fullUrl = $this->baseUrl . $url;

        // Skip if already tested
        if (in_array($fullUrl, $this->visitedUrls)) {
            return;
        }

        $this->visitedUrls[] = $fullUrl;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'LinkTester/1.0');

        // Don't follow redirects for auth-required pages
        if ($requiresAuth) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);

        curl_close($ch);

        $status = 'PASS';
        $notes = '';

        // Check for various success conditions
        if ($error) {
            $status = 'ERROR';
            $notes = "cURL Error: $error";
        } elseif ($httpCode >= 400) {
            if ($requiresAuth && ($httpCode == 302 || $httpCode == 401 || $httpCode == 403)) {
                $status = 'AUTH_REQUIRED';
                $notes = "Authentication required (expected for protected routes)";
            } else {
                $status = 'FAIL';
                $notes = "HTTP $httpCode";
            }
        } elseif ($httpCode >= 300 && $httpCode < 400) {
            $status = 'REDIRECT';
            $notes = "HTTP $httpCode (redirect)";
        } elseif (strpos($contentType, 'text/html') === false && strpos($contentType, 'application/json') === false) {
            // For non-HTML/JSON responses, check if it's a valid asset
            if ($httpCode == 200) {
                $status = 'ASSET_OK';
                $notes = "Asset served correctly";
            }
        }

        // Check for common HTML issues
        if ($response && strpos($contentType, 'text/html') !== false) {
            if (strpos($response, '<title>') === false) {
                $notes .= ($notes ? '; ' : '') . 'Missing title tag';
            }
            if (strpos($response, '<!DOCTYPE') === false) {
                $notes .= ($notes ? '; ' : '') . 'Missing DOCTYPE';
            }
        }

        $this->testResults[] = [
            'url' => $url,
            'description' => $description,
            'status' => $status,
            'http_code' => $httpCode,
            'notes' => $notes,
            'requires_auth' => $requiresAuth
        ];

        // Visual output
        $symbol = match($status) {
            'PASS' => '✅',
            'AUTH_REQUIRED' => '🔒',
            'REDIRECT' => '↪️',
            'ASSET_OK' => '📁',
            'ERROR' => '❌',
            'FAIL' => '❌',
            default => '❓'
        };

        echo "  $symbol $description ($url) - $status" . ($notes ? " [$notes]" : "") . "\n";
    }

    /**
     * Generate comprehensive test report
     */
    private function generateReport()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 COMPREHENSIVE LINK TEST REPORT\n";
        echo str_repeat("=", 60) . "\n\n";

        // Statistics
        $stats = array_count_values(array_column($this->testResults, 'status'));
        $total = count($this->testResults);

        echo "📈 SUMMARY STATISTICS:\n";
        echo "Total URLs tested: $total\n";
        foreach ($stats as $status => $count) {
            $percentage = round(($count / $total) * 100, 1);
            echo "  $status: $count ($percentage%)\n";
        }

        // Detailed results by category
        echo "\n📋 DETAILED RESULTS:\n\n";

        $categories = [
            'PASS' => '✅ Working Links',
            'AUTH_REQUIRED' => '🔒 Authentication Required (Expected)',
            'REDIRECT' => '↪️ Redirects',
            'ASSET_OK' => '📁 Assets',
            'FAIL' => '❌ Broken Links',
            'ERROR' => '❌ Connection Errors'
        ];

        foreach ($categories as $status => $title) {
            $filtered = array_filter($this->testResults, fn($r) => $r['status'] === $status);
            if (empty($filtered)) continue;

            echo "$title:\n";
            foreach ($filtered as $result) {
                echo "  • {$result['description']} ({$result['url']})";
                if ($result['notes']) echo " - {$result['notes']}";
                echo "\n";
            }
            echo "\n";
        }

        // Recommendations
        echo "💡 RECOMMENDATIONS:\n";
        $brokenLinks = array_filter($this->testResults, fn($r) => in_array($r['status'], ['FAIL', 'ERROR']));

        if (!empty($brokenLinks)) {
            echo "• Fix the following broken links:\n";
            foreach ($brokenLinks as $link) {
                echo "  - {$link['description']} ({$link['url']})\n";
            }
        } else {
            echo "• All links are working correctly! 🎉\n";
        }

        $authLinks = array_filter($this->testResults, fn($r) => $r['status'] === 'AUTH_REQUIRED');
        if (!empty($authLinks)) {
            echo "• Protected routes correctly require authentication\n";
        }

        echo "\n✅ Link testing completed!\n";
    }

    /**
     * Get test results as array
     */
    public function getResults()
    {
        return $this->testResults;
    }
}

// Run the tests
if ($argc > 1) {
    $baseUrl = $argv[1];
} else {
    $baseUrl = 'http://localhost:8000';
}

echo "Testing against: $baseUrl\n\n";

$tester = new LinkTester($baseUrl);
$tester->runTests();
?>