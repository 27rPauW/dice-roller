<?php
// Error reporting (log only, never display)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// CORS (restrict to your domain)
$allowed_origins = [
    'https://paulwillmott.ai',
    'https://www.paulwillmott.ai',
];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Simple file-based rate limiting (max 5 submissions per IP per hour)
$rate_limit_dir = __DIR__ . '/rate_limits';
if (!is_dir($rate_limit_dir)) {
    @mkdir($rate_limit_dir, 0700, true);
}
$ip_hash = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$rate_file = $rate_limit_dir . '/' . $ip_hash . '.txt';
$now = time();
$window = 3600; // 1 hour
$max_requests = 5;

$timestamps = [];
if (file_exists($rate_file)) {
    $timestamps = array_filter(
        explode("\n", file_get_contents($rate_file)),
        function ($ts) use ($now, $window) {
            return $ts && ($now - (int)$ts) < $window;
        }
    );
}
if (count($timestamps) >= $max_requests) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
    exit();
}
$timestamps[] = $now;
file_put_contents($rate_file, implode("\n", $timestamps));

// Get form data
$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

// Validate
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit();
}

// Sanitize inputs to prevent header injection
$name = str_replace(["\r", "\n"], '', $name);
$email = str_replace(["\r", "\n"], '', $email);

// Email settings
$to = 'contact@paulwillmott.ai';
$subject = 'Website Contact: ' . substr($name, 0, 100);

$email_body = "You have received a new message from your website contact form.\n\n";
$email_body .= "Name: " . $name . "\n";
$email_body .= "Email: " . $email . "\n\n";
$email_body .= "Message:\n" . $message . "\n";

$headers = "From: noreply@paulwillmott.ai\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
if (mail($to, $subject, $email_body, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send email']);
}
?>
