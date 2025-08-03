<?php
// Management interface for allowed groups
function getAllowedGroups() {
    $groupsFile = 'allowed_groups.txt';
    if (!file_exists($groupsFile)) {
        return [];
    }
    
    $groups = file($groupsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_map('intval', $groups);
}

function removeAllowedGroup($chatId) {
    $groupsFile = 'allowed_groups.txt';
    $groups = getAllowedGroups();
    
    // Remove the group ID from the array
    $groups = array_diff($groups, [$chatId]);
    
    // Write the updated list back to the file
    file_put_contents($groupsFile, implode(PHP_EOL, $groups) . PHP_EOL);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['chat_id'])) {
        removeAllowedGroup((int)$_POST['chat_id']);
        $message = "Group removed successfully.";
    } else if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['chat_id'])) {
        $chatId = (int)$_POST['chat_id'];
        $groupsFile = 'allowed_groups.txt';
        $groups = getAllowedGroups();
        
        // Check if group is already in the list
        if (!in_array($chatId, $groups)) {
            // Add the group ID to the file
            file_put_contents($groupsFile, $chatId . PHP_EOL, FILE_APPEND | LOCK_EX);
            $message = "Group added successfully.";
        } else {
            $message = "Group is already in the allowed list.";
        }
    } else if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        file_put_contents('allowed_groups.txt', '');
        $message = "All groups cleared.";
    }
}

$allowedGroups = getAllowedGroups();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Allowed Groups</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"], input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #0088cc; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #006699; }
        .remove-btn { background-color: #dc3545; }
        .remove-btn:hover { background-color: #c82333; }
        .clear-btn { background-color: #ffc107; color: #212529; }
        .clear-btn:hover { background-color: #e0a800; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .group-list { list-style-type: none; padding: 0; }
        .group-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px; }
        .group-id { font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Allowed Groups</h1>
        
        <?php if (isset($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Add Group</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="chat_id">Group Chat ID:</label>
                    <input type="number" id="chat_id" name="chat_id" required>
                </div>
                <button type="submit">Add Group</button>
            </form>
        </div>
        
        <div class="section">
            <h2>Allowed Groups</h2>
            <?php if (empty($allowedGroups)): ?>
                <p>No groups in the allowed list. The bot will respond in all groups.</p>
            <?php else: ?>
                <p>The bot will only respond in these groups:</p>
                <ul class="group-list">
                    <?php foreach ($allowedGroups as $groupId): ?>
                        <li class="group-item">
                            <span class="group-id"><?php echo htmlspecialchars($groupId); ?></span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="chat_id" value="<?php echo htmlspecialchars($groupId); ?>">
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="clear-btn" onclick="return confirm('Are you sure you want to clear all groups?')">Clear All Groups</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>How It Works</h2>
            <ul>
                <li>When you add the bot to a group, the group ID is automatically added to this list</li>
                <li>The bot will only respond in groups that are in this list</li>
                <li>If the list is empty, the bot will respond in all groups</li>
                <li>You can manually add or remove groups using this interface</li>
            </ul>
        </div>
    </div>
</body>
</html>
