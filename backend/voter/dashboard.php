<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'voter') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$voter_id = $_SESSION['user_id'];

// Fetch ongoing elections
$elections = [];
$res = $conn->query("SELECT id, name, start_date, end_date FROM elections WHERE status='ongoing' ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    $election_id = $row['id'];
    // Fetch seats for this election
    $seats = [];
    $seat_res = $conn->query("SELECT id, name FROM seats");
    while ($seat = $seat_res->fetch_assoc()) {
        // Fetch candidates for this seat
        $candidates = [];
        $cand_res = $conn->query("SELECT c.id, u.name FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.seat_id=".$seat['id']." AND c.status='approved'");
        while ($cand = $cand_res->fetch_assoc()) {
            $candidates[] = $cand;
        }
        // Check if voter has already voted for this seat in this election
        $vote_res = $conn->prepare("SELECT id FROM votes WHERE election_id=? AND seat_id=? AND voter_id=?");
        $vote_res->bind_param('iii', $election_id, $seat['id'], $voter_id);
        $vote_res->execute();
        $vote_res->store_result();
        $has_voted = $vote_res->num_rows > 0;
        $vote_res->close();
        $seats[] = [
            'id' => $seat['id'],
            'name' => $seat['name'],
            'candidates' => $candidates,
            'has_voted' => $has_voted
        ];
    }
    $elections[] = [
        'id' => $election_id,
        'name' => $row['name'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'seats' => $seats
    ];
}
// Fetch voter name
$stmt = $conn->prepare('SELECT name FROM users WHERE id=?');
$stmt->bind_param('i', $voter_id);
$stmt->execute();
$stmt->bind_result($voter_name);
$stmt->fetch();
$stmt->close();

echo json_encode(['success' => true, 'voter_name' => $voter_name, 'elections' => $elections]);
?> 