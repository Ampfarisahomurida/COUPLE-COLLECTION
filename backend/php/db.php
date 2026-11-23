<?php
require_once __DIR__ . '/config.php';

function get_db($respondOnFailure = true){
  static $pdo = null;
  if($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  try{
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  }catch(PDOException $e){
    if(!$respondOnFailure){
      throw $e;
    }
    error_log('Database connection failed for ' . DB_HOST . '/' . DB_NAME . ': ' . $e->getMessage());
    $mysqlError = (int)($e->errorInfo[1] ?? 0);
    if($mysqlError === 1045){
      $message = 'MySQL rejected DB_USER or DB_PASS. Update the local .env with credentials for your MySQL server, then run php backend/php/seed.php.';
    }elseif($mysqlError === 1049){
      $message = 'The database in DB_NAME does not exist. Check the local .env and run php backend/php/seed.php with a MySQL account that can create databases.';
    }elseif(in_array($mysqlError, [2002, 2003, 2005], true)){
      $message = 'Cannot reach MySQL at DB_HOST. Start your MySQL server and check DB_HOST in the local .env.';
    }else{
      $message = 'The database is unavailable. Check your local .env MySQL settings and server permissions.';
    }
    http_response_code(503);
    echo json_encode(['error'=>$message]);
    exit;
  }
  return $pdo;
}
