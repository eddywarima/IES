<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

function log_action($conn, $action, $details) {
    $admin_id = $_SESSION['user_id'] ?? 0;
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_type, user_id, action, details) VALUES ("admin", ?, ?, ?)');
    $stmt->bind_param('iss', $admin_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

switch ($action) {
    case 'stats':
        // Example: votes per seat for ongoing/completed elections
        $sql = 'SELECT s.name AS seat, COUNT(v.id) AS votes
                FROM votes v
                JOIN seats s ON v.seat_id = s.id
                JOIN elections e ON v.election_id = e.id
                WHERE e.status IN ("ongoing", "completed")
                GROUP BY s.id';
        $result = $conn->query($sql);
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
    case 'count':
        // For demo, just log the action
        log_action($conn, 'count_results', 'Results counting triggered');
        echo json_encode(['success' => true, 'message' => 'Counting triggered']);
        break;
    case 'publish':
        // For demo, just log the action
        log_action($conn, 'publish_results', 'Results published');
        echo json_encode(['success' => true, 'message' => 'Results published']);
        break;
    case 'export':
        // For demo, just log the action
        log_action($conn, 'export_results', 'Results exported');
        echo json_encode(['success' => true, 'message' => 'Results exported']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 