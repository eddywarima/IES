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

function log_action($conn, $action, $details) {
    $admin_id = $_SESSION['user_id'] ?? 0;
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_type, user_id, action, details) VALUES ("admin", ?, ?, ?)');
    $stmt->bind_param('iss', $admin_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

switch ($action) {
    case 'list':
        $sql = 'SELECT c.id, u.name, s.name AS seat, c.status FROM candidates c JOIN users u ON c.user_id = u.id JOIN seats s ON c.seat_id = s.id ORDER BY c.id DESC';
        $result = $conn->query($sql);
        $candidates = [];
        while ($row = $result->fetch_assoc()) {
            $candidates[] = [
                'ID' => $row['id'],
                'Name' => $row['name'],
                'Seat' => $row['seat'],
                'Status' => $row['status']
            ];
        }
        echo json_encode(['success' => true, 'candidates' => $candidates]);
        break;
    case 'approve':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid candidate ID']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE candidates SET status="approved" WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_action($conn, 'approve_candidate', json_encode(['id'=>$id]));
            echo json_encode(['success' => true, 'message' => 'Candidate approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve candidate']);
        }
        $stmt->close();
        break;
    case 'reject':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid candidate ID']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE candidates SET status="rejected" WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_action($conn, 'reject_candidate', json_encode(['id'=>$id]));
            echo json_encode(['success' => true, 'message' => 'Candidate rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject candidate']);
        }
        $stmt->close();
        break;
    case 'details':
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid candidate ID']);
            exit;
        }
        $sql = 'SELECT c.id, u.name, s.name AS seat, c.status, c.manifesto, c.documents, c.photo FROM candidates c JOIN users u ON c.user_id = u.id JOIN seats s ON c.seat_id = s.id WHERE c.id=?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $candidate = $result->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'candidate' => $candidate]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 