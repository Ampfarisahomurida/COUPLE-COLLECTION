<?php
require_once __DIR__ . '/config.php';

function get_db(){
  static $pdo = null;
  if($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  try{
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  }catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error'=>'DB connection failed']);
    exit;
  }
  return $pdo;
}
