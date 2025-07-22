<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

switch ($action) {
    case 'list':
        header('Content-Type: application/json');
        $result = $conn->query('SELECT id, user_type, user_id, action, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 100');
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;
    case 'filter':
        header('Content-Type: application/json');
        $result = $conn->query('SELECT id, user_type, user_id, action, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 100');
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;
    case 'export':
        // Export audit logs as CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_logs_export.csv"');
        $result = $conn->query('SELECT id, user_type, user_id, action, details, created_at FROM audit_logs ORDER BY id DESC');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'User Type', 'User ID', 'Action', 'Details', 'Created At']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['id'], $row['user_type'], $row['user_id'], $row['action'], $row['details'], $row['created_at']]);
        }
        fclose($output);
        exit;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 