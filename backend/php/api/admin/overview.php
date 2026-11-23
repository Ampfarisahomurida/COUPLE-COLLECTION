<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../session.php';

if(empty($_SESSION['is_admin'])){
  http_response_code(401);
  echo json_encode(['error'=>'Authentication required']);
  exit;
}
if($_SERVER['REQUEST_METHOD'] !== 'GET'){
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

try{
  $pdo = get_db();
  $metrics = $pdo->query("
    SELECT
      COALESCE(SUM(CASE WHEN status IN ('paid','processing','shipped','delivered') THEN total ELSE 0 END), 0) AS gross_revenue,
      COALESCE(SUM(CASE WHEN status IN ('paid','processing','shipped','delivered') THEN 1 ELSE 0 END), 0) AS paid_orders,
      COALESCE(SUM(CASE WHEN status = 'awaiting_payment_eft' THEN 1 ELSE 0 END), 0) AS awaiting_payment_orders,
      COUNT(*) AS total_orders
    FROM orders
  ")->fetch(PDO::FETCH_ASSOC);
  $customers = $pdo->query("
    SELECT
      COUNT(*) AS registered_customers,
      COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS new_customers_30_days
    FROM users
    WHERE is_admin = 0
  ")->fetch(PDO::FETCH_ASSOC);
  $recentCustomers = $pdo->query("
    SELECT email, created_at
    FROM users
    WHERE is_admin = 0
    ORDER BY created_at DESC
    LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);
  $monthlySales = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total) AS revenue
    FROM orders
    WHERE status IN ('paid','processing','shipped','delivered')
      AND created_at >= DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
  ")->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'metrics'=>[
      'gross_revenue'=>(float)$metrics['gross_revenue'],
      'paid_orders'=>(int)$metrics['paid_orders'],
      'awaiting_payment_orders'=>(int)$metrics['awaiting_payment_orders'],
      'total_orders'=>(int)$metrics['total_orders'],
      'registered_customers'=>(int)$customers['registered_customers'],
      'new_customers_30_days'=>(int)$customers['new_customers_30_days']
    ],
    'monthly_sales'=>$monthlySales,
    'recent_customers'=>$recentCustomers
  ]);
}catch(Throwable $e){
  error_log('Admin overview failed: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error'=>'Business overview could not be loaded']);
}
