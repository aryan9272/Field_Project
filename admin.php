<?php
// D:\Xampp\htdocs\FP\admin.php
session_start();

// --- Security Check: Redirect if not logged in or not an admin ---
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: /FP/index.php"); // Send non-admins back to the main page
    exit();
}

$current_user = $_SESSION['username'];

// Include the PDO database connection
include 'db_connect.php'; 

// Fetch all item data for the admin table view using PDO
$items = [];
try {
    // Fetch all necessary details, ordered by reported_at (newest first)
    $sql = "SELECT id, type, name, category, location, reported_at, status, reported_by FROM items ORDER BY reported_at DESC";
    $result = $pdo->query($sql);
    $items = $result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin Item Fetch Error: " . $e->getMessage());
    $items = [];
}

// Fetch user data for basic management (optional, but good practice)
$users = [];
try {
    $sql = "SELECT username, role, email FROM users ORDER BY username ASC";
    $result = $pdo->query($sql);
    $users = $result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin User Fetch Error: " . $e->getMessage());
    $users = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="../Frontend/L&F.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Lost & Found</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
    }
    .admin-tab-active {
        background-color: #ffffff;
        color: #3b82f6; 
        border-color: #3b82f6;
    }
    .modal { display: none; }
    .modal.active { 
        display: flex; 
        position: fixed;
        top: 0;
        left: 0;
        z-index: 50;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    .modal-content-scroll { max-height: 80vh; overflow-y: auto; }
</style>
</head>
<body class="bg-gray-100 font-sans">

<header class="gradient-bg text-white shadow-lg z-40">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Admin Dashboard</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm">Logged in as: <?php echo htmlspecialchars($current_user); ?> (Admin)</span>
            <a href="index.php" class="text-indigo-200 hover:text-white transition">← Back to Portal</a>
        </div>
    </div>
</header>

<main class="container mx-auto px-4 py-8">
    
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Dashboard Controls</h2>
        <div class="flex space-x-4 border-b pb-4 mb-4">
            <button id="itemsTab" onclick="showTab('itemManagement')" class="px-4 py-2 font-semibold text-gray-700 border-b-2 border-transparent hover:border-blue-500 transition admin-tab-active">
                Reported Items (<?php echo count($items); ?>)
            </button>
            <button id="usersTab" onclick="showTab('userManagement')" class="px-4 py-2 font-semibold text-gray-700 border-b-2 border-transparent hover:border-blue-500 transition">
                User Accounts (<?php echo count($users); ?>)
            </button>
        </div>

        <div id="itemManagement" class="tab-content">
            <h3 class="text-xl font-semibold mb-4">Item Management</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Item Name</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Location</th>
                            <th class="py-3 px-4">Reported By</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemTableBody" class="divide-y divide-gray-200">
                        <?php if (empty($items)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-gray-500">No items have been reported yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($items as $item): 
                            $type_bg = $item['type'] === 'lost' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                            $status_color = $item['status'] === 'Active' ? 'bg-blue-500' : 'bg-gray-500';
                        ?>
                        <tr data-id="<?php echo $item['id']; ?>" class="hover:bg-gray-50 item-row">
                            <td class="py-3 px-4 text-sm font-semibold"><?php echo $item['id']; ?></td>
                            <td class="py-3 px-4"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $type_bg; ?>"><?php echo ucfirst($item['type']); ?></span></td>
                            <td class="py-3 px-4 text-sm font-medium text-gray-900 cursor-pointer hover:text-blue-600" onclick="showItemDetails(<?php echo $item['id']; ?>)">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?php echo htmlspecialchars($item['category']); ?></td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?php echo htmlspecialchars($item['location']); ?></td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?php echo htmlspecialchars($item['reported_by']); ?></td>
                            <td class="py-3 px-4"><span class="px-2 py-0.5 text-xs font-semibold text-white rounded-full <?php echo $status_color; ?> status-badge"><?php echo htmlspecialchars($item['status']); ?></span></td>
                            <td class="py-3 px-4 text-center space-x-2">
                                <?php if ($item['status'] === 'Active'): ?>
                                    <button onclick="updateItemStatus(<?php echo $item['id']; ?>, 'Resolved', this)" class="resolve-btn bg-green-500 hover:bg-green-600 text-white text-xs font-bold py-1 px-3 rounded transition">Resolve</button>
                                <?php endif; ?>
                                <button onclick="deleteItem(<?php echo $item['id']; ?>, this)" class="delete-btn bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-3 rounded transition">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="userManagement" class="tab-content hidden">
            <h3 class="text-xl font-semibold mb-4">User Management</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead>
                        <tr class="bg-gray-50 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Username</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if (empty($users)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-gray-500">No user accounts found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="py-3 px-4 text-sm font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="py-3 px-4 text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3 px-4 text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($user['role'] === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($user['username'] !== $current_user): // Prevent admin from changing their own role/deleting themselves ?>
                                    <button onclick="toggleUserRole('<?php echo $user['username']; ?>', '<?php echo $user['role']; ?>')" 
                                            class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded transition">
                                        <?php echo ($user['role'] === 'admin' ? 'Demote' : 'Promote'); ?>
                                    </button>
                                    <button onclick="deleteUser('<?php echo $user['username']; ?>')" 
                                            class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-3 rounded transition">
                                        Delete
                                    </button>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</main>

<div id="itemDetailModal" class="modal fixed inset-0 z-50">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl relative m-4 modal-content-scroll">
        <div id="itemDetailContent" class="p-6">
            </div>
        <button onclick="closeModal('itemDetailModal')" class="absolute top-2 right-4 text-gray-500 hover:text-gray-800 text-3xl leading-none">&times;</button>
    </div>
</div>

<div id="statusPopup" class="fixed bottom-5 right-5 z-50 p-4 rounded-lg text-white shadow-xl hidden opacity-0 transition-opacity duration-300"></div>


<script>
    // --- Helper Functions ---
    function showPopup(message, isSuccess = true) {
        const popup = document.getElementById('statusPopup');
        popup.textContent = message;
        popup.classList.remove('hidden', 'bg-green-600', 'bg-red-600', 'opacity-0'); 
        popup.classList.add('opacity-100', isSuccess ? 'bg-green-600' : 'bg-red-600');

        setTimeout(() => {
            popup.classList.remove('opacity-100');
            popup.classList.add('opacity-0');
            setTimeout(() => { popup.classList.add('hidden'); }, 300); 
        }, 4000);
    }
    
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    function showTab(tabId) {
        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        // Remove active class from all buttons
        document.querySelectorAll('button[id$="Tab"]').forEach(btn => {
            btn.classList.remove('admin-tab-active');
        });

        // Show the selected tab content
        document.getElementById(tabId).classList.remove('hidden');
        // Set the active class on the button
        document.getElementById(tabId.replace('Management', 'Tab')).classList.add('admin-tab-active');
    }

    // --- Item Detail Logic (Copied from index.php) ---
    async function showItemDetails(id) {
        const itemDetailModal = document.getElementById('itemDetailModal');
        const itemDetailContent = document.getElementById('itemDetailContent');

        openModal('itemDetailModal');
        itemDetailContent.innerHTML = '<div class="text-center p-8 text-blue-500 font-semibold">Loading item details...</div>';

        try {
            // Reuses the existing 'get_item_details' action from api.php
            const response = await fetch(`api.php?action=get_item_details&id=${id}`);
            const item = await response.json();

            if (response.ok && item.name) {
                const isLost = item.type === 'lost';
                const mainColor = isLost ? 'red' : 'green'; 
                const statusColor = item.status === 'Active' ? 'blue' : 'gray';
                const reportedDateTime = item.reported_at ? new Date(item.reported_at).toLocaleDateString() : 'N/A';
                const eventDateTime = item.event_date_time ? new Date(item.event_date_time).toLocaleString() : 'N/A'; 
                const imageUrl = item.image_url || 'static/images/placeholder.jpg';

                itemDetailContent.innerHTML = `
                    <div class="relative">
                        <div class="flex items-center space-x-4 border-b pb-4 mb-4">
                            <span class="text-4xl text-${mainColor}-600">${isLost ? 'LOST 😔' : 'FOUND 🙌'}</span>
                            <h2 class="text-3xl font-bold text-gray-800">${item.name}</h2>
                        </div>

                        <div class="md:flex md:space-x-6">
                            <div class="md:w-1/2 mb-4 md:mb-0">
                                <img src="${imageUrl}" alt="${item.name}" class="w-full h-auto object-cover rounded-lg shadow-lg border-2 border-${mainColor}-300">
                            </div>
                            
                            <div class="md:w-1/2 space-y-3">
                                <p class="text-sm">
                                    <span class="font-semibold text-gray-700">Category:</span> 
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-full">${item.category}</span>
                                </p>
                                
                                <p class="text-sm">
                                    <span class="font-semibold text-gray-700">Status:</span> 
                                    <span class="px-3 py-1 bg-${statusColor}-100 text-${statusColor}-700 text-sm font-medium rounded-full">${item.status}</span>
                                </p>

                                <div class="border-t pt-3">
                                    <p class="font-semibold text-gray-700 mb-1">Details:</p>
                                    <ul class="text-sm space-y-1 text-gray-600">
                                        <li><span class="font-medium">Reported:</span> ${reportedDateTime}</li>
                                        <li><span class="font-medium">${isLost ? 'Lost Date/Time' : 'Found Date/Time'}:</span> ${eventDateTime}</li>
                                        <li><span class="font-medium">Location ${isLost ? 'Lost' : 'Found'}:</span> ${item.location}</li>
                                        ${item.color ? `<li><span class="font-medium">Color:</span> ${item.color}</li>` : ''}
                                    </ul>
                                </div>
                                
                                <div class="border-t pt-3">
                                    <p class="font-semibold text-gray-700 mb-1">Contact Info (Reporter):</p>
                                    <p class="text-sm text-blue-600 font-medium">${item.contact}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 border-t pt-4">
                             <p class="text-lg font-semibold text-gray-800 mb-2">Full Description:</p>
                             <p class="text-gray-600 whitespace-pre-wrap">${item.description}</p>
                        </div>

                    </div>
                `;
            } else {
                itemDetailContent.innerHTML = '<div class="text-center p-8 text-red-500 font-semibold">Item not found or failed to load details.</div>';
            }
        } catch (error) {
            console.error("Error fetching item details:", error);
            itemDetailContent.innerHTML = '<div class="text-center p-8 text-red-500 font-semibold">Network error loading item details.</div>';
        }
    }


    // --- Admin Action Handlers ---
    async function updateItemStatus(id, newStatus, button) {
        if (!confirm(`Are you sure you want to mark Item ID ${id} as ${newStatus}?`)) return;
        
        try {
            const response = await fetch('api.php?action=update_item_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: newStatus })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showPopup(`Item ${id} status updated to ${newStatus}.`, true);
                
                // Update UI without full page reload
                const row = button.closest('.item-row');
                const statusBadge = row.querySelector('.status-badge');
                
                statusBadge.textContent = newStatus;
                statusBadge.classList.remove('bg-blue-500');
                statusBadge.classList.add('bg-gray-500');

                // Hide the Resolve button
                button.remove();

            } else {
                showPopup(`Failed to update status: ${result.error || 'Unknown error.'}`, false);
            }
        } catch (error) {
            console.error('Status update error:', error);
            showPopup('Network error during status update.', false);
        }
    }

    async function deleteItem(id, button) {
        if (!confirm(`WARNING: Are you sure you want to PERMANENTLY delete Item ID ${id}? This action cannot be undone.`)) return;
        
        try {
            const response = await fetch('api.php?action=delete_item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showPopup(`Item ${id} permanently deleted.`, true);
                
                // Remove the row from the table
                button.closest('.item-row').remove();
            } else {
                showPopup(`Failed to delete item: ${result.error || 'Unknown error.'}`, false);
            }
        } catch (error) {
            console.error('Delete item error:', error);
            showPopup('Network error during item deletion.', false);
        }
    }
    
    async function toggleUserRole(username, currentRole) {
         const newRole = currentRole === 'admin' ? 'user' : 'admin';
         if (!confirm(`Are you sure you want to change the role of ${username} to ${newRole}?`)) return;
         
         try {
             const response = await fetch('api.php?action=update_user_role', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify({ username: username, role: newRole })
             });
             const result = await response.json();

             if (response.ok && result.success) {
                 showPopup(`User ${username} promoted/demoted successfully!`, true);
                 // Simple page reload to update user table is easiest here
                 setTimeout(() => window.location.reload(), 500); 
             } else {
                 showPopup(`Failed to update user role: ${result.error || 'Unknown error.'}`, false);
             }
         } catch (error) {
             console.error('User role update error:', error);
             showPopup('Network error during user role update.', false);
         }
    }

    async function deleteUser(username) {
        if (!confirm(`WARNING: Are you sure you want to PERMANENTLY delete the user account: ${username}?`)) return;
        
        try {
            const response = await fetch('api.php?action=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: username })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showPopup(`User ${username} permanently deleted.`, true);
                setTimeout(() => window.location.reload(), 500); 
            } else {
                showPopup(`Failed to delete user: ${result.error || 'Unknown error.'}`, false);
            }
        } catch (error) {
            console.error('Delete user error:', error);
            showPopup('Network error during user deletion.', false);
        }
    }
</script>

</body>
</html>