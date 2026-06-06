<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session.php';
require_once __DIR__ . '/../../db.php';

if(empty($_SESSION['is_admin'])){ http_response_code(401); echo json_encode(['error'=>'auth required']); exit; }

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

if(empty($_FILES['image'])){ http_response_code(400); echo json_encode(['error'=>'image required']); exit; }

$file = $_FILES['image'];
if($file['error'] !== UPLOAD_ERR_OK){ http_response_code(400); echo json_encode(['error'=>'upload failed']); exit; }

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$allowed = ['jpg','jpeg','png','webp','gif'];
if(!in_array(strtolower($ext), $allowed)){ http_response_code(400); echo json_encode(['error'=>'invalid file type']); exit; }

$destDir = __DIR__ . '/../../uploads';
if(!is_dir($destDir)) mkdir($destDir, 0755, true);
$fname = uniqid('img_') . '.' . $ext;
$dest = $destDir . '/' . $fname;
if(!move_uploaded_file($file['tmp_name'], $dest)){ http_response_code(500); echo json_encode(['error'=>'move failed']); exit; }

$url = dirname($_SERVER['SCRIPT_NAME']) . '/../../uploads/' . $fname;
echo json_encode(['ok'=>true,'path'=>$url,'filename'=>$fname]);
