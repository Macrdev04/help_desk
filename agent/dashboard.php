<?php
require '../../includes/config.php';
require '../../includes/auth.php';

if (!isAgent() && !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$assignedTickets = $db->query("
    SELECT t.*, u.name as user_name 
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.assigned_to = $userId AND t.status != 'closed'
    ORDER BY t.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$unassignedTickets = $db->query("
    SELECT COUNT(*) as count FROM tickets 
    WHERE assigned_to IS NULL AND status = 'open'
")->fetch_assoc()['count'];

include '../../templates/header.php';
?>

<h2>Panel de Agente</h2>
<div class="dashboard-grid">
    <!-- Tickets asignados -->
    <div class="card">
        <h3>Tickets Asignados</h3>
        <ul class="ticket-list">
            <?php foreach ($assignedTickets as $ticket): ?>
            <li>
                <a href="../../view_ticket.php?id=<?= $ticket['id'] ?>">
                    #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
                    <span class="priority-badge <?= $ticket['priority'] ?>">
                        <?= ucfirst($ticket['priority']) ?>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="../../tickets.php?assigned=me" class="btn">Ver todos</a>
    </div>

    <!-- Resumen rápido -->
    <div class="card">
        <h3>Resumen</h3>
        <div class="stats">
            <div class="stat">
                <span class="number"><?= $unassignedTickets ?></span>
                <span class="label">Tickets sin asignar</span>
            </div>
            <a href="../../tickets.php?assigned=unassigned" class="btn">Asignar tickets</a>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>