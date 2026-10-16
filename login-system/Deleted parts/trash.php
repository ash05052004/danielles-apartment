<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

// Handle permanent delete single tenant
if (isset($_GET['delete_permanent'])) {
    $userId = $_GET['delete_permanent'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    header("Location: trash.php?msg=deleted_permanent");
    exit();
}

// Handle bulk permanent delete
if (isset($_POST['delete_selected']) && isset($_POST['selected_tenants'])) {
    $selected = $_POST['selected_tenants'];
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
    $stmt->execute($selected);
    header("Location: trash.php?msg=deleted_selected");
    exit();
}

// Handle empty all trash
if (isset($_GET['empty_all'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'tenant' AND is_deleted = 1");
    $stmt->execute();
    header("Location: trash.php?msg=emptied");
    exit();
}

// Fetch all deleted tenants (soft deleted records)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'tenant' AND is_deleted = 1 ORDER BY deleted_at DESC");
$stmt->execute();
$deletedTenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted_permanent') $message = '🗑️ Tenant permanently deleted';
    if ($_GET['msg'] == 'deleted_selected') $message = '🗑️ Selected tenants permanently deleted';
    if ($_GET['msg'] == 'emptied') $message = '🗑️ Trash emptied successfully';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/OStyle.css">
    <style>
        .trash-container {
            max-width: 1000px;
            margin: 100px auto 50px;
            padding: 30px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .trash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #dc3545;
        }
        .back-btn {
            background: rgba(108, 117, 125, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid #6c757d;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #6c757d;
        }
        .empty-all-btn {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid #dc3545;
            transition: all 0.3s;
        }
        .empty-all-btn:hover {
            background: #dc3545;
            color: white;
        }
        .trash-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .trash-table th {
            background: rgba(220, 53, 69, 0.3);
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        .trash-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .trash-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-block;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        .bulk-actions {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .bulk-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .bulk-btn.delete {
            background: #dc3545;
            color: white;
        }
        .bulk-btn.delete:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        .checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .message {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .count-badge {
            background: #dc3545;
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 14px;
        }
        .room-badge {
            background: #667eea;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: rgba(255, 255, 255, 0.5);
        }
        .empty-state span {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <div class="video-background">
        <iframe id="youtube-player" 
                src="https://www.youtube.com/embed/ObDgomocKZY?autoplay=1&mute=1&loop=1&playlist=ObDgomocKZY&controls=0&showinfo=0&rel=0&modestbranding=1&enablejsapi=1" 
                frameborder="0" 
                allow="autoplay; encrypted-media" 
                allowfullscreen>
        </iframe>
    </div>
    <div class="overlay"></div>

    <!-- Unmute Button -->
    <div class="unmute-btn" id="unmuteButton">
        <span>🔇</span> Click for Audio
    </div>

    <div class="navbar">
        <h2>🗑️ Trash Management</h2>
        <div>
            <a href="owner_dashboard.php" class="back-btn">⬅️ Back to Dashboard</a>
        </div>
    </div>

    <div class="trash-container">
        <div class="trash-header">
            <h1>🗑️ Deleted Tenants <span class="count-badge"><?php echo count($deletedTenants); ?> items</span></h1>
            <?php if (count($deletedTenants) > 0): ?>
                <a href="?empty_all=1" class="empty-all-btn" onclick="return confirm('⚠️ Permanently delete ALL tenants in trash? This cannot be undone!')">🗑️ Empty Trash</a>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="message">✅ <?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (count($deletedTenants) > 0): ?>
            <form method="POST" id="trashForm">
                <table class="trash-table">
                    <thead>
                        <tr>
                            <th width="50"><input type="checkbox" id="selectAll" class="checkbox"></th>
                            <th>Tenant</th>
                            <th>Original Room</th>
                            <th>Deleted Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedTenants as $tenant): 
                            preg_match('/^(A[1-7]|B[1-7])/', $tenant['email'], $matches);
                            $room = $matches[1] ?? 'Unknown';
                        ?>
                            <tr>
                                <td><input type="checkbox" name="selected_tenants[]" value="<?php echo $tenant['id']; ?>" class="checkbox tenant-checkbox"></td>
                                <td><strong><?php echo htmlspecialchars($tenant['email']); ?></strong></td>
                                <td><span class="room-badge">Room <?php echo $room; ?></span></td>
                                <td><?php echo date('F j, Y g:i A', strtotime($tenant['deleted_at'])); ?></td>
                                <td>
                                    <a href="?delete_permanent=<?php echo $tenant['id']; ?>" class="delete-btn" onclick="return confirm('⚠️ Permanently delete this tenant? This cannot be undone!')">❌ Delete Permanently</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="bulk-actions">
                    <span style="color: white;">With selected:</span>
                    <button type="submit" name="delete_selected" class="bulk-btn delete" onclick="return confirm('⚠️ Permanently delete selected tenants? This cannot be undone!')">🗑️ Delete Selected</button>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <span>🗑️</span>
                <p>Trash is empty</p>
                <p style="font-size: 14px; margin-top: 10px;">Deleted tenants will appear here</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.getElementsByClassName('tenant-checkbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        // Update select all checkbox when individual checkboxes change
        const checkboxes = document.getElementsByClassName('tenant-checkbox');
        for (let checkbox of checkboxes) {
            checkbox.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('.tenant-checkbox:checked').length === checkboxes.length;
                document.getElementById('selectAll').checked = allChecked;
            });
        }
    </script>

    <!-- YouTube Audio Control JavaScript -->
    <script src="https://www.youtube.com/iframe_api"></script>
    <script>
        let player;
        function onYouTubeIframeAPIReady() {
            player = new YT.Player('youtube-player', {
                events: { 'onReady': onPlayerReady }
            });
        }
        function onPlayerReady(event) {
            const unmuteBtn = document.getElementById('unmuteButton');
            player.mute();
            unmuteBtn.addEventListener('click', function() {
                if (player.isMuted()) {
                    player.unMute();
                    this.innerHTML = '<span>🔊</span> Audio On';
                    this.classList.remove('muted');
                    this.classList.add('unmuted');
                } else {
                    player.mute();
                    this.innerHTML = '<span>🔇</span> Click for Audio';
                    this.classList.remove('unmuted');
                    this.classList.add('muted');
                }
            });
        }
    </script>
</body>
</html>