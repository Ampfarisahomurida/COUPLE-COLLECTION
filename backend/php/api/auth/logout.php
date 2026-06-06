<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session.php';
// logout user
unset($_SESSION['user_id']);
unset($_SESSION['email']);
unset($_SESSION['is_admin']);
echo json_encode(['ok'=>true]);
