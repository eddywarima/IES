<?php
require_once __DIR__ . '/config/session.php';
session_unset();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out']);
?> 