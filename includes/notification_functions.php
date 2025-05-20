<?php
require 'config.php';

function getUnreadNotifications($userId) {
    global $db;
    $query = "SELECT * FROM notifications 
              WHERE user_id = ? AND is_read = 0 
              ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function markAsRead($notificationId) {
    global $db;
    $query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $notificationId);
    return $stmt->execute();
}
?>