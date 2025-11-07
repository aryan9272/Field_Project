<?php
// D:\Xampp\htdocs\FP\get_stats.php
// This file assumes 'db_connect.php' has already been included and $pdo is available.

// Check if $pdo (PDO connection object) is defined, otherwise fall back to a safe state.
if (!isset($pdo)) {
    // If $pdo is not set, include db_connect.php to establish the connection
    // This makes the stats file self-sufficient if used directly or if db_connect.php wasn't loaded.
    // If db_connect.php fails, it will die() with an error message.
    include 'db_connect.php'; 
}

// Initialize variables
$total_users = 0;
$resolved_cases = 0;

try {
    // Fetch total users (Using PDO)
    $users_sql = "SELECT COUNT(username) AS total_users FROM users";
    $stmt = $pdo->query($users_sql);
    $user_data = $stmt->fetch();
    if ($user_data) {
        $total_users = $user_data['total_users'];
    }

    // Fetch resolved cases (Using PDO)
    $resolved_sql = "SELECT COUNT(id) AS resolved_cases FROM items WHERE status = 'Resolved'";
    $stmt = $pdo->query($resolved_sql);
    $resolved_data = $stmt->fetch();
    if ($resolved_data) {
        $resolved_cases = $resolved_data['resolved_cases'];
    }
} catch (\PDOException $e) {
    // Log the error but continue with 0 for display
    error_log("Stats Fetch Error: " . $e->getMessage());
    // $total_users and $resolved_cases remain 0
}
?>