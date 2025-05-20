<?php
require '../includes/config.php';
require '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$tickets = $db->query("SELECT * FROM tickets WHERE user_id = $userId ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

include '../templates/header.php';
?>

<h2>Mi Panel</h2>
<div class="dashboard-grid">
    <!-- Sección: Crear ticket rápido -->
    <div class="card">
        <h3>Nuevo Ticket</h3>
        <form action="../create_ticket.php" method="POST">
            <input type="text" name="title" placeholder="Asunto" required>
            <textarea name="description" placeholder="Descripción..." required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <!-- Sección: Últimos tickets -->
    <div class="card">
        <h3>Mis Tickets Recientes</h3>
        <ul class="ticket-list">
            <?php foreach ($tickets as $ticket): ?>
            <li>
                <a href="../view_ticket.php?id=<?= $ticket['id'] ?>">
                    #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
                    <span class="status-badge <?= $ticket['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="../tickets.php" class="btn">Ver todos</a>
    </div>
</div>

<?php include '../templates/footer.php'; ?>