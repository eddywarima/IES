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
        $result = $conn->query('SELECT id, name, start_date, end_date, status FROM elections ORDER BY id DESC');
        $elections = [];
        while ($row = $result->fetch_assoc()) {
            $elections[] = [
                'ID' => $row['id'],
                'Name' => $row['name'],
                'Start' => $row['start_date'],
                'End' => $row['end_date'],
                'Status' => $row['status']
            ];
        }
        echo json_encode(['success' => true, 'elections' => $elections]);
        break;
    case 'create':
        $name = trim($_POST['name'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $end = trim($_POST['end_date'] ?? '');
        if (!$name || !$start || !$end) {
            echo json_encode(['success' => false, 'message' => 'All fields required']);
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO elections (name, start_date, end_date) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $name, $start, $end);
        if ($stmt->execute()) {
            log_action($conn, 'create_election', json_encode(['name'=>$name,'start'=>$start,'end'=>$end]));
            echo json_encode(['success' => true, 'message' => 'Election created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create election']);
        }
        $stmt->close();
        break;
    case 'update':
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $end = trim($_POST['end_date'] ?? '');
        $status = trim($_POST['status'] ?? '');
        if (!$id || !$name || !$start || !$end || !$status) {
            echo json_encode(['success' => false, 'message' => 'All fields required']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE elections SET name=?, start_date=?, end_date=?, status=? WHERE id=?');
        $stmt->bind_param('ssssi', $name, $start, $end, $status, $id);
        if ($stmt->execute()) {
            log_action($conn, 'update_election', json_encode(['id'=>$id,'name'=>$name,'start'=>$start,'end'=>$end,'status'=>$status]));
            echo json_encode(['success' => true, 'message' => 'Election updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update election']);
        }
        $stmt->close();
        break;
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid election ID']);
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM elections WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_action($conn, 'delete_election', json_encode(['id'=>$id]));
            echo json_encode(['success' => true, 'message' => 'Election deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete election']);
        }
        $stmt->close();
        break;
    case 'activate':
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if (!$id || !in_array($status, ['upcoming','ongoing','completed'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE elections SET status=? WHERE id=?');
        $stmt->bind_param('si', $status, $id);
        if ($stmt->execute()) {
            log_action($conn, 'activate_election', json_encode(['id'=>$id,'status'=>$status]));
            echo json_encode(['success' => true, 'message' => 'Election status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 