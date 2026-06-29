<?php
header('Content-Type: application/json');

// Inclusion of config.php is allowed since the self name will be db_status.php
require_once 'config.php';

$status = [
    'mysql_sqlite' => [
        'status' => 'offline',
        'driver' => 'none',
        'details' => null
    ],
    'mongodb' => [
        'status' => 'offline',
        'details' => null
    ],
    'redis' => [
        'status' => 'offline',
        'details' => null
    ]
];

// Test MySQL / SQLite
if (isset($pdo)) {
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $status['mysql_sqlite']['status'] = 'online';
        $status['mysql_sqlite']['driver'] = $driver;
        if ($driver === 'sqlite') {
            $status['mysql_sqlite']['details'] = 'Using local SQLite database fallback (full-time online).';
        } else {
            $status['mysql_sqlite']['details'] = 'Connected to remote MySQL cloud database successfully.';
        }
    } catch (Exception $e) {
        $status['mysql_sqlite']['details'] = 'Error getting driver name: ' . $e->getMessage();
    }
} else {
    $status['mysql_sqlite']['details'] = 'PDO object was not initialized.';
}

// Test MongoDB
if (isset($mongoManager)) {
    try {
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $mongoManager->executeCommand('auth_system', $command);
        $status['mongodb']['status'] = 'online';
        $status['mongodb']['details'] = 'Connected to MongoDB cloud cluster successfully.';
    } catch (Exception $e) {
        $status['mongodb']['details'] = 'Error: ' . $e->getMessage();
    }
} else {
    $status['mongodb']['details'] = 'MongoDB Manager was not initialized.';
}

// Test Redis
if (isset($redis)) {
    try {
        $pingResult = $redis->ping();
        $status['redis']['status'] = 'online';
        $status['redis']['details'] = 'Connected to Upstash Redis successfully. Ping response: ' . (is_bool($pingResult) ? ($pingResult ? 'true' : 'false') : $pingResult);
    } catch (Exception $e) {
        $status['redis']['details'] = 'Error: ' . $e->getMessage();
    }
} else {
    $status['redis']['details'] = 'Redis client was not initialized.';
}

echo json_encode([
    'success' => true,
    'message' => 'Database connections status check completed.',
    'data' => $status
], JSON_PRETTY_PRINT);
