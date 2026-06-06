<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';
if(!$token || !$password){ http_response_code(400); echo json_encode(['error'=>'token and password required']); exit; }

$pdo = get_db();
$stmt = $pdo->prepare('SELECT pr.id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = :token LIMIT 1');
$stmt->execute([':token'=>$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ http_response_code(400); echo json_encode(['error'=>'Invalid token']); exit; }
if(new DateTime($row['expires_at']) < new DateTime()){
  http_response_code(400); echo json_encode(['error'=>'Token expired']); exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$upd = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
$upd->execute([':hash'=>$hash,':id'=>$row['user_id']]);

// Delete reset tokens for user
$del = $pdo->prepare('DELETE FROM password_resets WHERE user_id = :uid');
$del->execute([':uid'=>$row['user_id']]);

echo json_encode(['ok'=>true]);
