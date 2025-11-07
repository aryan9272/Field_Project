<?php
// Include the database connection setup
include 'db_connect.php'; 

// 1. Get token from URL
$token = $_GET['token'] ?? '';
$message = '';
$user = null; // Initialize user variable
$error_type = 'error'; // For styling the message

if (empty($token)) {
    $message = "Invalid or missing token.";
} else {
    // 2. Look up the user by token and check expiry
    // Use prepared statement to prevent SQL Injection
    $stmt = $pdo->prepare("SELECT id, username, token_expiry FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "Invalid token. The link may have been used or is incorrect.";
    } elseif (strtotime($user['token_expiry']) < time()) {
        $message = "Token expired. Please request a new password reset link.";
        $user = null; // Token is expired, so clear user
    }
}

// 3. Handle new password submission (Only runs if a valid user/token exists)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || $new_password !== $confirm_password) {
        $message = "Passwords do not match or are empty.";
    } elseif (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        // Securely hash the new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password and clear the token/expiry fields
        $update_sql = "UPDATE users SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);

        if ($update_stmt->execute([$password_hash, $user['id']])) {
            $message = "Your password has been successfully reset. You can now log in.";
            $error_type = 'success';
            $user = null; // Prevent the form from showing again
        } else {
            $message = "An error occurred while updating your password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" type="image/png" href="static/images/L&F.jpg" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md">
        <div class="bg-white p-8 rounded-xl shadow-2xl border border-gray-100">
            <div class="text-center mb-6">
                <a href="home.php" class="flex items-center justify-center mb-4">
                    <img class="h-10 w-auto inline-block mr-2" src="static/images/L&F.jpg" alt="Logo">
                    <span class="text-2xl font-bold text-gray-800">Password Reset</span>
                </a>
                <h2 class="text-xl font-semibold text-gray-900">Set a New Password</h2>
            </div>

            <?php if (!empty($message)): ?>
                <p class="text-center mb-6 p-3 rounded-lg font-medium 
                    <?php echo $error_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> 
                    <?php echo $error_type === 'success' ? 'border-green-300' : 'border-red-300'; ?> border-l-4">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <?php if ($user && empty($message)): // Only show the form if a valid user/token is found and no message is set ?>
                <form action="reset.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="password" name="password" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" minlength="8">
                    </div>
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" minlength="8">
                    </div>
                    <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                        Set New Password
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    ← Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>