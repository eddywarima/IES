<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$voter_id = trim($_POST['voter_id'] ?? '');
$name = trim($_POST['name'] ?? '');
$seat = trim($_POST['seat'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!$voter_id || !$name || !$seat || !$password || !$confirm_password) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}
if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Check for duplicate voter_id in users
$stmt = $conn->prepare('SELECT id FROM users WHERE voter_id = ?');
$stmt->bind_param('s', $voter_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Voter ID already registered']);
    exit;
}
$stmt->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare('INSERT INTO users (voter_id, name, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $voter_id, $name, $hashed_password);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    $stmt->close();
    exit;
}
$user_id = $stmt->insert_id;
$stmt->close();

// Find or create seat
$seat_id = null;
$stmt = $conn->prepare('SELECT id FROM seats WHERE name = ?');
$stmt->bind_param('s', $seat);
$stmt->execute();
$stmt->bind_result($sid);
if ($stmt->fetch()) {
    $seat_id = $sid;
}
$stmt->close();
if (!$seat_id) {
    $stmt = $conn->prepare('INSERT INTO seats (name) VALUES (?)');
    $stmt->bind_param('s', $seat);
    $stmt->execute();
    $seat_id = $stmt->insert_id;
    $stmt->close();
}

// Insert candidate
$stmt = $conn->prepare('INSERT INTO candidates (user_id, seat_id) VALUES (?, ?)');
$stmt->bind_param('ii', $user_id, $seat_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful! Await admin approval. Redirecting to login...']);
} else {
    echo json_encode(['success' => false, 'message' => 'Candidate registration failed.']);
}
$stmt->close();
?> 