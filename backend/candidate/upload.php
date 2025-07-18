<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
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
$user_id = $_SESSION['user_id'];
$manifesto = trim($_POST['manifesto'] ?? '');
$documents_path = null;
$photo_path = null;
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
// Handle documents upload
if (isset($_FILES['documents']) && $_FILES['documents']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Documents must be a PDF file.']);
        exit;
    }
    $documents_path = 'backend/candidate/uploads/doc_' . $user_id . '_' . time() . '.pdf';
    move_uploaded_file($_FILES['documents']['tmp_name'], __DIR__ . '/uploads/' . basename($documents_path));
}
// Handle photo upload
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
        echo json_encode(['success' => false, 'message' => 'Photo must be JPG or PNG.']);
        exit;
    }
    $photo_path = 'backend/candidate/uploads/photo_' . $user_id . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/uploads/' . basename($photo_path));
}
// Update candidate record
$set = 'manifesto=?';
$params = [$manifesto];
$types = 's';
if ($documents_path) {
    $set .= ', documents=?';
    $params[] = $documents_path;
    $types .= 's';
}
if ($photo_path) {
    $set .= ', photo=?';
    $params[] = $photo_path;
    $types .= 's';
}
$params[] = $user_id;
$types .= 'i';
$sql = "UPDATE candidates SET $set WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Application updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update application.']);
}
$stmt->close();
?> 