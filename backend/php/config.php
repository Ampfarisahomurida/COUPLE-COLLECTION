<?php
// Prefer explicit server environment variables; use the private project .env locally.
$localEnvFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if(is_file($localEnvFile)){
  if(!is_readable($localEnvFile)){
    throw new RuntimeException('The project .env file exists but cannot be read.');
  }
  $localEnvLines = file($localEnvFile, FILE_IGNORE_NEW_LINES);
  if($localEnvLines === false){
    throw new RuntimeException('The project .env file could not be loaded.');
  }
  foreach($localEnvLines as $line){
    $line = trim($line);
    if($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$name, $value] = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);
    if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) || getenv($name) !== false) continue;
    if(strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))){
      $value = substr($value, 1, -1);
    }
    putenv($name . '=' . $value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
  }
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'couple_collection');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Site settings
define('SITE_NAME', getenv('SITE_NAME') ?: 'Couple Collection');
$deliveryFee = getenv('DELIVERY_FEE');
if($deliveryFee !== false && $deliveryFee !== '' && (!is_numeric($deliveryFee) || (float)$deliveryFee < 0)){
  throw new InvalidArgumentException('DELIVERY_FEE must be a non-negative number');
}
define('DELIVERY_FEE', $deliveryFee === false || $deliveryFee === '' ? 99.99 : (float)$deliveryFee);

// Payment provider placeholders (set your real keys in production)
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET') ?: 'sk_test_replace_me');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUB') ?: 'pk_test_replace_me');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');
