<?php
// Webhook receiver with Stripe signature verification if secret provided
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhook_secret = getenv('STRIPE_WEBHOOK_SECRET') ?: (defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : null);

if($webhook_secret){
	try{
		$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
	}catch(\UnexpectedValueException $e){
		http_response_code(400);
		echo json_encode(['error'=>'Invalid payload']);
		exit;
	}catch(\Stripe\Exception\SignatureVerificationException $e){
		http_response_code(400);
		echo json_encode(['error'=>'Invalid signature']);
		exit;
	}
	// Handle the event
	file_put_contents(__DIR__ . '/../../logs/payment_webhook.log', date('c') . " VERIFIED " . $event->type . " " . json_encode($event->data->object) . "\n", FILE_APPEND);
}else{
	// No webhook secret configured — log raw payload
	file_put_contents(__DIR__ . '/../../logs/payment_webhook.log', date('c') . " UNVERIFIED " . json_encode([ 'headers' => getallheaders(), 'body' => $payload ]) . "\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(['ok'=>true]);
