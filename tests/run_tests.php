<?php
function http($url, $method='GET', $data=null, $cookieJar=null, $extraHeaders=[]){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  if($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
  $headers = $extraHeaders;
  if($data !== null){
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $headers[] = 'Content-Type: application/json';
  }
  if($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  if($cookieJar !== null){
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
  }
  $res = curl_exec($ch);
  if($res === false){
    $error = curl_error($ch);
    curl_close($ch);
    throw new RuntimeException($error);
  }
  $info = curl_getinfo($ch);
  $body = substr($res, $info['header_size']);
  curl_close($ch);
  return [$info['http_code'], $res, $body];
}

$base = 'http://localhost:8000';
$endpoints = [
  '/auth.html' => 200,
  '/backend/php/api/products.php' => 200,
  '/backend/php/api/auth/me.php' => 200,
  '/backend/php/api/auth/login.php' => 405,
  '/backend/php/api/auth/register.php' => 405,
  '/backend/php/api/auth/logout.php' => 405,
  '/backend/php/api/cart.php' => 401,
  '/backend/php/api/checkout.php' => 405,
  '/backend/php/api/payment/create_intent.php' => 405,
  '/backend/php/api/admin/login.php' => 405,
  '/backend/php/api/admin/orders.php' => 401,
  '/backend/php/api/admin/overview.php' => 401,
  '/backend/php/api/age_verification.php' => 200
];
$failures = 0;
foreach($endpoints as $e=>$expectedCode){
  list($code,$res) = http($base.$e);
  $result = $code === $expectedCode ? 'PASS' : 'FAIL';
  if($code !== $expectedCode) $failures++;
  echo $result . ' ' . $e . ' -> ' . $code . ' (expected ' . $expectedCode . ")\n";
}

list($cartCode,) = http($base.'/backend/php/api/cart.php', 'POST', ['id'=>1,'name'=>'Test','qty'=>1]);
echo ($cartCode === 401 ? 'PASS' : 'FAIL') . ' unauthenticated basket write -> ' . $cartCode . " (expected 401)\n";
if($cartCode !== 401) $failures++;

list($registerCode,) = http($base.'/backend/php/api/auth/register.php', 'POST', ['email'=>'customer@example.com','password'=>'long-enough-password']);
echo ($registerCode === 403 ? 'PASS' : 'FAIL') . ' registration without CSRF token -> ' . $registerCode . " (expected 403)\n";
if($registerCode !== 403) $failures++;

list($loginCode,) = http($base.'/backend/php/api/auth/login.php', 'POST', ['email'=>'customer@example.com','password'=>'long-enough-password']);
echo ($loginCode === 403 ? 'PASS' : 'FAIL') . ' login without CSRF token -> ' . $loginCode . " (expected 403)\n";
if($loginCode !== 403) $failures++;

list($logoutCode,) = http($base.'/backend/php/api/auth/logout.php', 'POST', []);
echo ($logoutCode === 403 ? 'PASS' : 'FAIL') . ' logout without CSRF token -> ' . $logoutCode . " (expected 403)\n";
if($logoutCode !== 403) $failures++;

$sessionJar = tempnam(sys_get_temp_dir(), 'couple-auth-');
try{
  list($sessionCode,,$sessionBody) = http($base.'/backend/php/api/auth/me.php', 'GET', null, $sessionJar);
  $session = json_decode($sessionBody, true);
  $csrfToken = is_array($session) ? ($session['csrf_token'] ?? '') : '';
  $sessionReady = $sessionCode === 200 && is_string($csrfToken) && $csrfToken !== '';
  echo ($sessionReady ? 'PASS' : 'FAIL') . " account session issues a CSRF token -> {$sessionCode}\n";
  if(!$sessionReady) $failures++;

  if($sessionReady){
    list($invalidRegisterCode,) = http(
      $base.'/backend/php/api/auth/register.php',
      'POST',
      ['email'=>'not-an-email','password'=>'long-enough-password'],
      $sessionJar,
      ['X-CSRF-Token: '.$csrfToken]
    );
    echo ($invalidRegisterCode === 400 ? 'PASS' : 'FAIL') . ' registration validates input with a session token -> ' . $invalidRegisterCode . " (expected 400)\n";
    if($invalidRegisterCode !== 400) $failures++;

    list($validLogoutCode,) = http(
      $base.'/backend/php/api/auth/logout.php',
      'POST',
      ['csrf_token'=>$csrfToken],
      $sessionJar,
      ['X-CSRF-Token: '.$csrfToken]
    );
    echo ($validLogoutCode === 200 ? 'PASS' : 'FAIL') . ' logout accepts the matching session token -> ' . $validLogoutCode . " (expected 200)\n";
    if($validLogoutCode !== 200) $failures++;

    list($afterLogoutCode,,$afterLogoutBody) = http($base.'/backend/php/api/auth/me.php', 'GET', null, $sessionJar);
    $afterLogout = json_decode($afterLogoutBody, true);
    $loggedOut = $afterLogoutCode === 200 && is_array($afterLogout) && empty($afterLogout['authenticated']);
    echo ($loggedOut ? 'PASS' : 'FAIL') . ' logout clears the customer session -> ' . $afterLogoutCode . "\n";
    if(!$loggedOut) $failures++;
  }
}finally{
  if(is_file($sessionJar)) unlink($sessionJar);
}

list($checkoutCode,) = http($base.'/backend/php/api/checkout.php', 'POST', []);
echo ($checkoutCode === 401 ? 'PASS' : 'FAIL') . ' unauthenticated checkout -> ' . $checkoutCode . " (expected 401)\n";
if($checkoutCode !== 401) $failures++;

list($intentCode,) = http($base.'/backend/php/api/payment/create_intent.php', 'POST', ['cart'=>[]]);
echo ($intentCode === 401 ? 'PASS' : 'FAIL') . ' unauthenticated payment intent -> ' . $intentCode . " (expected 401)\n";
if($intentCode !== 401) $failures++;

if($failures){
  exit(1);
}
echo "All endpoint smoke tests passed.\n";
