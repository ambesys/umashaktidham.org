<?php
// This file contains helper functions used throughout the application.

function redirect($url) {
    header("Location: $url");
    exit();
}

function flash($message) {
    $_SESSION['flash_message'] = $message;
}

function old($key, $default = '') {
    return isset($_SESSION['old'][$key]) ? $_SESSION['old'][$key] : $default;
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? 'guest';
}
?>