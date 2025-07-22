<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

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

function log_action($conn, $action, $details) {
    $admin_id = $_SESSION['user_id'] ?? 0;
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_type, user_id, action, details) VALUES ("admin", ?, ?, ?)');
    $stmt->bind_param('iss', $admin_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

switch ($action) {
    case 'stats':
        header('Content-Type: application/json');
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
        header('Content-Type: application/json');
        log_action($conn, 'count_results', 'Results counting triggered');
        echo json_encode(['success' => true, 'message' => 'Counting triggered']);
        break;
    case 'publish':
        header('Content-Type: application/json');
        log_action($conn, 'publish_results', 'Results published');
        echo json_encode(['success' => true, 'message' => 'Results published']);
        break;
    case 'export':
        // Export results as CSV
        log_action($conn, 'export_results', 'Results exported');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="results_export.csv"');
        $sql = 'SELECT s.name AS seat, COUNT(v.id) AS votes
                FROM votes v
                JOIN seats s ON v.seat_id = s.id
                JOIN elections e ON v.election_id = e.id
                WHERE e.status IN ("ongoing", "completed")
                GROUP BY s.id';
        $result = $conn->query($sql);
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Seat', 'Votes']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['seat'], $row['votes']]);
        }
        fclose($output);
        exit;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 