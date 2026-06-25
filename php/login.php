<?php
header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed. Use POST.',
        'data' => null
     ]);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$rateKey = 'rate:login:' . $ip;
try {
    $attempts = (int)$redis->get($rateKey);
    if ($attempts >= 10) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many login attempts. Please try again in 1 minute.',
            'data' => null
        ]);
        exit;
    }
    if ($attempts === 0) {
        $redis->setex($rateKey, 60, 1);
    } else {
        $redis->incr($rateKey);
    }
} catch (Exception $e) {
}

if (
    (isset($_POST['identity']) && is_array($_POST['identity'])) ||
    (isset($_POST['password']) && is_array($_POST['password']))
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameter format. Arrays are not allowed.',
        'data' => null
    ]);
    exit;
}

$identity = filter_input(INPUT_POST, 'identity', FILTER_SANITIZE_SPECIAL_CHARS);
$password = $_POST['password'] ?? null;

if (!$identity || !$password) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide both username/email and password.',
        'data' => null
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, username, email, password FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials. Please try again.',
            'data' => null
        ]);
        exit;
    }

    $token = bin2hex(random_bytes(32));

    $redisKey = 'session:' . $token;
    $redis->setex($redisKey, 3600, $user['id']);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful!',
        'data' => [
            'token' => $token
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'data' => null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred: ' . $e->getMessage(),
        'data' => null
    ]);
}
