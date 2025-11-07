<?php
// api.php - Backend API Handler (FINAL VERSION)
session_start();
header('Content-Type: application/json');

// --- 1. CONFIGURATION & SETUP ---
// ASSUMPTION: 'db_connect.php' exists and defines a PDO object named $pdo
include 'db_connect.php'; 

/**
 * Sends an error response and terminates the script.
 * @param string $message The error message.
 * @param int $code The HTTP response code.
 */
function exit_with_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

/**
 * Robustly retrieves POST data, supporting both raw JSON input (fetch/API calls) 
 * and standard form-urlencoded data ($_POST).
 * @return array The decoded POST data.
 */
function get_post_data() {
    // Check for raw JSON input by inspecting the Content-Type header
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        // Return empty array if decoding fails
        return $data ?: []; 
    }
    // Return standard POST data for form submissions
    return $_POST;
}

// --- 2. AUTHENTICATION GATE ---
$action = $_GET['action'] ?? null; 

// Define actions that do NOT require a user session (public access)
$public_actions = ['login', 'signup', 'logout', 'get_all_items', 'get_item_details'];

// Check if user is logged in OR if the action is public
if (!isset($_SESSION['username']) && !in_array($action, $public_actions)) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

$current_user = $_SESSION['username'] ?? null;
$current_user_email = $_SESSION['email'] ?? null;
$user_role = $_SESSION['role'] ?? 'user';
$is_admin = ($user_role === 'admin');


