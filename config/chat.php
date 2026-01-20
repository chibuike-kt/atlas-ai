<?php
// config/chat.php
// Receives { user, messages } and returns { reply }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Use POST']);
  exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$messages = $body['messages'] ?? [];
$user = $body['user'] ?? [];

if (!is_array($messages) || count($messages) === 0) {
  http_response_code(400);
  echo json_encode(['error' => 'messages required']);
  exit;
}

$apiKey = "" . getenv('OPENAI_API_KEY');
if (!$apiKey) {
  // Or set $apiKey = '...'; but env is safer
  http_response_code(500);
  echo json_encode(['error' => 'OPENAI_API_KEY not set']);
  exit;
}

// --- Build request to OpenAI ---
$payload = [
  'model' => 'gpt-4.1-mini', // fast + solid; change if you want
  'messages' => $messages,
  'temperature' => 0.7,
];

// cURL
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
  http_response_code(500);
  echo json_encode(['error' => 'cURL error: ' . $err]);
  exit;
}

$data = json_decode($response, true);

if ($code < 200 || $code >= 300) {
  http_response_code($code);
  echo json_encode(['error' => $data['error']['message'] ?? 'OpenAI error', 'raw' => $data]);
  exit;
}

$reply = $data['choices'][0]['message']['content'] ?? '';
echo json_encode(['reply' => $reply]);
