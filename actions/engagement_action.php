<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/daily_engagement.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'simulate_today' || $action === 'force_simulate') {
    $force = ($action === 'force_simulate');
    $result = run_daily_engagement($force);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ'], JSON_UNESCAPED_UNICODE);
exit;
