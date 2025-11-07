<?php
// D:\Xampp\htdocs\FP\home.php
session_start();

// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['username'])) {
    header("Location: /FP/index.php"); 
    exit();
}

// Include the PDO connection file for consistency
include 'db_connect.php';
include 'get_stats.php'; // Fetches $total_users and $resolved_cases
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Campus Lost & Found - Home</title>
  <link rel="icon" type="image/png" href="static/images/L&F.jpg" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .gradient-bg { 
        background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%); 
    }
    .hero-section {
        background-color: #f8f8f8;
        min-height: calc(100vh - 80px); 
        padding: 80px 0;
    }
  </style>
</head>
<body class="font-sans bg-gray-50">

  <header class="gradient-bg text-white shadow-lg">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="static/images/sggs.jpg" alt="SGGS Logo" class="h-10 w-auto rounded bg-white p-1 shadow" />
            <h1 class="text-2xl font-bold tracking-tight">Campus Lost & Found</h1>
        </div>
        <nav class="space-x-4">
            <a href="login.php" class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg font-semibold hover:bg-yellow-500 transition shadow-md">Login</a>
            <a href="signup.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-200 transition shadow-md">Sign Up</a>
        </nav>
    </div>
  </header>

  <main class="hero-section text-gray-800">
    <div class="container mx-auto px-4 text-center max-w-4xl">
      
      <h2 class="text-5xl font-extrabold text-blue-700 mb-4 animate-pulse">Reconnecting What's Lost, Within Campus.</h2>
      <p class="text-xl text-gray-600 mb-12">
        SGGS's dedicated portal to report, track, and recover lost and found items quickly and efficiently.
      </p>

      <div class="flex justify-center gap-16 mb-16">
          <div class="p-6 rounded-xl bg-white shadow-2xl border-b-4 border-yellow-500 transform hover:scale-105 transition duration-300">
              <p class="text-6xl font-extrabold text-yellow-600"><?php echo htmlspecialchars($total_users); ?></p>
              <p class="text-xl font-semibold mt-2 text-gray-700">Active Users</p>
          </div>
          <div class="p-6 rounded-xl bg-white shadow-2xl border-b-4 border-green-500 transform hover:scale-105 transition duration-300">
              <p class="text-6xl font-extrabold text-green-600"><?php echo htmlspecialchars($resolved_cases); ?></p>
              <p class="text-xl font-semibold mt-2 text-gray-700">Cases Resolved</p>
          </div>
      </div>

      <h3 class="text-3xl font-bold text-gray-800 mb-8 border-b pb-2">How It Works</h3>
      <div class="grid md:grid-cols-3 gap-8">
        <div class="p-6 bg-white rounded-lg shadow-lg border border-red-200">
            <div class="text-4xl mb-3 text-red-600">🔴</div>
            <h4 class="text-xl font-bold mb-2">Lost? Report It.</h4>
            <p class="text-gray-600">Quickly post details and a photo of the item you misplaced.</p>
        </div>
        <div class="p-6 bg-white rounded-lg shadow-lg border border-green-200">
            <div class="text-4xl mb-3 text-green-600">🟢</div>
            <h4 class="text-xl font-bold mb-2">Found? Log It.</h4>
            <p class="text-gray-600">Help a classmate out by reporting what you found.</p>
        </div>
        <div class="p-6 bg-white rounded-lg shadow-lg border border-blue-200">
            <div class="text-4xl mb-3 text-blue-600">✨</div>
            <h4 class="text-xl font-bold mb-2">Get Notified.</h4>
            <p class="text-gray-600">Our system automatically checks for potential matches.</p>
        </div>
      </div>

      <div class="mt-16">
          <a href="login.php" class="text-lg bg-blue-600 text-white px-8 py-3 rounded-full font-bold hover:bg-blue-700 transition shadow-xl">
              Get Started Now!
          </a>
      </div>

    </div>
  </main>

  <footer class="gradient-bg text-white py-4 text-center">
    <p>&copy; 2025 SGGS Lost & Found. Powered by Campus Connect.</p>
  </footer>

</body>
</html>