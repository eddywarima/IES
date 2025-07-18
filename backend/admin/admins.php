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
        $result = $conn->query('SELECT id, username, name, created_at FROM admins ORDER BY id DESC');
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        echo json_encode(['success' => true, 'admins' => $admins]);
        break;
    case 'add':
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$name || !$password) {
            echo json_encode(['success' => false, 'message' => 'All fields required']);
            exit;
        }
        // Check for duplicate username
        $stmt = $conn->prepare('SELECT id FROM admins WHERE username=?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }
        $stmt->close();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO admins (username, password, name) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $hashed_password, $name);
        if ($stmt->execute()) {
            log_action($conn, 'add_admin', json_encode(['username'=>$username,'name'=>$name]));
            echo json_encode(['success' => true, 'message' => 'Admin added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add admin']);
        }
        $stmt->close();
        break;
    case 'remove':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM admins WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_action($conn, 'remove_admin', json_encode(['id'=>$id]));
            echo json_encode(['success' => true, 'message' => 'Admin removed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove admin']);
        }
        $stmt->close();
        break;
    case 'change_password':
        $id = intval($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';
        if (!$id || !$password) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE admins SET password=? WHERE id=?');
        $stmt->bind_param('si', $hashed_password, $id);
        if ($stmt->execute()) {
            log_action($conn, 'change_admin_password', json_encode(['id'=>$id]));
            echo json_encode(['success' => true, 'message' => 'Password changed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to change password']);
        }
        $stmt->close();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?> 