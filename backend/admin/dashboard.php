<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'summary';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

switch ($action) {
    case 'summary':
        // Dashboard summary: counts for elections, candidates, voters, admins
        $counts = [];
        $result = $conn->query('SELECT COUNT(*) AS count FROM elections');
        $counts['elections'] = $result ? (int)$result->fetch_assoc()['count'] : 0;
        $result = $conn->query('SELECT COUNT(*) AS count FROM candidates');
        $counts['candidates'] = $result ? (int)$result->fetch_assoc()['count'] : 0;
        $result = $conn->query('SELECT COUNT(*) AS count FROM users');
        $counts['voters'] = $result ? (int)$result->fetch_assoc()['count'] : 0;
        $result = $conn->query('SELECT COUNT(*) AS count FROM admins');
        $counts['admins'] = $result ? (int)$result->fetch_assoc()['count'] : 0;
        echo json_encode(['success' => true, 'summary' => $counts]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 