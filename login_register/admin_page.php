<?php
session_start();
require_once 'config.php';        // users_db
require_once 'db_disease.php';    // coconut_diseases_db
require_once 'db_messages.php';   // messages database

// Check if admin is logged in
if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

// Verify role from users_db
$email = $_SESSION['email'];
$result = $conn->query("SELECT role, name FROM users WHERE email = '$email'");
$user = $result->fetch_assoc();

if($user['role'] !== 'admin'){
    header("Location: user_page.php");
    exit();
}

// Fetch users from users_db into an array (so we can reuse)
$users_result = $conn->query("SELECT id, name, email, role, disease FROM users"); // added disease column
$users = [];
while($row = $users_result->fetch_assoc()){
    $users[] = $row;
}

// Fetch diseases from coconut_diseases_db
$diseases_result = $conn_disease->query("SELECT * FROM diseases");
$diseases = [];
while($row = $diseases_result->fetch_assoc()){
    $diseases[] = $row;
}

// Handle add disease form
if(isset($_POST['add_disease'])){
    $name = $_POST['name'];
    $description = $_POST['description'];
    $precautions = $_POST['precautions'];
    $conn_disease->query("INSERT INTO diseases (name, description, precautions) VALUES ('$name', '$description', '$precautions')");
    header("Location: admin_page.php");
    exit();
}

// Handle delete disease
if(isset($_GET['delete_disease'])){
    $id = intval($_GET['delete_disease']);
    $conn_disease->query("DELETE FROM diseases WHERE id=$id");
    header("Location: admin_page.php");
    exit();
}

// Handle sending messages
if(isset($_POST['send_message'])){
    $user_id = $_POST['user_id'];
    $title = $_POST['title'];
    $message = $_POST['message'];
    $conn_messages->query("INSERT INTO messages (user_id, title, message) VALUES ('$user_id', '$title', '$message')");
    header("Location: admin_page.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Coconut Disease Detection</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <!-- Navbar -->
  <header class="bg-green-900 text-white p-4 flex justify-between items-center">
    <h1 class="text-xl font-bold">Admin Dashboard</h1>
    <div>
      <span class="mr-4">Welcome, <?= htmlspecialchars($user['name']); ?> (Admin)</span>
      <a href="logout.php" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Logout</a>
    </div>
  </header>

  <main class="p-6 space-y-10">
    
    <!-- User Management -->
    <section>
      <h2 class="text-2xl font-semibold mb-4">Registered Users</h2>
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full table-auto">
          <thead class="bg-green-700 text-white">
            <tr>
              <th class="p-3 text-left">ID</th>
              <th class="p-3 text-left">Name</th>
              <th class="p-3 text-left">Email</th>
              <th class="p-3 text-left">Role</th>
              <th class="p-3 text-left">Disease</th> <!-- Added Disease Column -->
              <th class="p-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($users as $row): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="p-3"><?= $row['id']; ?></td>
                <td class="p-3"><?= htmlspecialchars($row['name']); ?></td>
                <td class="p-3"><?= htmlspecialchars($row['email']); ?></td>
                <td class="p-3"><?= ucfirst($row['role']); ?></td>
                <td class="p-3"><?= isset($row['disease']) ? htmlspecialchars($row['disease']) : '-'; ?></td>
                <td class="p-3">
                  <?php if($row['role'] !== 'admin'): ?>
                    <a href="delete_user.php?id=<?= $row['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this user?');" 
                       class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                       Delete
                    </a>
                  <?php else: ?>
                    <span class="text-gray-500">N/A</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Disease Management -->
    <section>
      <h2 class="text-2xl font-semibold mb-4">Manage Diseases & Precautions</h2>

      <!-- Add Disease Form -->
      <div class="bg-white p-6 rounded-lg shadow mb-6">
        <form method="POST" class="space-y-4">
          <input type="text" name="name" placeholder="Disease Name" class="w-full border p-2 rounded" required>
          <textarea name="description" placeholder="Description" class="w-full border p-2 rounded" required></textarea>
          <textarea name="precautions" placeholder="Precautionary Measures" class="w-full border p-2 rounded" required></textarea>
          <button type="submit" name="add_disease" class="bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800">Add Disease</button>
        </form>
      </div>

      <!-- Disease List -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="w-full table-auto">
          <thead class="bg-green-700 text-white">
            <tr>
              <th class="p-3 text-left">ID</th>
              <th class="p-3 text-left">Name</th>
              <th class="p-3 text-left">Description</th>
              <th class="p-3 text-left">Precautions</th>
              <th class="p-3 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($diseases as $row): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="p-3"><?= $row['id']; ?></td>
                <td class="p-3 font-bold"><?= htmlspecialchars($row['name']); ?></td>
                <td class="p-3"><?= htmlspecialchars($row['description']); ?></td>
                <td class="p-3"><?= htmlspecialchars($row['precautions']); ?></td>
                <td class="p-3">
                  <a href="admin_page.php?delete_disease=<?= $row['id']; ?>" 
                     onclick="return confirm('Delete this disease info?');" 
                     class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                     Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Send Message to User -->
      <section>
        <h2 class="text-2xl font-semibold mb-4">Send Message / Notification to User</h2>
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <form method="POST" class="space-y-4">
                <select name="user_id" class="w-full border p-2 rounded" required>
                    <option value="">--Select User--</option>
                    <?php foreach($users as $u): ?>
                        <?php if($u['role'] === 'user'): ?>
                            <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['name']); ?> (<?= htmlspecialchars($u['email']); ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="title" placeholder="Message Title" class="w-full border p-2 rounded" required>
                <textarea name="message" placeholder="Type your message here..." class="w-full border p-2 rounded" required></textarea>
                <button type="submit" name="send_message" class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">
                    Send Message
                </button>
            </form>
        </div>
      </section>

    </section>
  </main>
</body>
</html>
