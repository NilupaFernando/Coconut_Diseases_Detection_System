<?php
session_start();
header("Content-Type: text/plain");

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please login first.";
    exit();
}

require_once 'config.php'; // Should contain $OPENAI_API_KEY
$user_id = $_SESSION['user_id'];

// ✅ Get user message
$userMessage = trim($_POST['message'] ?? '');
if (!$userMessage) {
    echo "Please type a question.";
    exit();
}

// ✅ OpenAI API setup
$apiKey = $OPENAI_API_KEY; // Load from config.php
$payload = [
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => "You are a coconut farming assistant. Use verified coconut disease info (stem bleeding, bud rot, bud root dropping, caterpillar infestation). If unsure, say 'I don't know, please consult an expert.'"],
        ["role" => "user", "content" => $userMessage]
    ],
    "temperature" => 0.3
];

// ✅ cURL request to OpenAI
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if ($response === false) {
    echo "Curl Error: " . curl_error($ch);
    exit();
}
curl_close($ch);

// ✅ Parse API response
$result = json_decode($response, true);
if (isset($result['error'])) {
    echo "OpenAI API Error: " . $result['error']['message'];
    exit();
}

$botReply = $result['choices'][0]['message']['content'] ?? "I don't know, please consult an expert.";

// ✅ Save chat history to MySQL
try {
    $pdo = new PDO("mysql:host=localhost;dbname=coconut_chatbot", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, user_message, bot_reply) VALUES (:user, :msg, :bot)");
    $stmt->execute([
        ":user" => $user_id,
        ":msg"  => $userMessage,
        ":bot"  => $botReply
    ]);
} catch (PDOException $e) {
    // Log error if needed, but don't break chat
    // error_log($e->getMessage());
}

// ✅ Return AI response
echo $botReply;
?>
