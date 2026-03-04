<?php
/**
 * Paul Willmott - AI Persona API
 * Refined Strategic Version
 */

// 1. ERROR REPORTING & LOGGING (errors logged, not displayed)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// 2. CONFIGURATION
// API key loaded from environment variable. Set via hosting panel or .htaccess:
//   SetEnv OPENAI_API_KEY sk-proj-...
$openai_api_key = getenv('OPENAI_API_KEY');
if (empty($openai_api_key)) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured']);
    exit();
}

// 3. HEADERS & CORS (restrict to your domain)
$allowed_origins = [
    'https://paulwillmott.ai',
    'https://www.paulwillmott.ai',
];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Allow localhost for development
    if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 4. INPUT VALIDATION
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$user_message = isset($input['message']) ? trim($input['message']) : '';

if (empty($user_message)) {
    http_response_code(400);
    echo json_encode(['error' => 'No message provided']);
    exit();
}

// Limit message length to prevent abuse
if (strlen($user_message) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Message too long (max 2000 characters)']);
    exit();
}

// 5. THE SYSTEM PROMPT (loaded from external file)
$system_prompt = file_get_contents(__DIR__ . '/system-prompt.txt');
if ($system_prompt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load system prompt']);
    exit();
}

// 6. API REQUEST DATA
$data = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_message]
    ],
    'max_tokens' => 800,
    'temperature' => 0.6
];

// 7. CURL EXECUTION
$ch = curl_init('https://api.openai.com/v1/chat/completions');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($data),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key
    ],
    // On Hostinger: if SSL fails, set the CA bundle path instead of disabling verification.
    // e.g. CURLOPT_CAINFO => '/etc/ssl/certs/ca-certificates.crt',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// 8. OUTPUT & ERROR HANDLING
if ($curl_error) {
    http_response_code(500);
    error_log('Chat API cURL error: ' . $curl_error);
    echo json_encode(['error' => 'Connection failed. Please try again.']);
    exit();
}

if ($http_code !== 200) {
    http_response_code(502);
    error_log('OpenAI API error: HTTP ' . $http_code . ' - ' . $response);
    echo json_encode(['error' => 'AI service temporarily unavailable. Please try again.']);
    exit();
}

$result = json_decode($response, true);
$assistant_message = $result['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';

echo json_encode(['response' => $assistant_message]);
