<?php
/**
 * Global Helper Functions
 * ฟังก์ชันช่วยเหลือที่ใช้ทั่วทั้งโปรเจกต์
 */

/**
 * Get application URL
 */
function url($path = '') {
    return \App\Config\App::url($path);
}

/**
 * Get asset URL
 */
function asset($path) {
    $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
    return $baseUrl . '/assets/' . ltrim($path, '/');
}

/**
 * Get old input value
 */
function old($key, $default = '') {
    return $_SESSION['_old'][$key] ?? $default;
}

/**
 * Generate CSRF token
 */
function csrf_token() {
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

/**
 * Generate CSRF field HTML
 */
function csrf_field() {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

/**
 * Dump and die
 */
function dd(...$vars) {
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Clean and sanitize input
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Redirect back
 */
function back() {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

/**
 * Check if current route matches
 */
function is_route($route) {
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return $current === $route || strpos($current, $route) === 0;
}

/**
 * Get flash message
 */
function flash($key, $value = null) {
    if ($value === null) {
        // Get flash message
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    // Set flash message
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Get current user
 */
function auth() {
    return new \App\Services\AuthService();
}

/**
 * Format date in Thai
 */
function thai_date($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Escape output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
