<?php

declare(strict_types=1);

if (!defined('BASE_URL')) {
    if (PHP_SAPI === 'cli-server') {
        define('BASE_URL', '');
    } else {
        define('BASE_URL', '/cooking-website');
    }
}

const DB_HOST = '127.0.0.1';
const DB_NAME = 'cookio_db';
const DB_USER = 'root';
const DB_PASS = '';
const UPLOAD_DIR = __DIR__ . '/../uploads/recipes/';
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    $base = rtrim(BASE_URL, '/');
    $target = ltrim($path, '/');
    $url = $base !== '' ? $base . '/' . $target : '/' . $target;
    header('Location: ' . $url);
    exit;
}

function is_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        session_start();
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !is_string($sessionToken) || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

function get_chef_badges(int $authorId): array
{
    static $cache = [];
    if (isset($cache[$authorId])) {
        return $cache[$authorId];
    }
    if (!function_exists('db')) {
        return [];
    }
    $badges = [];
    try {
        $recipesCount = (int) db()->query("SELECT COUNT(*) FROM recipes WHERE author_id = {$authorId} AND status = 'approved'")->fetchColumn();
        $cooksnapCount = (int) db()->query("SELECT COUNT(*) FROM comments WHERE user_id = {$authorId} AND image_url IS NOT NULL AND image_url != ''")->fetchColumn();
        $followersCount = (int) db()->query("SELECT COUNT(*) FROM follows WHERE author_id = {$authorId}")->fetchColumn();

        if ($recipesCount >= 3) {
            $badges[] = ['label' => 'Bếp Trưởng Vàng', 'icon' => '🥇', 'class' => 'badge-gold'];
        } elseif ($recipesCount >= 1) {
            $badges[] = ['label' => 'Đầu Bếp Triển Vọng', 'icon' => '👨‍🍳', 'class' => 'badge-rising'];
        }
        if ($cooksnapCount >= 1) {
            $badges[] = ['label' => 'Bậc Thầy Cooksnap', 'icon' => '📸', 'class' => 'badge-cooksnap'];
        }
        if ($followersCount >= 1) {
            $badges[] = ['label' => 'Được Yêu Thích', 'icon' => '🔥', 'class' => 'badge-popular'];
        }
    } catch (Throwable) {
    }

    $cache[$authorId] = $badges;
    return $badges;
}

