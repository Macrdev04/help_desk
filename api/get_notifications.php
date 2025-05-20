<?php
header('Content-Type: application/json');
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/notification_functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$notifications = getUnreadNotifications($_SESSION['user_id']);
echo json_encode($notifications);
?>