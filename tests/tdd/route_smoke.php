<?php
// Simple static smoke-test: parse routes.php for controller references and verify methods exist.
$routesFile = __DIR__ . '/../routes.php';
if (!file_exists($routesFile)) {
    echo "routes.php not found\n";
    exit(2);
}

$content = file_get_contents($routesFile);

// Match patterns like [$controllers['auth'], 'login'] or [\App\Controllers\ClassName::class, 'method']
$pattern1 = "/\[\s*\$controllers\s*\[\s*'(?P<key>[a-zA-Z0-9_\-]+)'\s*\]\s*,\s*'(?P<method>[a-zA-Z0-9_]+)'\s*\]/";
$pattern2 = "/\[\s*\\?(?P<class>App\\\\Controllers\\\\[a-zA-Z0-9_]+)::class\s*,\s*'(?P<method2>[a-zA-Z0-9_]+)'\s*\]/";

$found = [];
if (preg_match_all($pattern1, $content, $m1, PREG_SET_ORDER)) {
    foreach ($m1 as $m) {
        $found[] = ['type' => 'controller_key', 'key' => $m['key'], 'method' => $m['method']];
    }
}

if (preg_match_all($pattern2, $content, $m2, PREG_SET_ORDER)) {
    foreach ($m2 as $m) {
        $found[] = ['type' => 'class', 'class' => $m['class'], 'method' => $m['method2']];
    }
}

echo "Found " . count($found) . " handler references in routes.php\n";

$errors = 0;
foreach ($found as $ref) {
    if ($ref['type'] === 'controller_key') {
        $key = $ref['key'];
        $method = $ref['method'];
        $class = '\\App\\Controllers\\' . ucfirst($key) . 'Controller';
        if (!class_exists($class)) {
            echo "Missing class for key '$key' -> expected $class\n";
            $errors++;
            continue;
        }
        if (!method_exists($class, $method)) {
            echo "Missing method $method in $class\n";
            $errors++;
        } else {
            echo "OK: $class::$method\n";
        }
    } else {
        $class = '\\' . ltrim($ref['class'], '\\');
        $method = $ref['method'];
        if (!class_exists($class)) {
            echo "Missing class $class\n";
            $errors++;
            continue;
        }
        if (!method_exists($class, $method)) {
            echo "Missing method $method in $class\n";
            $errors++;
        } else {
            echo "OK: $class::$method\n";
        }
    }
}

if ($errors === 0) {
    echo "Smoke check passed: all referenced controller methods exist (or classes found)\n";
    exit(0);
} else {
    echo "Smoke check failed: $errors missing items\n";
    exit(3);
}
