<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/connection.php';

// Path helpers
if (!function_exists('hms_url')) {
    function hms_url($path = '') {
        // Strip modules/<subfolder>/ from the path if present
        $path = preg_replace('/^modules\/[^\/]+\//', '', $path);
        
        // Build base URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        
        // Return full URL
        return $protocol . "://" . $host . "/hms/" . ltrim($path, '/');
    }
}

if (!function_exists('hms_path')) {
    function hms_path($path = '') {
        // Strip modules/<subfolder>/ from the path if present
        $path = preg_replace('/^modules\/[^\/]+\//', '', $path);
        return dirname(__DIR__) . '/' . ltrim($path, '/');
    }
}
?>
