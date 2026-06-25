<?php
header('Content-Type: application/json');

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

require_once 'config.php';

$token = null;
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

if (isset($headers['authorization']) && preg_match('/bearer\s(\S+)/i', $headers['authorization'], $matches)) {
    $token = $matches[1];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/bearer\s(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    $token = $matches[1];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/bearer\s(\S+)/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Session token is missing.',
        'data' => null
    ]);
    exit;
}

$redisKey = 'session:' . $token;
$userId = $redis->get($redisKey);

if (!$userId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired or invalid token.',
        'data' => null
    ]);
    exit;
}

$userId = (int)$userId;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    $redis->del($redisKey);
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully.',
        'data' => null
    ]);
    exit;
}

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $mysqlData = $stmt->fetch();

        if (!$mysqlData) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User credentials not found in MySQL system.',
                'data' => null
            ]);
            exit;
        }

        $filter = ['user_id' => $userId];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $mongoManager->executeQuery('auth_system.profiles', $query);
        $mongoDocs = $cursor->toArray();

        $extendedData = [];
        if (!empty($mongoDocs)) {
            $doc = (array)$mongoDocs[0];
            $extendedData = [
                'age' => isset($doc['age']) ? (int)$doc['age'] : null,
                'dob' => isset($doc['dob']) ? (string)$doc['dob'] : null,
                'contact' => isset($doc['contact']) ? (string)$doc['contact'] : null,
                'address' => isset($doc['address']) ? (string)$doc['address'] : null
            ];
        }

        $profile = [
            'username' => (string)$mysqlData['username'],
            'email' => (string)$mysqlData['email'],
            'age' => $extendedData['age'] ?? '',
            'dob' => $extendedData['dob'] ?? '',
            'contact' => $extendedData['contact'] ?? '',
            'address' => $extendedData['address'] ?? ''
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => $profile
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error retrieving profile: ' . $e->getMessage(),
            'data' => null
        ]);
    }
    exit;
}

if ($method === 'POST') {
    if (
        (isset($_POST['age']) && is_array($_POST['age'])) ||
        (isset($_POST['dob']) && is_array($_POST['dob'])) ||
        (isset($_POST['contact']) && is_array($_POST['contact'])) ||
        (isset($_POST['address']) && is_array($_POST['address']))
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid parameter format. Arrays are not allowed.',
            'data' => null
        ]);
        exit;
    }

    $ageInput = isset($_POST['age']) && $_POST['age'] !== '' ? $_POST['age'] : null;
    $age = null;
    if ($ageInput !== null) {
        if (!filter_var($ageInput, FILTER_VALIDATE_INT)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid age. Age must be a valid integer.',
                'data' => null
            ]);
            exit;
        }
        $age = (int)$ageInput;
        if ($age < 0 || $age > 120) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid age. Age must be between 0 and 120.',
                'data' => null
            ]);
            exit;
        }
    }

    $dobInput = isset($_POST['dob']) && $_POST['dob'] !== '' ? $_POST['dob'] : null;
    $dobString = null;
    if ($dobInput !== null) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobInput)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date of birth format. Must be YYYY-MM-DD.',
                'data' => null
            ]);
            exit;
        }
        $dateObj = DateTime::createFromFormat('Y-m-d', $dobInput);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $dobInput) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date of birth. Real date required.',
                'data' => null
            ]);
            exit;
        }
        if ($dateObj > new DateTime('today')) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Date of birth cannot be in the future.',
                'data' => null
            ]);
            exit;
        }
        $dobString = $dobInput;
    }

    $contactInput = isset($_POST['contact']) && $_POST['contact'] !== '' ? $_POST['contact'] : null;
    $contactString = null;
    if ($contactInput !== null) {
        $cleanContact = preg_replace('/[\s\-\(\)]/', '', $contactInput);
        if (!preg_match('/^\+?[0-9]{7,15}$/', $cleanContact)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid contact number. Must be between 7 and 15 digits.',
                'data' => null
            ]);
            exit;
        }
        $contactString = $contactInput;
    }

    $addressInput = isset($_POST['address']) && $_POST['address'] !== '' ? $_POST['address'] : null;
    $addressString = $addressInput !== null ? (string)$addressInput : null;

    try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['user_id' => $userId],
            ['$set' => [
                'user_id' => $userId,
                'age' => $age,
                'dob' => $dobString,
                'contact' => $contactString,
                'address' => $addressString,
                'updated_at' => date('Y-m-d H:i:s')
            ]],
            ['upsert' => true]
        );

        $mongoManager->executeBulkWrite('auth_system.profiles', $bulk);

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => null
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error saving profile: ' . $e->getMessage(),
            'data' => null
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Method Not Allowed.',
    'data' => null
]);
