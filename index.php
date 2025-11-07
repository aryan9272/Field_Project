<?php
// D:\Xampp\htdocs\FP\index.php (Final Merged File)
session_start();

// --- 1. Session Check (Using $_SESSION['username'] for consistency with the requested configuration)
if (!isset($_SESSION['username'])) {
    header("Location: /FP/login.php"); 
    exit();
}

$current_user = $_SESSION['username'] ?? 'User'; 
$userRole = $_SESSION['role'] ?? 'user'; 

// Include the PDO database connection
// ASSUMPTION: 'db_connect.php' exists and defines a PDO object named $pdo
include 'db_connect.php'; 

// --- 2. Fetch UNREAD NOTIFICATIONS for the current user using PDO ---
$unread_notifications = [];
try {
    // CORRECTED: Added item_id to the SELECT list
    $notif_sql = "SELECT id, message, item_id FROM notifications WHERE recipient_username = ? AND is_read = 0 ORDER BY created_at DESC";
    $notif_stmt = $pdo->prepare($notif_sql); 
    $notif_stmt->execute([$current_user]); 
    $unread_notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    error_log("Notification Fetch Error in index.php: " . $e->getMessage());
}
// -------------------------------------------------------------------

// 3. Fetch all item data for the grid view using PDO
$items = [];
try {
    // Ensuring all necessary fields (location, description, color, image_url, contact, event_date_time) are fetched for the detailed forms/grid
    $sql = "SELECT id, type, name, category, contact, image_url, reported_at, status, description, location, color, event_date_time FROM items ORDER BY reported_at DESC";
    $result = $pdo->query($sql);
    $items = $result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Item Fetch Error in index.php: " . $e->getMessage());
    $items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="../Frontend/L&F.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Lost & Found</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* UI STYLES */
    .gradient-bg {
        background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
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
    }
    
    /* Specific style for the Profile Popup (Top-right position) */
    #profileModal.active {
        display: block; /* Use block to position correctly */
        pointer-events: none; /* Allows clicks to fall through to close other modals */
    }
    #profileModal .modal-content {
        position: absolute;
        top: 80px; /* Below the header */
        right: 20px;
        z-index: 60; /* Higher than other dropdowns/modals */
        pointer-events: auto; /* Allows interaction with modal content */
    }

    /* Tooltip */
    .tooltip { position: relative; display: inline-block; }
    .tooltip .tooltiptext {
        visibility: hidden;
        width: 160px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 6px 8px;
        position: absolute;
        bottom: 120%;
        left: 50%;
        transform: translateX(-50%);
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.9rem;
        z-index: 50;
    }
    .tooltip:hover .tooltiptext { visibility: visible; opacity: 1; }

    /* Highlight effect for search */
    .highlight {
        background-color: #fff3cd !important;
        transition: background-color 1s ease;
    }

    /* Popup messages */
    .popup {
        position: fixed;
        bottom: 20px; 
        right: 20px;
        background-color: white;
        border: 2px solid #000;
        padding: 20px 30px;
        border-radius: 10px;
        text-align: center;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: fadeInOut 4s forwards;
    }

    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(20px); }
        10% { opacity: 1; transform: translateY(0); }
        90% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0; transform: translateY(20px); }
    }
    
    .modal-content-scroll { max-height: 80vh; overflow-y: auto; }
    .item-card { transition: transform 0.2s, box-shadow 0.2s; }
    .item-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); cursor: pointer; }
    .item-card.highlight { border: 3px solid #3b82f6; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.5); }
</style>
</head>
<body class="bg-gray-50 font-sans">

<header class="gradient-bg text-white shadow-lg relative z-40">
    <div class="container mx-auto px-4 py-6 flex items-center gap-4 justify-between">
        <div class="flex items-center gap-4">
            <img src="static/images/sggs.jpg" alt="SGGS Logo" class="h-12 w-auto rounded bg-white p-2 shadow" /> 
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">Campus Lost & Found</h1>
        </div>
        
        <nav class="flex items-center space-x-4">
            <span class="text-white text-sm hidden sm:inline-block">Welcome, <?php echo htmlspecialchars($current_user); ?></span>
            
            <div class="relative">
                <button id="notificationBtn" class="p-2 text-white hover:text-indigo-200 relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <?php if (count($unread_notifications) > 0): ?>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"><?php echo count($unread_notifications); ?></span>
                    <?php endif; ?>
                </button>
                <div id="notificationDropdown" class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl z-20 border border-gray-200 hidden">
                    <div class="p-4 border-b"><h3 class="font-bold text-gray-800">Notifications</h3></div>
                    <div id="notificationContent" class="modal-content-scroll max-h-60">
                        <?php if (count($unread_notifications) > 0): ?>
                            <?php foreach ($unread_notifications as $notif): ?>
                                <div 
                                    class="p-3 border-b text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                    <?php if ($notif['item_id']): ?>
                                        onclick="showItemDetails(<?php echo htmlspecialchars($notif['item_id']); ?>); markNotificationsRead(); closeDropdown('notificationDropdown');"
                                    <?php endif; ?>
                                >
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </div>
                            <?php endforeach; ?>
                            <button id="markReadBtn" class="w-full text-center text-blue-600 hover:bg-gray-100 py-2 text-sm border-t">Mark All as Read</button>
                        <?php else: ?>
                            <div class="p-4 text-sm text-gray-500 text-center">No new notifications.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="relative">
                <button id="userMenuBtn" class="p-2 text-white hover:text-indigo-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </button>
                <div id="userMenuDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-20 hidden">
                    <a href="#" id="openProfileModal" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                    <?php if ($userRole === 'admin'): ?>
                           <a href="admin.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 font-semibold text-blue-600">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="#" id="logoutBtn" class="block px-4 py-2 text-red-600 hover:bg-red-50 border-t">Logout</a>
                </div>
            </div>
        </nav>
        
    </div>
</header>

<div class="bg-yellow-200 text-red-700 font-semibold py-2">
    <marquee behavior="scroll" direction="left" scrollamount="6">
        📢 State Report: Please submit lost and found items responsibly. Ensure accurate details for faster recovery!
    </marquee>
</div>

<main class="container mx-auto px-4 py-16 text-center">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800">Welcome to the Campus Lost & Found Portal</h2>
    <p class="text-gray-600 mb-12 max-w-xl mx-auto">Report your lost or found items here. Your reports will help us quickly reunite owners with their belongings.</p>

    <div class="mb-12 max-w-3xl mx-auto">
        <div class="flex flex-col sm:flex-row gap-3">
            <input id="searchInput" type="text" placeholder="🔍 Search item name or category..."
                class="flex-1 border border-gray-300 p-3 rounded-full shadow focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select id="categoryFilter" class="border border-gray-300 p-3 rounded-full shadow focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                <option value="Electronics">Electronics</option>
                <option value="ID/Wallet">ID/Wallet</option>
                <option value="Keys">Keys</option>
                <option value="Clothing">Clothing</option>
                <option value="Bags">Bags</option>
                <option value="Jewelry">Jewelry</option>
                <option value="Other">Other</option>
            </select>
            <select id="statusFilter" class="border border-gray-300 p-3 rounded-full shadow focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Resolved">Resolved</option>
            </select>
        </div>

        <div id="searchResults" class="absolute w-full max-w-3xl left-1/2 -translate-x-1/2 mt-1 hidden bg-white shadow rounded-lg p-2 text-left z-30"></div>
    </div>

    <div class="flex justify-center gap-12 mb-16">
        <div class="tooltip">
            <button id="openLostModal" onclick="openModal('lostModal')"
                class="w-36 h-36 rounded-full bg-red-600 text-white text-2xl font-bold flex items-center justify-center shadow-lg hover:bg-red-700 transition">
                Lost
            </button>
            <span class="tooltiptext">Report Lost Item</span>
        </div>

        <div class="tooltip">
            <button id="openFoundModal" onclick="openModal('foundModal')"
                class="w-36 h-36 rounded-full bg-green-600 text-white text-2xl font-bold flex items-center justify-center shadow-lg hover:bg-green-700 transition">
                Found
            </button>
            <span class="tooltiptext">Report Found Item</span>
        </div>
    </div>

    <section class="max-w-7xl mx-auto text-left" id="itemsSection">
        <h3 class="text-2xl font-bold mb-6 text-gray-800 text-center">📋 Recent Items Reported</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" id="itemGrid">
            
            <?php if (empty($items)): ?>
                <div id="noItemsMessage" class="lg:col-span-3 text-center py-10 bg-white rounded-xl shadow-lg">
                    <p class="text-xl text-gray-500">No lost or found items have been reported yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): 
                    $type_color = $item['type'] === 'lost' ? 'border-red-500' : 'border-green-500';
                    $status_color = $item['status'] === 'Active' ? 'bg-blue-500' : 'bg-gray-500';
                    $text_color = $item['type'] === 'lost' ? 'text-red-600' : 'text-green-600';
                    $image_url = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'static/images/placeholder.jpg';
                ?>
                <div id="item<?php echo $item['id']; ?>"
                    class="item-card bg-white p-5 rounded-lg shadow border-l-4 <?php echo $type_color; ?> relative overflow-hidden"
                    data-id="<?php echo $item['id']; ?>"
                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                    data-type="<?php echo htmlspecialchars($item['type']); ?>"
                    data-category="<?php echo htmlspecialchars($item['category']); ?>"
                    data-status="<?php echo htmlspecialchars($item['status']); ?>"
                    onclick="showItemDetails(<?php echo $item['id']; ?>)">

                    <div class="absolute top-2 right-2 flex space-x-2 z-10">
                         <span class="px-2 py-0.5 text-xs font-semibold text-white rounded-full <?php echo $status_color; ?>">
                             <?php echo htmlspecialchars($item['status']); ?>
                         </span>
                    </div>

                    <h4 class="font-semibold <?php echo $text_color; ?> text-lg"><?php echo ucfirst($item['type']) . ': ' . htmlspecialchars($item['name']); ?></h4>
                    <p class="text-gray-600 text-sm">📍 Location: <?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></p>
                    <p class="text-gray-600 text-sm">Category: <?php echo htmlspecialchars($item['category']); ?></p>
                    <p class="text-gray-600 text-sm">Reported: <?php echo date('M d, Y', strtotime($item['reported_at'])); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </section>
