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

if (
    (isset($_POST['username']) && is_array($_POST['username'])) ||
    (isset($_POST['email']) && is_array($_POST['email'])) ||
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

$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? null;

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid inputs. Please check your username, email, and password.',
        'data' => null
    ]);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters long.',
        'data' => null
    ]);
    exit;
}

try {
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $checkStmt->execute([$username, $email]);
    $existingUser = $checkStmt->fetch();

    if ($existingUser) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Username or email already exists.',
            'data' => null
        ]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $insertStmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $insertStmt->execute([$username, $email, $hashedPassword]);

    echo json_encode([
        'success' => true,
        'message' => 'Account successfully created! You can now sign in.',
        'data' => null
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'data' => null
    ]);
}
