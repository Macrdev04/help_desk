<?php
require 'config.php';
require 'auth.php';

if (!isAdmin() && !isAgent()) {
  die('No autorizado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ticketId = $_POST['ticket_id'] ?? null;
  
  if (isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    $db->query("UPDATE tickets SET status = '$newStatus' WHERE id = $ticketId");
  }
  
  if (isset($_POST['assigned_to'])) {
    $assignedTo = $_POST['assigned_to'] ?: null;
    $db->query("UPDATE tickets SET assigned_to = " . ($assignedTo ? "'$assignedTo'" : "NULL") . " WHERE id = $ticketId");
  }
  
  header("Location: " . $_SERVER['HTTP_REFERER']);
  exit;
}