</main>

<div id="lostModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-lg relative m-4">
        <div class="flex justify-between items-center border-b p-4 bg-red-50">
             <h2 class="text-2xl font-bold text-red-600">Report Lost Item 😔</h2>
             <button onclick="closeModal('lostModal')" class="text-red-400 hover:text-red-600 text-3xl leading-none">&times;</button>
        </div>
        <form id="lostItemForm" class="p-6 space-y-4 modal-content-scroll">
            <input type="hidden" name="type" value="lost">
            <input type="hidden" name="reported_by" value="<?php echo htmlspecialchars($current_user); ?>">

            <input type="text" name="name" placeholder="Item Name *" required class="w-full border p-3 rounded-md">
            <select name="category" required class="w-full border p-3 rounded-md">
                <option value="">Select Category *</option>
                <option value="Electronics">Electronics</option>
                <option value="ID/Wallet">ID/Wallet</option>
                <option value="Keys">Keys</option>
                <option value="Clothing">Clothing</option>
                <option value="Bags">Bags</option>
                <option value="Jewelry">Jewelry</option>
                <option value="Other">Other</option>
            </select>
            <input type="text" name="location" placeholder="Lost Location (Be Specific) *" required class="w-full border p-3 rounded-md">
            <input type="text" name="color" placeholder="Color (optional)" class="w-full border p-3 rounded-md">
            <textarea name="description" placeholder="Brief Description (e.g., Brand, unique features) *" rows="3" required class="w-full border p-3 rounded-md"></textarea>
            <input type="datetime-local" name="event_date_time" placeholder="Approximate Date/Time of Loss" class="w-full border p-3 rounded-md">
            <label class="block text-sm font-medium text-gray-700">Upload Image (Optional, max 2MB)</label>
            <input type="file" name="image" accept="image/*" class="w-full border p-2 rounded-md">
            <input type="text" name="contact" placeholder="Your Contact Info (email/phone) *" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required class="w-full border p-3 rounded-md">
            <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-md hover:bg-red-700 font-semibold transition">Submit Lost Report</button>
        </form>
    </div>
