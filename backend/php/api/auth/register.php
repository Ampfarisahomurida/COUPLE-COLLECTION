<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
if(!$email || !$password){ http_response_code(400); echo json_encode(['error'=>'email and password required']); exit; }

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
if($stmt->fetch()){ http_response_code(409); echo json_encode(['error'=>'Email already registered']); exit; }

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $pdo->prepare('INSERT INTO users (email,password_hash,is_admin) VALUES (:email,:hash,0)');
$ins->execute([':email'=>$email,':hash'=>$hash]);
$uid = $pdo->lastInsertId();

// Set session
$_SESSION['user_id'] = (int)$uid;
$_SESSION['email'] = $email;

echo json_encode(['ok'=>true,'user_id'=>$uid]);
