<?php
/**
 * Telegram User Info API
 * Get user details by username or user_id
 * Deployable on Vercel
 */

// ============ HEADERS ============
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============ ERROR REPORTING ============
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-error.log');

// ============ CONFIGURATION ============
// Get BOT_TOKEN from environment variable (Vercel) or fallback
$botToken = getenv('BOT_TOKEN') ?: getenv('VERCEL_ENV_BOT_TOKEN') ?: '';

if (empty($botToken) || $botToken === 'YOUR_BOT_TOKEN_HERE') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'BOT_TOKEN not configured. Please set BOT_TOKEN in Vercel environment variables.',
        'solution' => 'Go to Vercel Dashboard → Project → Settings → Environment Variables → Add BOT_TOKEN'
    ], JSON_PRETTY_PRINT);
    exit();
}

// ============ HELPER FUNCTIONS ============

/**
 * Validate Telegram username
 */
function validateUsername($username) {
    $username = ltrim($username, '@');
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    return $username;
}

/**
 * Make cURL request to Telegram API
 */
function telegramApiRequest($url, $params = []) {
    $ch = curl_init();
    
    $fullUrl = $url . '?' . http_build_query($params);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'TelegramBotAPI/1.0',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $curlError,
            'http_code' => $httpCode
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response from Telegram',
            'raw_response' => substr($response, 0, 500)
        ];
    }
    
    return [
        'success' => true,
        'data' => $data,
        'http_code' => $httpCode
    ];
}

/**
 * Get user info by username
 */
function getUserByUsername($username, $botToken) {
    $username = validateUsername($username);
    
    if (empty($username)) {
        return [
            'success' => false,
            'error' => 'Invalid username format'
        ];
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/getChat";
    $params = ['chat_id' => '@' . $username];
    
    $result = telegramApiRequest($url, $params);
    
    if (!$result['success']) {
        return $result;
    }
    
    $data = $result['data'];
    
    if (!$data['ok']) {
        return [
            'success' => false,
            'error' => $data['description'] ?? 'Unknown Telegram API error',
            'error_code' => $data['error_code'] ?? null
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
            'phone_number' => $chat['phone_number'] ?? null,
            'bio' => $chat['bio'] ?? null,
            'description' => $chat['description'] ?? null,
            'photo' => $chat['photo'] ?? null,
            'invite_link' => $chat['invite_link'] ?? null,
            'has_private_forwards' => $chat['has_private_forwards'] ?? null,
            'has_restricted_voice_and_video_messages' => $chat['has_restricted_voice_and_video_messages'] ?? null,
            'join_to_send_messages' => $chat['join_to_send_messages'] ?? null,
            'join_by_request' => $chat['join_by_request'] ?? null,
            'can_send_polls' => $chat['can_send_polls'] ?? null,
            'can_send_other_messages' => $chat['can_send_other_messages'] ?? null,
            'can_add_web_page_previews' => $chat['can_add_web_page_previews'] ?? null,
            'can_change_info' => $chat['can_change_info'] ?? null,
            'can_invite_users' => $chat['can_invite_users'] ?? null,
            'can_pin_messages' => $chat['can_pin_messages'] ?? null,
            'can_manage_topics' => $chat['can_manage_topics'] ?? null
        ]
    ];
}

/**
 * Get user info by user ID
 */
function getUserById($userId, $botToken) {
    if (!is_numeric($userId) || $userId <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid user ID format'
        ];
    }
    
    $url = "https://api.telegram.org/bot{$botToken}/getChat";
    $params = ['chat_id' => (int)$userId];
    
    $result = telegramApiRequest($url, $params);
    
    if (!$result['success']) {
        return $result;
    }
    
    $data = $result['data'];
    
    if (!$data['ok']) {
        return [
            'success' => false,
            'error' => $data['description'] ?? 'Unknown Telegram API error',
            'error_code' => $data['error_code'] ?? null
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
            'phone_number' => $chat['phone_number'] ?? null,
            'bio' => $chat['bio'] ?? null,
            'description' => $chat['description'] ?? null,
            'photo' => $chat['photo'] ?? null,
            'invite_link' => $chat['invite_link'] ?? null
        ]
    ];
}

/**
 * Get bot info (for testing)
 */
function getBotInfo($botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/getMe";
    $result = telegramApiRequest($url, []);
    
    if (!$result['success']) {
        return $result;
    }
    
    $data = $result['data'];
    
    if (!$data['ok']) {
        return [
            'success' => false,
            'error' => $data['description'] ?? 'Failed to get bot info'
        ];
    }
    
    $bot = $data['result'];
    
    return [
        'success' => true,
        'data' => [
            'id' => $bot['id'] ?? null,
            'username' => $bot['username'] ?? null,
            'first_name' => $bot['first_name'] ?? null,
            'is_bot' => true,
            'can_join_groups' => $bot['can_join_groups'] ?? null,
            'can_read_all_group_messages' => $bot['can_read_all_group_messages'] ?? null,
            'supports_inline_queries' => $bot['supports_inline_queries'] ?? null
        ]
    ];
}

// ============ REQUEST HANDLING ============

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

try {
    if ($method === 'GET') {
        // Check if testing bot
        if (isset($_GET['test']) || isset($_GET['bot'])) {
            $response = getBotInfo($botToken);
        } else {
            $username = $_GET['username'] ?? $_GET['user'] ?? null;
            $userId = $_GET['user_id'] ?? $_GET['id'] ?? null;
            
            if ($userId) {
                $response = getUserById($userId, $botToken);
            } elseif ($username) {
                $response = getUserByUsername($username, $botToken);
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Missing parameter. Provide either username or user_id',
                    'usage' => [
                        'by_username' => 'GET /api/index.php?username=@telegram_username',
                        'by_username_alt' => 'GET /api/index.php?user=@telegram_username',
                        'by_user_id' => 'GET /api/index.php?user_id=123456789',
                        'by_user_id_alt' => 'GET /api/index.php?id=123456789',
                        'test_bot' => 'GET /api/index.php?test=true'
                    ]
                ];
            }
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !is_array($input)) {
            $response = [
                'success' => false,
                'error' => 'Invalid JSON payload. Please send valid JSON.',
                'example' => [
                    'username' => '@telegram_username',
                    'user_id' => 123456789
                ]
            ];
        } else {
            $username = $input['username'] ?? $input['user'] ?? null;
            $userId = $input['user_id'] ?? $input['id'] ?? null;
            
            if ($userId) {
                $response = getUserById($userId, $botToken);
            } elseif ($username) {
                $response = getUserByUsername($username, $botToken);
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Missing parameter. Provide either username or user_id',
                    'example' => [
                        'username' => '@telegram_username',
                        'user_id' => 123456789
                    ]
                ];
            }
        }
    } else {
        http_response_code(405);
        $response = [
            'success' => false,
            'error' => 'Method not allowed. Use GET or POST.',
            'allowed_methods' => ['GET', 'POST']
        ];
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ];
}

// ============ OUTPUT ============

// Set appropriate HTTP status code
if (!$response['success'] && isset($response['error'])) {
    http_response_code(400);
}

// Output JSON
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Log any errors for debugging
if (!$response['success']) {
    error_log('API Error: ' . ($response['error'] ?? 'Unknown error'));
}
?>