</div>

<div id="foundModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-lg relative m-4">
        <div class="flex justify-between items-center border-b p-4 bg-green-50">
             <h2 class="text-2xl font-bold text-green-600">Report Found Item 🙌</h2>
             <button onclick="closeModal('foundModal')" class="text-green-400 hover:text-green-600 text-3xl leading-none">&times;</button>
        </div>
        <form id="foundItemForm" class="p-6 space-y-4 modal-content-scroll">
            <input type="hidden" name="type" value="found">
            <input type="hidden" name="reported_by" value="<?php echo htmlspecialchars($current_user); ?>">

            <input type="text" name="name" placeholder="Item Name *" required class="w-full border p-3 rounded-md">
            <select name="category" required class="w-full border p-3 rounded-md">
                <option value="">Select Category *</option>
                <option value="Electronics">Electronics</option>
                <option value="ID/Wallet">ID/Wallet</option>
                <option value="Keys">Keys</option>
                <option value="Clothing">Clothing</option>
                <option value="Bags">Bags</option>
                <option value="Jewelry">Jewelry</option>
                <option value="Other">Other</option>
            </select>
            <input type="text" name="location" placeholder="Found Location (Be Specific) *" required class="w-full border p-3 rounded-md">
            <input type="text" name="color" placeholder="Color (optional)" class="w-full border p-3 rounded-md">
            <textarea name="description" placeholder="Brief Description (e.g., Brand, unique features) *" rows="3" required class="w-full border p-3 rounded-md"></textarea>
            <input type="datetime-local" name="event_date_time" placeholder="Approximate Date/Time of Finding" class="w-full border p-3 rounded-md">
            <label class="block text-sm font-medium text-gray-700">Upload Image (Optional, max 2MB)</label>
            <input type="file" name="image" accept="image/*" class="w-full border p-2 rounded-md">
            <input type="text" name="contact" placeholder="Your Contact Info (email/phone) *" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required class="w-full border p-3 rounded-md">
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 font-semibold transition">Submit Found Report</button>
        </form>
    </div>
