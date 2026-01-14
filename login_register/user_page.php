<?php
session_start();

// ✅ Auto-start Flask if not running
$flask_url = "http://127.0.0.1:5000";
$flask_check = @file_get_contents($flask_url);

if ($flask_check === FALSE) {
    // Flask not running → start in background
    $pythonPath = "C:\\Users\\Nilupa\\AppData\\Local\\Programs\\Python\\Python313\\python.exe"; // 🔹 Update this path if needed
    $flaskScript = "C:\\Users\\Nilupa\\OneDrive\\Desktop\\ai-api\\app.py";
    shell_exec("start /B \"$pythonPath\" \"$flaskScript\"");
}

if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

require_once 'config.php';       // Users DB connection
require_once 'db_disease.php';   // Diseases DB connection
require_once 'db_messages.php';  // Messages DB connection

$user_id = $_SESSION['user_id'];

// Fetch user info
$user_res = $conn->query("SELECT * FROM users WHERE id=$user_id");
$user = $user_res->fetch_assoc();

// Fetch messages
$messages = $conn_messages->query("SELECT * FROM messages WHERE user_id = $user_id ORDER BY created_at DESC");

// Fetch diseases from DB
$diseases_result = $conn_disease->query("SELECT * FROM diseases");
$diseases_array = [];
while($row = $diseases_result->fetch_assoc()){
    $diseases_array[$row['name']] = $row['precautions'];
}

// Handle disease update
$saved_disease = '';
$saved_precaution = '';
if(isset($_POST['disease_name']) && !empty($_POST['disease_name'])){
    $disease_name = $_POST['disease_name'];
    $precaution = $diseases_array[$disease_name] ?? "No precautions found";

    // Save to user profile
    $stmt = $conn->prepare("UPDATE users SET disease=? WHERE id=?");
    $stmt->bind_param("si", $disease_name, $user_id);
    $stmt->execute();
    $stmt->close();

    $saved_disease = $disease_name;
    $saved_precaution = $precaution;
} else {
    $saved_disease = $user['disease'] ?? '';
    $saved_precaution = $diseases_array[$saved_disease] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Coconut Disease Detection</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 text-gray-800 font-sans">

<!-- Navbar -->
<header class="bg-green-900 text-white shadow p-4">
  <div class="container mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">Coconut Disease Detection</h1>
    <nav class="space-x-4">
      <a href="#about" class="hover:underline">About</a>
      <a href="#diseases" class="hover:underline">Diseases</a>
      <a href="#messages" class="hover:underline">Messages</a>
      <a href="#detection" class="hover:underline">Detection</a>
      <a href="#chatbot" class="hover:underline">Chatbot</a>
      <a href="#contact" class="hover:underline">Contact</a>
      <button onclick="window.location.href='logout.php'" class="bg-red-600 px-2 py-1 rounded">Logout</button>
    </nav>
  </div>
</header>

<!-- Hero -->
<section class="relative w-full h-screen bg-center bg-cover flex items-center justify-center text-white" 
style="background-image: url('images/coconut-bg.jpeg');">
  <div class="absolute inset-0 bg-black bg-opacity-50"></div>
  <div class="relative z-10 text-center p-8">
    <h2 class="text-4xl md:text-5xl font-bold mb-4">
      Early Detection and Management of Coconut Diseases
    </h2>
    <p class="text-lg md:text-xl">
      Focusing on Stem and Bud Regions with AI-Powered Solutions
    </p>
  </div>
</section>

<!-- About -->
<section id="about" class="py-12 px-6 md:px-16">
  <h3 class="text-2xl font-bold mb-4">About the Research</h3>
  <p>
    This research focuses on developing an intelligent system for detecting and managing coconut pests and diseases,
    especially in the <strong>stem and bud regions</strong>. Using deep learning models, we aim to provide accurate disease identification,
    timely alerts, and suggest precautionary and treatment measures for farmers and agriculture officers.
  </p>
</section>

<!-- Diseases -->
<section id="diseases" class="py-12 px-6 md:px-16 bg-white">
  <h3 class="text-2xl font-bold mb-6">Major Diseases & Precautionary Advice</h3>
  <div class="grid md:grid-cols-2 gap-8">
    <?php foreach($diseases_array as $disease => $precaution): ?>
      <div class="bg-green-100 p-6 rounded-xl shadow">
        <h4 class="text-xl font-semibold mb-2"><?= htmlspecialchars($disease); ?></h4>
        <p class="mb-2">Precaution:</p>
        <ul class="list-disc ml-5 text-sm">
          <li><?= htmlspecialchars($precaution); ?></li>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Messages -->
<section id="messages" class="py-12 px-6 md:px-16 bg-white">
  <h3 class="text-2xl font-bold mb-6">Messages from Admin</h3>
  <?php if($messages->num_rows > 0): ?>
      <?php while($msg = $messages->fetch_assoc()): ?>
          <div class="bg-green-100 p-4 mb-4 rounded shadow">
              <h4 class="font-bold"><?= htmlspecialchars($msg['title']); ?></h4>
              <p><?= htmlspecialchars($msg['message']); ?></p>
              <small class="text-gray-500"><?= $msg['created_at']; ?></small>
          </div>
      <?php endwhile; ?>
  <?php else: ?>
      <p class="text-gray-500">No messages yet.</p>
  <?php endif; ?>
</section>

<!-- ✅ Detection System -->
<section id="detection" class="py-12 px-6 md:px-16 bg-green-50">
  <h3 class="text-2xl font-bold mb-4">Detection System</h3>
  <p class="mb-6">
    Upload an image of the coconut stem or bud to identify potential diseases using our AI-based detection system.
  </p>

  <div class="bg-white p-6 rounded-xl shadow-md max-w-xl mx-auto">
    <form id="detectionForm">
      <input type="file" accept="image/*" id="imageInput" class="mb-4 block w-full" required/>
      <img id="preview" src="" alt="Image Preview" class="w-full h-64 object-cover rounded-xl mb-4 hidden">
      <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-xl w-full">Detect Disease</button>
    </form>
    <div id="result" class="mt-4 text-center font-bold text-green-700 hidden"></div>
  </div>
</section>

<!-- ✅ Chatbot Section -->
<section id="chatbot" class="py-12 px-6 md:px-16 bg-green-50">
  <h3 class="text-2xl font-bold mb-4">Ask the Coconut Assistant 🌴</h3>
  <div class="bg-white p-6 rounded-xl shadow-md max-w-xl mx-auto flex flex-col gap-4">
    <div id="chat-box" class="h-64 overflow-y-auto p-2 border rounded flex flex-col gap-2 bg-gray-50"></div>
    <div class="flex gap-2">
      <input type="text" id="user-input" placeholder="Ask about coconut diseases..." class="flex-1 border rounded px-3 py-2"/>
      <button onclick="sendMessage()" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded">Send</button>
    </div>
  </div>
</section>

<!-- ✅ Chatbot Script -->
<script>
async function sendMessage(){
    const input = document.getElementById('user-input');
    const message = input.value.trim();
    if(!message) return;
    addMessage(message, 'user');
    input.value = '';
    addMessage('Thinking...', 'bot');

    const response = await fetch('chat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message=' + encodeURIComponent(message)
    });

    const botReply = await response.text();
    const lastBot = document.querySelector('#chat-box .bot:last-child');
    if(lastBot) lastBot.textContent = botReply;
}

