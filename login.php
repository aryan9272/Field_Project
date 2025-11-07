<?php
// D:\Xampp\htdocs\FP\login.php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: /FP/index.php"); 
    exit();
}
// INCLUDE THE STATS FILE
// FIX: Using db_connect.php (PDO) for consistency
include 'db_connect.php';
include 'get_stats.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Campus Lost & Found - Login</title>
  <link rel="icon" type="image/png" href="static/images/L&F.jpg" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="static/css/login.css" /> 
  <style>
      .input-group { position: relative; }
      .input-field { width: 100%; padding: 10px 10px 10px 0; font-size: 16px; border: none; border-bottom: 2px solid #ccc; outline: none; transition: border-bottom-color 0.3s; background-color: transparent; }
      .input-field:focus { border-bottom-color: #3b82f6; }
      .input-label { position: absolute; top: 10px; left: 0; font-size: 16px; color: #999; pointer-events: none; transition: 0.3s ease all; }
      .input-field:focus ~ .input-label, .input-field:not(:placeholder-shown) ~ .input-label { top: -14px; font-size: 12px; color: #3b82f6; }
      .btn-primary { width: 100%; padding: 10px; background-color: #3b82f6; color: white; border-radius: 6px; font-weight: 600; transition: background-color 0.3s; }
      .btn-primary:hover { background-color: #2563eb; }
      .heading-accent { border-bottom: 3px solid #f97316; display: inline-block; padding-bottom: 2px; }
      #loginMessage { /* New style for error message */ color: red; text-align: center; margin-top: 10px; } 
  </style>
</head>
<body>
  <div class="min-h-screen flex flex-col md:flex-row">
    <div class="flex-1 flex flex-col justify-center items-center bg-gradient-to-br from-blue-500 to-indigo-600 text-white p-10">
      <div class="flex items-center space-x-4 mb-10">
        <img src="static/images/sggs.jpg" alt="SGGS Logo" class="w-20 h-20 rounded-full shadow-lg bg-white p-2" />
        <h1 class="text-4xl font-bold">SGGS Campus Connect</h1>
      </div>
      
      <div class="flex gap-8 mb-8 text-center">
          <div class="p-4 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm shadow-xl border border-white border-opacity-30">
              <p class="text-5xl font-extrabold text-yellow-300"><?php echo htmlspecialchars($total_users); ?></p>
              <p class="text-lg font-semibold mt-1">Total Users</p>
          </div>
          <div class="p-4 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm shadow-xl border border-white border-opacity-30">
              <p class="text-5xl font-extrabold text-green-300"><?php echo htmlspecialchars($resolved_cases); ?></p>
              <p class="text-lg font-semibold mt-1">Cases Resolved</p>
          </div>
      </div>
      
      <div class="flex space-x-8">
        <div class="bg-white rounded-2xl shadow-lg p-4">
          <img src="static/images/lost.jpg" alt="Lost Item" class="w-48 h-48 object-contain" />
          <p class="text-black mt-2 text-center">🔴 Someone lost it</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-4">
          <img src="static/images/found.jpg" alt="Found Item" class="w-48 h-48 object-contain" />
          <p class="text-black mt-2 text-center">🟢 Someone found it</p>
        </div>
      </div>
      <p class="mt-8 text-center font-semibold">Let’s reconnect them together ✨</p>
    </div>

    <div class="flex-1 flex justify-center items-center bg-gray-50 p-8">
      <div class="form-container animate-fade-in p-8 w-full max-w-md bg-white rounded-xl shadow-2xl text-gray-800">
        <h2 class="text-2xl font-bold mb-6 text-center heading-accent">Campus Lost & Found</h2>
        <p class="text-center text-gray-600 mb-6">Login to continue</p>

        <form id="loginForm" class="space-y-6">
          <div class="input-group">
            <input type="text" id="identifier" name="identifier" required class="input-field peer" placeholder=" " />
            <label for="identifier" class="input-label">Username or Email</label>
          </div>

          <div class="input-group">
            <input type="password" id="password" name="password" required class="input-field peer" placeholder=" " />
            <label for="password" class="input-label">Password</label>
          </div>

          <button type="submit" class="btn-primary">Login</button>
        </form>

        <p id="loginMessage"></p> <div class="mt-4 text-center">
          <p class="text-sm">
            Don’t have an account?
            <a href="signup.php" class="text-blue-600 hover:underline">Sign up</a>
          </p>
          <p class="text-xs mt-2">
            <a href="home.php" class="text-gray-500 hover:text-gray-700">← Back to Home</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("loginForm").addEventListener("submit", async function(e) {
      e.preventDefault(); 
      const identifier = document.getElementById("identifier").value; 
      const password = document.getElementById("password").value;
      const messageElement = document.getElementById("loginMessage");
      messageElement.textContent = "";

      try {
        // *** CRITICAL FIX: Changed URL to pass action as a query parameter ***
        const response = await fetch('/FP/api.php?action=login', { 
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          // Passed 'identifier' instead of 'username' to match front-end input name
          body: JSON.stringify({ identifier: identifier, password: password }) 
        });

        const result = await response.json(); // Wait for the JSON response

        if (response.ok) {
          window.location.href = "/FP/index.php"; 
        } else {
          // Display the specific error message from the API
          messageElement.textContent = result.error || "Login failed due to an unknown error.";
        }
      } catch (networkError) {
        messageElement.textContent = "NETWORK ERROR: Could not connect to the server. Check XAMPP status.";
      }
    });
  </script>
</body>
</html>