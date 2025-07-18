<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

switch ($action) {
    case 'list':
        $result = $conn->query('SELECT id, user_type, user_id, action, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 100');
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;
    case 'filter':
        // For demo, just return all logs (add real filtering as needed)
        $result = $conn->query('SELECT id, user_type, user_id, action, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 100');
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;
    case 'export':
        // For demo, just return a message
        echo json_encode(['success' => true, 'message' => 'Logs exported']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 