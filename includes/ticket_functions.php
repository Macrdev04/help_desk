<?php
// Crear ticket
function createTicket($title, $description, $priority, $userId) {
  global $db;
  $stmt = $db->prepare("INSERT INTO tickets (title, description, priority, user_id) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("sssi", $title, $description, $priority, $userId);
  return $stmt->execute();
}

// Obtener tickets por usuario
function getUserTickets($userId) {
  global $db;
  $result = $db->query("SELECT * FROM tickets WHERE user_id = $userId ORDER BY created_at DESC");
  return $result->fetch_all(MYSQLI_ASSOC);
}

// Agregar comentario
function addComment($ticketId, $userId, $comment) {
  global $db;
  $stmt = $db->prepare("INSERT INTO comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
  $stmt->bind_param("iis", $ticketId, $userId, $comment);
  return $stmt->execute();
}
?>