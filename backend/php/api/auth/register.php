<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');
if(!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? null))){
  http_response_code(403); echo json_encode(['error'=>'Please refresh the page and try again']); exit;
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255){
  http_response_code(400); echo json_encode(['error'=>'Enter a valid email address']); exit;
}
if(strlen($password) < 10 || strlen($password) > 72){
  http_response_code(400); echo json_encode(['error'=>'Use a password between 10 and 72 characters']); exit;
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
if($stmt->fetch()){ http_response_code(409); echo json_encode(['error'=>'Email already registered']); exit; }

$hash = password_hash($password, PASSWORD_DEFAULT);
try{
  $ins = $pdo->prepare('INSERT INTO users (email,password_hash,is_admin) VALUES (:email,:hash,0)');
  $ins->execute([':email'=>$email,':hash'=>$hash]);
}catch(PDOException $e){
  if($e->getCode() === '23000'){
    http_response_code(409);
    echo json_encode(['error'=>'Email already registered']);
    exit;
  }
  throw $e;
}
$uid = $pdo->lastInsertId();

// Set session
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$uid;
$_SESSION['email'] = $email;
unset($_SESSION['is_admin'], $_SESSION['admin_id'], $_SESSION['admin_email']);

echo json_encode(['ok'=>true,'user_id'=>$uid,'email'=>$email,'is_admin'=>false]);