</div>

<div id="claimModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-lg relative m-4">
        <div class="flex justify-between items-center border-b p-4 bg-blue-50">
             <h2 class="text-2xl font-bold text-blue-600">Report Found Match! 🤝</h2>
             <button onclick="closeModal('claimModal')" class="text-blue-400 hover:text-blue-600 text-3xl leading-none">&times;</button>
        </div>
        <form id="claimItemForm" class="p-6 space-y-4 modal-content-scroll">
            <input type="hidden" name="itemId" id="claimItemId">
            <p class="text-gray-700">You are reporting that you have **found** the item: <span id="claimItemName" class="font-semibold text-blue-600"></span>.</p>
            
            <input type="text" name="contactInfo" placeholder="Your Contact Info (email/phone) *" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required class="w-full border p-3 rounded-md">
            
            <textarea name="message" placeholder="Optional: Provide details (e.g., where/when you have the item, best time to meet the owner)." rows="3" class="w-full border p-3 rounded-md"></textarea>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-semibold transition">Submit Claim/Match Report</button>
        </form>
    </div>
</div>
<div id="itemDetailModal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl relative m-4 modal-content-scroll">
        <div id="itemDetailContent" class="p-6">
            <div class="text-center p-8">Loading item details...</div>
        </div>
        <button onclick="closeModal('itemDetailModal')" class="absolute top-2 right-4 text-gray-500 hover:text-gray-800 text-3xl leading-none">&times;</button>
    </div>
</div>

<div id="statusPopup" class="fixed bottom-5 right-5 z-50 p-4 rounded-lg text-white shadow-xl hidden opacity-0 transition-opacity duration-300"></div>

<footer class="gradient-bg text-white py-6 mt-12 text-center">
    <p>&copy; 2025 Campus University. All rights reserved.</p>
</footer>

<div id="profileModal" class="modal fixed inset-0 h-full w-full">
    <div id="profileModalContent" class="modal-content bg-white w-full max-w-sm rounded-xl shadow-2xl relative">
        </div>
</div>

