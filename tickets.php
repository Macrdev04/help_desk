<?php
require 'includes/config.php';
require 'includes/auth.php';

if (!isLoggedIn()) {
  header('Location: login.php');
  exit;
}

$tickets = getUserTickets($_SESSION['user_id']);

include 'templates/header.php';
?>

<h2>Mis Tickets</h2>
<table class="ticket-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Título</th>
      <th>Estado</th>
      <th>Prioridad</th>
      <th>Fecha</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($tickets as $ticket): ?>
    <tr>
      <td><?= $ticket['id'] ?></td>
      <td><a href="view_ticket.php?id=<?= $ticket['id'] ?>"><?= htmlspecialchars($ticket['title']) ?></a></td>
      <td><span class="status-badge <?= $ticket['status'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></td>
      <td><?= ucfirst($ticket['priority']) ?></td>
      <td><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include 'templates/footer.php'; ?>