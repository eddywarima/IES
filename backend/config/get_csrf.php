<?php
require_once __DIR__ . '/session.php';
header('Content-Type: application/json');
echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
?> 