<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(204);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["error" => "Use POST"]);
  exit;
}

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);

$text = isset($body["text"]) ? trim($body["text"]) : "";
if ($text === "") {
  http_response_code(400);
  echo json_encode(["error" => "Missing text"]);
  exit;
}

// Keep payload sane
if (mb_strlen($text) > 2000) {
  $text = mb_substr($text, 0, 2000);
}

$apiKey = getenv("OPENAI_API_KEY");
if (!$apiKey) {
  http_response_code(500);
  echo json_encode(["error" => "OPENAI_API_KEY not set"]);
  exit;
}


// Choose model + voice
$model = $body["model"] ?? "tts-1-hd";     // high quality :contentReference[oaicite:1]{index=1}
$voice = $body["voice"] ?? "marin";       // recommended voices :contentReference[oaicite:2]{index=2}
$format = $body["format"] ?? "mp3";       // mp3 is easy in browsers

$payload = json_encode([
  "model" => $model,
  "voice" => $voice,
  "input" => $text,
  "format" => $format
]);

$ch = curl_init("https://api.openai.com/v1/audio/speech");
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer " . $apiKey,
    "Content-Type: application/json"
  ],
  CURLOPT_POSTFIELDS => $payload
]);

$audio = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($audio === false) {
  http_response_code(500);
  echo json_encode(["error" => "cURL error: " . curl_error($ch)]);
  curl_close($ch);
  exit;
}
curl_close($ch);

if ($code < 200 || $code >= 300) {
  http_response_code($code);
  echo $audio; // OpenAI error JSON
  exit;
}

// Return raw audio
header_remove("Content-Type");
header("Content-Type: audio/mpeg");
echo $audio;
