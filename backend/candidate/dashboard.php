<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$user_id = $_SESSION['user_id'];
// Fetch candidate record
$stmt = $conn->prepare('SELECT c.id, c.status, s.name AS seat, c.manifesto, c.documents, c.photo FROM candidates c JOIN seats s ON c.seat_id = s.id WHERE c.user_id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$candidate = $result->fetch_assoc();
$stmt->close();
// Fetch candidate name
$stmt = $conn->prepare('SELECT name FROM users WHERE id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($candidate_name);
$stmt->fetch();
$stmt->close();
if ($candidate) {
    echo json_encode([
        'success' => true,
        'candidate_name' => $candidate_name,
        'status' => $candidate['status'],
        'seat' => $candidate['seat'],
        'manifesto' => $candidate['manifesto'],
        'documents' => $candidate['documents'],
        'photo' => $candidate['photo']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Candidate application not found.']);
}
?> 