<script>
    // Global constants and variables
    const USER_ROLE = '<?php echo $userRole; ?>';
    const itemCards = Array.from(document.querySelectorAll('.item-card'));
    const searchInput = document.getElementById("searchInput");
    const categoryFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");
    const itemGrid = document.getElementById('itemGrid');
    const searchResults = document.getElementById("searchResults");
    const itemDetailModal = document.getElementById('itemDetailModal');
    const itemDetailContent = document.getElementById('itemDetailContent');


    // --- Helper Functions ---
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // NEW FUNCTION: Helper to close any dropdown by ID
    function closeDropdown(dropdownId) {
        document.getElementById(dropdownId)?.classList.add('hidden');
    }

    function showPopup(message, isSuccess = true) {
        const popup = document.getElementById('statusPopup');
        popup.textContent = message;
        // Adjusted colors to match the green/red gradient style better
        popup.classList.remove('hidden', 'bg-green-600', 'bg-red-600', 'opacity-0'); 
        popup.classList.add('opacity-100', isSuccess ? 'bg-green-600' : 'bg-red-600');

        setTimeout(() => {
            popup.classList.remove('opacity-100');
            popup.classList.add('opacity-0');
            setTimeout(() => { popup.classList.add('hidden'); }, 300); 
        }, 4000);
    }

    // NEW FUNCTION: Logic to mark all notifications as read
    async function markNotificationsRead() {
        try {
            const response = await fetch('api.php?action=mark_notifications_read', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({}) 
            });
            const result = await response.json();
            if (response.ok && result.success) {
                // Remove notification count indicator immediately
                const notifBtn = document.getElementById('notificationBtn');
                if (notifBtn) {
                    const countSpan = notifBtn.querySelector('span.bg-red-600');
                    if (countSpan) {
                        countSpan.remove();
                    }
                }
                // Update notification content to show "No new notifications."
                document.getElementById('notificationContent').innerHTML = '<div class="p-4 text-sm text-gray-500 text-center">No new notifications.</div>';
                
                showPopup("Notifications marked as read.", true);
                return true;
            } else {
                 showPopup("Failed to mark notifications as read.", false);
                 return false;
            }
        } catch (error) {
            showPopup("Network error marking notifications as read.", false);
            return false;
        }
    }
    
    // NEW: Function to open the Claim/Report Found modal
    function openClaimModal(itemId, itemName) {
        document.getElementById('claimItemId').value = itemId;
        // Use textContent to safely inject the item name, and replace single quotes for safety
        document.getElementById('claimItemName').textContent = itemName.replace(/\\'/g, "'"); 
        closeModal('itemDetailModal'); // Close details modal first
        openModal('claimModal');
    }


    // --- Filtering Logic ---
    function filterItems() {
        const searchValue = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();
        
        let matchedItems = 0;
        let resultsHTML = "";

        itemCards.forEach(item => {
            const itemName = item.dataset.name.toLowerCase();
            const itemCategory = item.dataset.category.toLowerCase();
            const itemStatus = item.dataset.status.toLowerCase();
            
            // 1. Check Search Match (Name or Category)
            const matchesSearch = searchValue === "" || itemName.includes(searchValue) || itemCategory.includes(searchValue);
            
            // 2. Check Category Filter Match
            const matchesCategory = selectedCategory === "" || itemCategory === selectedCategory;
            
            // 3. Check Status Filter Match
            const matchesStatus = selectedStatus === "" || itemStatus === selectedStatus;
            
            if (matchesSearch && matchesCategory && matchesStatus) {
                item.style.display = "block";
                matchedItems++;
                
                if (searchValue !== "" && matchedItems < 6) { 
                    const truncatedText = item.dataset.name;
                    resultsHTML += `
                      <div class="p-3 border-b last:border-none cursor-pointer hover:bg-gray-100 text-sm" 
                            onclick="highlightItem('${item.id}'); document.getElementById('searchInput').value='${truncatedText}'; document.getElementById('searchResults').classList.add('hidden');">
                        <span class="font-semibold text-${item.dataset.type === 'lost' ? 'red' : 'green'}-600">${item.dataset.type.toUpperCase()}</span>: ${truncatedText}
                      </div>
                    `;
                }
            } else {
                item.style.display = "none";
            }
        });

        if (searchValue !== "" && resultsHTML !== "") {
            searchResults.innerHTML = resultsHTML;
            searchResults.classList.remove("hidden");
        } else {
            searchResults.classList.add("hidden");
        }

        // --- CORRECTED LOGIC FOR HIDING/SHOWING "NO ITEMS" MESSAGE ---
        let noItemsMessage = document.getElementById('noItemsMessage');
        
        // Count only the actual item cards that are NOT hidden
        const visibleItemCards = itemCards.filter(item => item.style.display !== 'none');

        if (visibleItemCards.length === 0 && itemCards.length > 0) {
            // Case 1: No items match the filter, and a message needs to be shown.
            if (!noItemsMessage) {
                const msgDiv = document.createElement('div');
                msgDiv.id = 'noItemsMessage';
                msgDiv.className = 'lg:col-span-3 text-center py-10 bg-white rounded-xl shadow-lg'; 
                msgDiv.innerHTML = '<p class="text-xl text-gray-500">No items match your current search or filters.</p>';
                
                itemGrid.appendChild(msgDiv);
            }
        } else if (noItemsMessage) {
             // Case 2: Items are now visible, so remove the message.
            noItemsMessage.remove(); 
        }
        // --- END CORRECTED LOGIC ---
    }
    
    function highlightItem(itemId) {
        const item = document.getElementById(itemId);
        if (item) {
            document.querySelectorAll(".highlight").forEach(el => el.classList.remove("highlight"));
            item.style.display = "block"; 
            
            item.scrollIntoView({ behavior: 'smooth', block: 'center' });
            item.classList.add("highlight");
            setTimeout(() => item.classList.remove("highlight"), 2000);

            setTimeout(() => filterItems(), 2500);
        }
    }


    // --- ITEM DETAIL MODAL LOGIC (MODIFIED) ---
    async function showItemDetails(id) {
        if (!itemDetailContent || !itemDetailModal) return;

        openModal('itemDetailModal');
        itemDetailContent.innerHTML = '<div class="text-center p-8 text-blue-500 font-semibold">Loading item details...</div>';

        try {
            // Ensure this API call matches the format in your api.php
            const response = await fetch(`api.php?action=get_item_details&id=${id}`);
            const item = await response.json();

            if (response.ok && item.name) {
                const isLost = item.type === 'lost';
                // NOTE: Using full color names for Tailwind JIT compatibility
                const mainColor = isLost ? 'red' : 'green'; 
                const statusColor = item.status === 'Active' ? 'blue' : 'gray';
                const reportedDateTime = item.reported_at ? new Date(item.reported_at).toLocaleDateString() : 'N/A';
                // Fallback for null event_date_time
                const eventDateTime = item.event_date_time ? new Date(item.event_date_time).toLocaleString() : 'N/A'; 
                const imageUrl = item.image_url || 'static/images/placeholder.jpg';
                
                // NEW: Determine if a 'Report Found' button is needed
                let actionButtonHTML = '';
                if (isLost && item.status === 'Active') {
                    // Pass item name, escaping single quotes for correct string literal usage
                    actionButtonHTML = `
                        <button onclick="openClaimModal(${item.id}, '${item.name.replace(/'/g, "\\'")}')" 
                                class="w-full mt-6 bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-semibold transition">
                            I FOUND THIS ITEM! (Report Match)
                        </button>
                    `;
                }


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
                        
                        ${actionButtonHTML} 

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
    // --- END: ITEM DETAIL MODAL LOGIC ---

    // --- Event Listeners ---
    document.addEventListener('DOMContentLoaded', () => {

        searchInput.addEventListener("input", filterItems);
        categoryFilter.addEventListener("change", filterItems);
        statusFilter.addEventListener("change", filterItems);
        filterItems();

        // --- Form Submission Logic (Omitted for brevity, assumed functional) ---
        document.getElementById('lostItemForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('api.php?action=report', { method: 'POST', body: formData });
                const result = await response.json();
                if (response.ok) {
                    showPopup("✅ Lost item reported successfully!", true);
                    closeModal('lostModal');
                    e.target.reset();
                    setTimeout(() => window.location.reload(), 1500); 
                } else {
                    showPopup("Error: " + (result.error || 'Unknown error.'), false);
                }
            } catch (error) {
                showPopup("Network error: Could not submit report.", false);
            }
        });

        document.getElementById('foundItemForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('api.php?action=report', { method: 'POST', body: formData });
                const result = await response.json();
                if (response.ok) {
                    showPopup("🌟 Found item reported successfully! Thank you.", true);
                    closeModal('foundModal');
                    e.target.reset();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showPopup("Error: " + (result.error || 'Unknown error.'), false);
                }
            } catch (error) {
                showPopup("Network error: Could not submit report.", false);
            }
        });
        
        // NEW: Claim Item Form Submission Handler
        document.getElementById('claimItemForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                // NOTE: This assumes you will add an action=claim_item handler in your api.php
                const response = await fetch('api.php?action=claim_item', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (response.ok) {
                    showPopup("🎉 Match reported successfully! The owner will be notified.", true);
                    closeModal('claimModal');
                    e.target.reset();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showPopup("Error: " + (result.error || 'Could not submit match report.'), false);
                }
            } catch (error) {
                showPopup("Network error: Could not submit match report.", false);
            }
        });

        // --- UI Listeners (Dropdowns and Logout) ---
        
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        document.getElementById('userMenuBtn')?.addEventListener('click', () => {
            userMenuDropdown.classList.toggle('hidden');
            notificationDropdown.classList.add('hidden');
        });
        
        document.getElementById('notificationBtn')?.addEventListener('click', () => {
            notificationDropdown.classList.toggle('hidden');
            userMenuDropdown.classList.add('hidden');
        });

        document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await fetch('api.php?action=logout', { method: 'POST' }); 
                window.location.href = 'login.php'; 
            } catch (error) {
                console.error('Logout failed:', error);
                window.location.href = 'login.php'; 
            }
        });

        // UPDATED: Use the new reusable markNotificationsRead function
        document.getElementById('markReadBtn')?.addEventListener('click', async () => {
            const success = await markNotificationsRead();
            if (success) {
                notificationDropdown.classList.add('hidden');
            }
        });

        // PROFILE MODAL LOGIC (Omitted for brevity, assumed functional)
        const profileModal = document.getElementById('profileModal');
        const profileModalContent = document.getElementById('profileModalContent');
        const openProfileModalLink = document.getElementById('openProfileModal');
        
        openProfileModalLink.addEventListener('click', async (e) => {
            e.preventDefault();
            userMenuDropdown.classList.add('hidden'); 
            
            if (!profileModal) {
                 console.error('Profile modal element not found.');
                 return;
            }

            openModal('profileModal');
            profileModalContent.innerHTML = '<div class="text-center p-6 text-gray-500">Loading profile statistics...</div>';

            try {
                const response = await fetch('api.php?action=get_profile_stats');
                const stats = await response.json();

                if (response.ok && stats.email) {
                    profileModalContent.innerHTML = `
                        <div class="space-y-4 p-6">
                            <div class="text-xl font-bold text-gray-800 border-b pb-2">User Profile</div>
                            
                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h7m0 0v7m0-7L10 14"/></svg>
                                <div>
                                    <p class="text-xs text-gray-500">User Role</p>
                                    <p class="font-medium text-gray-700">${USER_ROLE.toUpperCase()}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/></svg>
                                <div>
                                    <p class="text-xs text-gray-500">Email Address</p>
                                    <p class="font-medium text-gray-700">${stats.email}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                                <div class="bg-red-50 p-3 rounded-lg">
                                    <p class="text-sm font-semibold text-red-600">${stats.total_reports}</p>
                                    <p class="text-xs text-gray-500">Total Reports</p>
                                </div>
                                <div class="bg-green-50 p-3 rounded-lg">
                                    <p class="text-sm font-semibold text-green-600">${stats.cases_solved}</p>
                                    <p class="text-xs text-gray-500">Cases Solved</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    profileModalContent.innerHTML = '<div class="text-center p-6 text-red-500">Failed to load profile data.</div>';
                }
            } catch (error) {
                console.error("Error fetching profile stats:", error);
                profileModalContent.innerHTML = '<div class="text-center p-6 text-red-500">Network error loading profile stats.</div>';
            }
        });


        // --- MODIFIED CLOSING LOGIC (The Fix) ---
        document.addEventListener('click', (event) => {
            // General Dropdowns
            if (!document.getElementById('userMenuBtn')?.contains(event.target) && 
                !userMenuDropdown?.contains(event.target)) {
                userMenuDropdown?.classList.add('hidden');
            }
            if (!document.getElementById('notificationBtn')?.contains(event.target) && 
                !notificationDropdown?.contains(event.target)) {
                notificationDropdown?.classList.add('hidden');
            }
            
            // Profile Modal (closes when clicking anywhere outside its content)
            if (profileModal && profileModal.classList.contains('active') && !profileModalContent.contains(event.target) && !openProfileModalLink.contains(event.target)) {
                closeModal('profileModal');
            }

            // Other general modals (closing on backdrop click)
            document.querySelectorAll('.modal').forEach(modal => {
                // If the click target is the modal backdrop itself and not the content container inside it
                if (event.target === modal && modal.id !== 'profileModal' && modal.id !== 'claimModal') {
                    modal.classList.remove('active');
                }
            });
        });

    });
</script>