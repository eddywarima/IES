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
        $result = $conn->query('SELECT id, voter_id, name FROM users ORDER BY id DESC');
        $voters = [];
        while ($row = $result->fetch_assoc()) {
            $voters[] = [
                'ID' => $row['id'],
                'Voter ID' => $row['voter_id'],
                'Name' => $row['name']
            ];
        }
        echo json_encode(['success' => true, 'voters' => $voters]);
        break;
    case 'approve':
        // For this example, assume all registered voters are approved by default
        echo json_encode(['success' => true, 'message' => 'Voter approved (no action needed)']);
        break;
    case 'deactivate':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid voter ID']);
            exit;
        }
        // For demo, just delete the user (in real app, add a status column)
        $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_action($conn, 'deactivate_voter', json_encode(['id'=>$id]));
            echo json_encode(['success' => true, 'message' => 'Voter deactivated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to deactivate voter']);
        }
        $stmt->close();
        break;
    case 'details':
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid voter ID']);
            exit;
        }
        $stmt = $conn->prepare('SELECT id, voter_id, name, registered_at FROM users WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $voter = $result->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'voter' => $voter]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 