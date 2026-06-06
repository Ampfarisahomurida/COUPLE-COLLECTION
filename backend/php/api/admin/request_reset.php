<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = isset($input['email']) ? trim($input['email']) : '';
if(!$email){ http_response_code(400); echo json_encode(['error'=>'email required']); exit; }

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$user){
  // Do not reveal whether email exists
  echo json_encode(['ok'=>true]);
  exit;
}

$token = bin2hex(random_bytes(32));
$expires = (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');
$ins = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid,:token,:exp)');
$ins->execute([':uid'=>$user['id'],':token'=>$token,':exp'=>$expires]);

// NOTE: In production send this token by email with a reset link.
// For now we return the token in the response for convenience.
echo json_encode(['ok'=>true,'reset_token'=>$token]);
