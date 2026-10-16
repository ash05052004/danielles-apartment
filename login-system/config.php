<?php
// Database configuration for XAMPP
$host = 'localhost';
$dbname = 'user_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_check() {
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

// ===== reCAPTCHA keys (environment-aware) =====
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
    || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;

if ($isLocalhost) {
    // Localhost keys (registered for localhost)
    define('RECAPTCHA_SITE_KEY',   '6LejkuUtAAAAAJx22hD4EUwgN8zhPy7Ijcb9sv0i');
    define('RECAPTCHA_SECRET_KEY', '6LejkuUtAAAAADtV0ul2CInWhasuo_jgccBshA8_');
} else {
    // Live keys (registered for dapartment.freepage.cc)
    define('RECAPTCHA_SITE_KEY',   '6LcijewtAAAAAI9qojyDAVv499bb0UEB6Pt50uzg');
    define('RECAPTCHA_SECRET_KEY', '6LcijewtAAAAAGcRYG5s0JC8TQQuFnnuIMm3MPES');
}

// Get unread message counts for a user
function getUnreadCounts($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT message_type, COUNT(*) as count 
                           FROM messages 
                           WHERE receiver_id = ? AND is_read = 0 
                           GROUP BY message_type");
    $stmt->execute([$user_id]);
    $counts = ['message' => 0, 'complaint' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $counts[$row['message_type']] = $row['count'];
    }
    return $counts;
}