function addMessage(text, sender){
    const chatBox = document.getElementById('chat-box');
    const div = document.createElement('div');
    div.textContent = text;
    div.classList.add('p-2', 'rounded');
    if(sender === 'user'){
        div.classList.add('bg-green-200', 'self-end');
    } else {
        div.classList.add('bg-gray-200', 'bot', 'self-start');
    }
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>

<!-- ✅ Detection Script (Flask Integration) -->
<script>
const input = document.getElementById('imageInput');
const preview = document.getElementById('preview');
const result = document.getElementById('result');
const form = document.getElementById('detectionForm');

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const file = input.files[0];
  if (!file) return alert("Please select an image.");

  preview.src = URL.createObjectURL(file);
  preview.classList.remove('hidden');

  const formData = new FormData();
  formData.append('image', file);

  result.innerHTML = "⏳ Detecting disease...";
  result.classList.remove('hidden');
  result.classList.add('text-yellow-600');

  try {
    const response = await fetch('http://127.0.0.1:5000/predict', { method: 'POST', body: formData });
    const data = await response.json();

    if (data.error) {
      result.innerHTML = "❌ " + data.error;
      result.classList.add('text-red-600');
    } else {
      result.innerHTML = `
        ✅ <strong>${data.predicted_disease}</strong> detected.<br>
        Confidence: ${data.confidence}%<br>
        <small>Probabilities: ${JSON.stringify(data.probabilities)}</small>
      `;
      result.classList.remove('text-yellow-600');
      result.classList.add('text-green-700');
    }
  } catch (err) {
    result.innerHTML = "❌ Error connecting to AI model. Please check Flask server.";
    result.classList.add('text-red-600');
  }
});
</script>


<!-- Contact -->
<section id="contact" class="py-12 px-6 md:px-16 bg-white">
  <h3 class="text-2xl font-bold mb-4">Contact & Collaboration</h3>
  <p>If you're interested in collaborating, testing our system, or need further info, feel free to contact:</p>
  <p class="mt-2 text-sm"><strong>Researcher:</strong> Nilupa Fernando</p>
  <p class="text-sm"><strong>Email:</strong> nilupafernando0505@gmail.com</p>
  <p class="text-sm"><strong>University:</strong> General Sir John Kotelalwala Defence University</p>
</section>

</body>
</html>
