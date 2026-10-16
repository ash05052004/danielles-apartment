<?php
// Skip ngrok warning
header('ngrok-skip-browser-warning: true');

define('TELEGRAM_BOT_TOKEN', '8700960900:AAEX30xpzi6EwiMOcoJ7idqYKJTfSN19oDs');
require_once 'config.php';

// Get the update from Telegram
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) exit;

$message = $update['message'] ?? null;
if (!$message) exit;

$chat_id = $message['chat']['id'];
$text = $message['text'] ?? '';

// Check if it's a /start command with user ID
if (preg_match('/^\/start\s+USER_(\d+)$/', $text, $matches)) {
    $user_id = intval($matches[1]);
    
    // Save chat_id to user
    $stmt = $pdo->prepare("UPDATE users SET telegram_chat_id = ? WHERE id = ?");
    $stmt->execute([$chat_id, $user_id]);
    
    // Send confirmation
    $reply = "✅ *Connected Successfully!*\n\n";
    $reply .= "You will now receive notifications from Danielle's Apartment.\n\n";
    $reply .= "📱 You'll get alerts for:\n";
    $reply .= "• 💳 Payment reminders\n";
    $reply .= "• ✅ Payment verification\n";
    $reply .= "• 📅 Appointment updates\n";
    $reply .= "• 💬 New messages";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $reply,
        'parse_mode' => 'Markdown'
    ];
    
    file_get_contents("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage?" . http_build_query($data));
}
?>