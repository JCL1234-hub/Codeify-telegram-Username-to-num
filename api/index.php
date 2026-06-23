<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Your Telegram Bot Token (get from @BotFather)
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');

/**
 * Get user information from Telegram by username
 */
function getTelegramUserInfo($username) {
    // Remove @ if present
    $username = ltrim($username, '@');
    
    // Clean username
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    
    if (empty($username)) {
        return [
            'success' => false,
            'error' => 'Invalid username provided'
        ];
    }
    
    // Telegram API endpoint
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getChat";
    
    // Prepare parameters
    $params = [
        'chat_id' => '@' . $username
    ];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $curlError
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Telegram API returned HTTP ' . $httpCode
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !$data['ok']) {
        $errorMsg = isset($data['description']) ? $data['description'] : 'Unknown error from Telegram API';
        return [
            'success' => false,
            'error' => $errorMsg
        ];
    }
    
    $chat = $data['result'];
    
    // Extract user information
    $userInfo = [
        'success' => true,
        'data' => [
            'user_id' => $chat['id'] ?? null,
            'username' => $chat['username'] ?? null,
            'first_name' => $chat['first_name'] ?? null,
            'last_name' => $chat['last_name'] ?? null,
            'full_name' => trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')),
            'type' => $chat['type'] ?? null,
            'is_bot' => $chat['is_bot'] ?? false,
            'phone_number' => $chat['phone_number'] ?? null, // Only available for your contacts
            'bio' => $chat['bio'] ?? null,
            'photo' => $chat['photo'] ?? null
        ]
    ];
    
    return $userInfo;
}

/**
 * Get user information by user ID
 */
function getTelegramUserInfoById($userId) {
    if (!is_numeric($userId)) {
        return [
            'success' => false,
            'error' => 'Invalid user ID'
        ];
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getChat";
    $params = ['chat_id' => $userId];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Failed to fetch user info'
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !$data['ok']) {
        return [
            'success' => false,
            'error' => $data['description'] ?? 'Unknown error'
        ];
    }
    
    $chat = $data['result'];
    
    return [
        'success' => true,
        'data' => [
            'user_id' => $chat['id'] ?? null,
            'username' => $chat['username'] ?? null,
            'first_name' => $chat['first_name'] ?? null,
            'last_name' => $chat['last_name'] ?? null,
            'full_name' => trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')),
            'type' => $chat['type'] ?? null,
            'is_bot' => $chat['is_bot'] ?? false,
            'phone_number' => $chat['phone_number'] ?? null
        ]
    ];
}

// Handle API request
$method = $_SERVER['REQUEST_METHOD'];
$response = [];

if ($method === 'GET') {
    // GET request - get username from query parameter
    $username = $_GET['username'] ?? $_GET['user'] ?? null;
    $userId = $_GET['user_id'] ?? $_GET['id'] ?? null;
    
    if ($userId) {
        $response = getTelegramUserInfoById($userId);
    } elseif ($username) {
        $response = getTelegramUserInfo($username);
    } else {
        $response = [
            'success' => false,
            'error' => 'Please provide username or user_id parameter',
            'example' => [
                'by_username' => '/api/index.php?username=@username',
                'by_user_id' => '/api/index.php?user_id=123456789'
            ]
        ];
    }
} elseif ($method === 'POST') {
    // POST request - get username from JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response = [
            'success' => false,
            'error' => 'Invalid JSON payload'
        ];
    } else {
        $username = $input['username'] ?? $input['user'] ?? null;
        $userId = $input['user_id'] ?? $input['id'] ?? null;
        
        if ($userId) {
            $response = getTelegramUserInfoById($userId);
        } elseif ($username) {
            $response = getTelegramUserInfo($username);
        } else {
            $response = [
                'success' => false,
                'error' => 'Please provide username or user_id in request body'
            ];
        }
    }
} else {
    $response = [
        'success' => false,
        'error' => 'Method not allowed. Use GET or POST'
    ];
}

// Output response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
