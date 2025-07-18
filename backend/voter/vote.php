<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'voter') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}
$voter_id = $_SESSION['user_id'];
$election_id = intval($_POST['election_id'] ?? 0);
$seat_id = intval($_POST['seat_id'] ?? 0);
$candidate_id = intval($_POST['candidate_id'] ?? 0);
if (!$election_id || !$seat_id || !$candidate_id) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}
// Prevent double voting
$stmt = $conn->prepare('SELECT id FROM votes WHERE election_id=? AND seat_id=? AND voter_id=?');
$stmt->bind_param('iii', $election_id, $seat_id, $voter_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already voted for this seat.']);
    exit;
}
$stmt->close();
// Store vote (for demo, just store candidate_id as encrypted_vote)
$encrypted_vote = password_hash($candidate_id, PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO votes (election_id, seat_id, candidate_id, voter_id, encrypted_vote) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('iiiis', $election_id, $seat_id, $candidate_id, $voter_id, $encrypted_vote);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Vote cast successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cast vote.']);
}
$stmt->close();
?> 