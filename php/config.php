<?php
if (basename($_SERVER['PHP_SELF']) == 'config.php') {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not permitted.');
}

$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$mysqlHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$mysqlDb   = $_ENV['DB_DATABASE'] ?? 'auth_system';
$mysqlUser = $_ENV['DB_USERNAME'] ?? 'root';
$mysqlPass = $_ENV['DB_PASSWORD'] ?? '';
$mysqlChar = 'utf8mb4';

$dsn = "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=$mysqlChar";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, $options);
} catch (PDOException $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: MySQL is offline.',
        'data' => null
    ]);
    exit;
}

try {
    $mongoUri = $_ENV['MONGO_URI'] ?? 'mongodb://127.0.0.1:27017';
    $mongoManager = new MongoDB\Driver\Manager($mongoUri);
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $mongoManager->executeCommand('admin', $command);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: MongoDB is offline.',
        'data' => null
    ]);
    exit;
}

try {
    if (!class_exists('Redis')) {
        throw new Exception('Redis extension is not enabled in PHP configuration.');
    }
    $redis = new Redis();
    $redisHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
    $redisPort = (int)($_ENV['REDIS_PORT'] ?? 6379);
    $redis->connect($redisHost, $redisPort);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'data' => null
    ]);
    exit;
}
