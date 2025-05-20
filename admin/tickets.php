<?php
require '../includes/config.php';
require '../includes/auth.php';

// Solo administradores/agentes
if (!isAdmin() && !isAgent()) {
  header('Location: ../login.php');
  exit;
}

// Filtros
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assigned = $_GET['assigned'] ?? '';

// Consulta base
$query = "SELECT t.*, u.name as user_name, a.name as agent_name 
          FROM tickets t
          JOIN users u ON t.user_id = u.id
          LEFT JOIN users a ON t.assigned_to = a.id
          WHERE 1=1";

if ($status) $query .= " AND t.status = '$status'";
if ($priority) $query .= " AND t.priority = '$priority'";
if ($assigned === 'me') $query .= " AND t.assigned_to = {$_SESSION['user_id']}";
elseif ($assigned === 'unassigned') $query .= " AND t.assigned_to IS NULL";

$query .= " ORDER BY t.created_at DESC";
$tickets = $db->query($query)->fetch_all(MYSQLI_ASSOC);

include '../templates/header.php';
?>

<h2>Gestión de Tickets</h2>

<!-- Filtros -->
<form method="get" class="filters">
  <select name="status">
    <option value="">Todos los estados</option>
    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Abiertos</option>
    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>En progreso</option>
  </select>
  
  <select name="priority">
    <option value="">Todas las prioridades</option>
    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Alta</option>
    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Media</option>
  </select>
  
  <select name="assigned">
    <option value="">Todos</option>
    <option value="me" <?= $assigned === 'me' ? 'selected' : '' ?>>Asignados a mí</option>
    <option value="unassigned" <?= $assigned === 'unassigned' ? 'selected' : '' ?>>Sin asignar</option>
  </select>
  
  <button type="submit">Filtrar</button>
</form>

<!-- Tabla de tickets -->
<table class="admin-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Título</th>
      <th>Usuario</th>
      <th>Estado</th>
      <th>Asignado a</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($tickets as $ticket): ?>
    <tr>
      <td><?= $ticket['id'] ?></td>
      <td><a href="../view_ticket.php?id=<?= $ticket['id'] ?>"><?= htmlspecialchars($ticket['title']) ?></a></td>
      <td><?= htmlspecialchars($ticket['user_name']) ?></td>
      <td>
        <form method="post" action="../includes/ticket_actions.php" class="inline-form">
          <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
          <select name="new_status" onchange="this.form.submit()">
            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Abierto</option>
            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>En progreso</option>
            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resuelto</option>
          </select>
        </form>
      </td>
      <td>
        <form method="post" action="../includes/ticket_actions.php" class="inline-form">
          <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
          <select name="assigned_to" onchange="this.form.submit()">
            <option value="">-- Sin asignar --</option>
            <?php 
            $agents = $db->query("SELECT id, name FROM users WHERE role IN ('admin', 'agent')");
            while ($agent = $agents->fetch_assoc()): 
            ?>
            <option value="<?= $agent['id'] ?>" <?= $ticket['assigned_to'] == $agent['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($agent['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </form>
      </td>
      <td>
        <a href="../view_ticket.php?id=<?= $ticket['id'] ?>" class="btn">Ver</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include '../templates/footer.php'; ?>