// --- 3. ACTION ROUTER ---
switch ($action) {
    
    // =========================================================================
    // ACTION 0.1: SIGNUP (PUBLIC ACCESS) 
    // =========================================================================
    case 'signup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit_with_error('Invalid request method.', 405); }

        $data = get_post_data();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            exit_with_error('Missing username, email, or password.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { exit_with_error('Invalid email format.'); }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check for existing user/email
            $check_sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$username, $email]);

            if ($check_stmt->fetchColumn() > 0) {
                exit_with_error('Username or email already exists. Please use a different one.', 409); 
            }

            // Insert new user with default 'user' role
            $insert_sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([$username, $email, $password_hash]);

            echo json_encode(['success' => true, 'message' => 'Registration successful. You can now log in.']);

        } catch (PDOException $e) {
            error_log("Signup Error: " . $e->getMessage());
            exit_with_error('Database error during registration.', 500);
        }
        break;


    // =========================================================================
    // ACTION 0.2: LOGIN (PUBLIC ACCESS) - Uses flexible identifier (username or email)
    // =========================================================================
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit_with_error('Invalid request method.', 405); }

        $data = get_post_data();
        
        $identifier = $data['identifier'] ?? ''; // Can be username or email
        $password = $data['password'] ?? '';
        
        if (empty($identifier) || empty($password)) {
            exit_with_error('Missing login identifier or password.');
        }

        try {
            // Determine if identifier is an email or username
            $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            
            $sql = "SELECT username, password_hash, role, email FROM users WHERE $field = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                exit_with_error('Invalid username/email or password.', 401);
            }

            // Set Session Variables on successful login
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            echo json_encode(['success' => true, 'message' => 'Login successful.', 'role' => $user['role']]);

        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            exit_with_error('Database error during login.', 500);
        }
        break;

    // =========================================================================
    // ACTION 1: REPORT LOST/FOUND ITEM (POST) - WITH NOTIFICATION LOGIC
    // =========================================================================
    case 'report':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit_with_error('Invalid request method.', 405); }

        // Basic validation for form data 
        $required_fields = ['name', 'type', 'category', 'location', 'description', 'contact'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                exit_with_error("Missing required field: $field.");
            }
        }

        $item_type = $_POST['type'];
        $item_status = 'Active'; 
        $image_url = null;

        // Prepare variables for INSERT with explicit NULL handling for optional/nullable fields
        $item_name = trim($_POST['name']);
        $item_category = trim($_POST['category']);
        $item_location = trim($_POST['location']);
        $item_description = trim($_POST['description']);
        // Convert empty string/missing data for nullable fields to actual NULL
        $item_color = !empty($_POST['color']) ? trim($_POST['color']) : null;
        $item_date_time = !empty($_POST['event_date_time']) ? $_POST['event_date_time'] : null;
        $item_contact = trim($_POST['contact']);


        // Image Upload Handling
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
             $target_dir = "uploads/"; 
             // Ensure the uploads directory exists
             if (!is_dir($target_dir)) { 
                 // Note: If this fails, the error_log message will be useful.
                 if (!mkdir($target_dir, 0777, true)) {
                     error_log("Failed to create uploads directory.");
                 }
             } 
             $image_file_type = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)); 
             $new_file_name = uniqid('img_') . '.' . $image_file_type; 
             $target_file = $target_dir . $new_file_name; 
             
             // Simple size check (2MB limit)
             if ($_FILES['image']['size'] > 2000000) { exit_with_error("File is too large. Max 2MB allowed."); } 
             
             // Move the uploaded file
             if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                 $image_url = $target_file; 
             } else {
                 // Log a non-database error here if file moving fails
                 error_log("Failed to move uploaded image to $target_file. Possible permissions issue."); 
             }
        }

        try {
            // --- 1. INSERT NEW ITEM ---
            $sql = "INSERT INTO items (type, name, category, location, color, description, event_date_time, contact, reported_by, image_url, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $item_type,
                $item_name,
                $item_category,
                $item_location,
                $item_color, 
                $item_description,
                $item_date_time,
                $item_contact,
                $current_user,
                $image_url,
                $item_status
            ]);
            
            $new_item_id = $pdo->lastInsertId();

            // --- 2. NOTIFICATION MATCHING LOGIC ---
            if ($new_item_id) {
                $opposite_type = ($item_type === 'lost') ? 'found' : 'lost';
                
                // Simple matching: find ACTIVE items of opposite type with the same name
                $match_sql = "SELECT reported_by, id, type FROM items 
                              WHERE type = ? AND name = ? AND status = 'Active'";
                
                $match_stmt = $pdo->prepare($match_sql);
                $match_stmt->execute([$opposite_type, $item_name]);

                $matches = $match_stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($matches) > 0) {
                    $new_item_reporter = $current_user;
                    $notification_stmt = $pdo->prepare("INSERT INTO notifications (recipient_username, message, item_id) VALUES (?, ?, ?)");

                    // A. Notify the user who just submitted the new report
                    $new_report_message = "Potential match found for your " . ucfirst($item_type) . " item ('$item_name'). Click to view details.";
                    $notification_stmt->execute([$new_item_reporter, $new_report_message, $new_item_id]);

                    // B. Notify the reporter(s) of the matching existing item(s)
                    foreach ($matches as $match) {
                        $match_reporter = $match['reported_by'];
                        $match_item_id = $match['id'];
                        $match_item_type = $match['type']; 
                        
                        $match_message = "A new " . ucfirst($item_type) . " item was reported that may match your " . ucfirst($match_item_type) . " item ('$item_name'). Click to view details.";
                        
                        if ($match_reporter !== $new_item_reporter) { 
                            $notification_stmt->execute([$match_reporter, $match_message, $match_item_id]);
                        }
                    }
                }
            }
            // --- END: NOTIFICATION MATCHING LOGIC ---

            echo json_encode(['success' => true, 'message' => ucfirst($item_type) . ' item reported successfully.']);

        } catch (PDOException $e) {
            // CRITICAL: Log the specific error for server-side debugging
            error_log("Report Insertion Error: " . $e->getMessage() . 
                      " | User: " . $current_user . 
                      " | Data: " . print_r($_POST, true)); 
            exit_with_error('Database error during report submission.', 500);
        }
        break;
    
    // =========================================================================
    // ACTION 2: GET ALL ITEMS (GET) - Public access for main grid
    // =========================================================================
    case 'get_all_items':
        try {
            // Select all items, ordered by reported_at descending
            $sql = "SELECT id, type, name, category, location, image_url, status, reported_at, description FROM items ORDER BY reported_at DESC";
            $stmt = $pdo->query($sql);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as &$item) {
                // Provide a placeholder image if image_url is missing
                if (empty($item['image_url'])) { 
                    $item['image_url'] = 'static/images/placeholder.jpg'; // Ensure this path is correct
                } 
                // Format the date for display
                $item['reported_at_formatted'] = date('M j, Y', strtotime($item['reported_at']));
                // Truncate description for card view
                $item['description_short'] = strlen($item['description']) > 100 ? substr($item['description'], 0, 100) . '...' : $item['description'];
            }
            unset($item); // Break reference

            echo json_encode($items);

        } catch (PDOException $e) {
            error_log("All Items Fetch Error: " . $e->getMessage());
            exit_with_error('Database error fetching items.', 500);
        }
        break;


    // =========================================================================
    // ACTION 3: GET ITEM DETAILS (GET) 
    // =========================================================================
    case 'get_item_details':
        $item_id = $_GET['id'] ?? null;
        if (!$item_id || !is_numeric($item_id)) { exit_with_error('Invalid item ID provided.'); }
        try {
            $sql = "SELECT id, type, name, category, contact, image_url, reported_at, status, description, location, color, event_date_time, reported_by FROM items WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) { 
                if (empty($item['image_url'])) { $item['image_url'] = 'static/images/placeholder.jpg'; } 
                // Format date/time for detail view
                $item['reported_at_formatted'] = date('M j, Y H:i', strtotime($item['reported_at']));
                if ($item['event_date_time']) {
                    $item['event_date_time_formatted'] = date('M j, Y H:i', strtotime($item['event_date_time']));
                } else {
                    $item['event_date_time_formatted'] = 'N/A';
                }

                echo json_encode($item); 
            } else { exit_with_error('Item not found.', 404); } 

        } catch (PDOException $e) 
        {
            error_log("Item Detail Fetch Error: " . $e->getMessage());
            exit_with_error('Database error fetching item details.', 500);
        }
        break;
    
    // =========================================================================
    // ACTION 4: GET PROFILE STATS (GET)
    // =========================================================================
    case 'get_profile_stats':
        if (!$current_user) { exit_with_error('User session data missing.', 401); }

        try {
            // 1. Fetch user email (if not in session)
            if (empty($current_user_email)) {
                $email_sql = "SELECT email FROM users WHERE username = ?";
                $email_stmt = $pdo->prepare($email_sql);
                $email_stmt->execute([$current_user]);
                $user_data = $email_stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_data) {
                    $current_user_email = $user_data['email'];
                    $_SESSION['email'] = $current_user_email;
                }
            }
            if (empty($current_user_email)) { 
                 // This should not happen if user is logged in, but check for safety
                 exit_with_error('User email could not be found.', 404); 
            }

            // 2. Count total reports by the user
            $reports_sql = "SELECT COUNT(*) FROM items WHERE reported_by = ?";
            $reports_stmt = $pdo->prepare($reports_sql);
            $reports_stmt->execute([$current_user]);
            $total_reports = $reports_stmt->fetchColumn();

            // 3. Count resolved cases reported by the user
            $solved_sql = "SELECT COUNT(*) FROM items WHERE reported_by = ? AND status = 'Resolved'";
            $solved_stmt = $pdo->prepare($solved_sql);
            $solved_stmt->execute([$current_user]);
            $cases_solved = $solved_stmt->fetchColumn();

            echo json_encode([
                'email' => $current_user_email,
                'total_reports' => (int)$total_reports,
                'cases_solved' => (int)$cases_solved
            ]);

        } catch (PDOException $e) {
            error_log("Profile Stats Error: " . $e->getMessage());
            exit_with_error('Database error fetching profile stats.', 500);
        }
        break;
        
    // =========================================================================
    // ACTION 5: GET ALL USERS (GET) - Admin Access
    // =========================================================================
    case 'get_all_users':
        if (!$is_admin) { exit_with_error('Access denied. Administrator privileges required.', 403); }
        try {
            // Select all users, ordered by created_at ascending
            $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at ASC";
            $stmt = $pdo->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
        } catch (PDOException $e) {
            error_log("Get Users Error: " . $e->getMessage());
            exit_with_error('Database error fetching user list.', 500);
        }
        break;

    // =========================================================================
    // ACTION 6: ADMIN: UPDATE ITEM STATUS (POST)
    // =========================================================================
    case 'update_item_status':
        if (!$is_admin) { exit_with_error('Access denied. Administrator privileges required.', 403); }
        $data = get_post_data();
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$id || !in_array($status, ['Active', 'Resolved'])) {
            exit_with_error('Invalid ID or status provided. Status must be Active or Resolved.');
        }

        try {
            $sql = "UPDATE items SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status, $id]);

            if ($stmt->rowCount() > 0) {
                 echo json_encode(['success' => true, 'message' => "Item $id status updated to $status."]);
            } else {
                 exit_with_error('Item not found or status is already the same.', 404);
            }
        } catch (PDOException $e) {
            error_log("Status Update Error: " . $e->getMessage());
            exit_with_error('Database error updating item status.', 500);
        }
        break;

    // =========================================================================
    // ACTION 7: ADMIN: DELETE ITEM (POST)
    // =========================================================================
    case 'delete_item':
        if (!$is_admin) { exit_with_error('Access denied. Administrator privileges required.', 403); }
        $data = get_post_data();
        $id = $data['id'] ?? null;

        if (!$id) { exit_with_error('Invalid item ID provided for deletion.'); }

        try {
            // First, delete related notifications to avoid foreign key constraints (if any exist)
            $delete_notif_sql = "DELETE FROM notifications WHERE item_id = ?";
            $delete_notif_stmt = $pdo->prepare($delete_notif_sql);
            $delete_notif_stmt->execute([$id]);

            // Then, delete the item
            $sql = "DELETE FROM items WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                 echo json_encode(['success' => true, 'message' => "Item $id deleted successfully."]);
            } else {
                 exit_with_error('Item not found.', 404);
            }
        } catch (PDOException $e) {
            error_log("Item Deletion Error: " . $e->getMessage());
            exit_with_error('Database error deleting item.', 500);
        }
        break;
        
    // =========================================================================
    // ACTION 8: ADMIN: UPDATE USER ROLE (POST)
    // =========================================================================
    case 'update_user_role':
        if (!$is_admin) { exit_with_error('Access denied. Administrator privileges required.', 403); }
        $data = get_post_data();
        $username = $data['username'] ?? null;
        $role = $data['role'] ?? null;

        if (!$username || !in_array($role, ['user', 'admin'])) {
            exit_with_error('Invalid username or role provided. Role must be user or admin.');
        }
        // Prevent admin from changing their own role 
        if ($username === $current_user) {
             exit_with_error('Cannot change the role of the current user.', 403);
        }

        try {
            $sql = "UPDATE users SET role = ? WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$role, $username]);

            if ($stmt->rowCount() > 0) {
                 echo json_encode(['success' => true, 'message' => "User $username role updated to $role."]);
            } else {
                 exit_with_error('User not found.', 404);
            }
        } catch (PDOException $e) {
            error_log("User Role Update Error: " . $e->getMessage());
            exit_with_error('Database error updating user role.', 500);
        }
        break;
        
    // =========================================================================
    // ACTION 9: ADMIN: DELETE USER (POST)
    // =========================================================================
    case 'delete_user':
        if (!$is_admin) { exit_with_error('Access denied. Administrator privileges required.', 403); }
        $data = get_post_data();
        $username = $data['username'] ?? null;

        if (!$username) { exit_with_error('Invalid username provided for deletion.'); }
         // Prevent admin from deleting themselves
        if ($username === $current_user) {
             exit_with_error('Cannot delete the currently logged-in administrator.', 403);
        }

        try {
            // The 'users' table has foreign keys in 'items' and 'notifications' with ON DELETE CASCADE,
            // so deleting the user should automatically clean up their related reports and notifications.
            $sql = "DELETE FROM users WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                 echo json_encode(['success' => true, 'message' => "User $username deleted successfully."]);
            } else {
                 exit_with_error('User not found.', 404);
            }
        } catch (PDOException $e) {
            error_log("User Deletion Error: " . $e->getMessage());
            exit_with_error('Database error deleting user.', 500);
        }
        break;


    // =========================================================================
    // ACTION 10: MARK NOTIFICATIONS READ (POST)
    // =========================================================================
    case 'mark_notifications_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit_with_error('Invalid request method.', 405); }
        try {
            $sql = "UPDATE notifications SET is_read = 1 WHERE recipient_username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$current_user]);
            echo json_encode(['success' => true, 'message' => 'Notifications marked as read.']);
        } catch (PDOException $e) {
            error_log("Mark Read Error: " . $e->getMessage());
            exit_with_error('Database error updating notifications.', 500);
        }
        break;
        
    // =========================================================================
    // ACTION 11: LOGOUT (POST)
    // =========================================================================
    case 'logout':
        // Session actions are handled by PHP
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
        break;

    // =========================================================================
    // DEFAULT/INVALID ACTION
    // =========================================================================
    default:
        exit_with_error('Invalid API action specified.', 400);
        break;
}
?>
