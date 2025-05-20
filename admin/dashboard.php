<?php
require '../../includes/config.php';
require '../../includes/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$openTickets = $db->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'open'")->fetch_assoc()['count'];
$resolvedTickets = $db->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'resolved'")->fetch_assoc()['count'];
$usersCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

include '../../templates/header.php';
?>

<h2>Panel de Administración</h2>
<div class="dashboard-grid">
    <!-- Estadísticas -->
    <div class="card">
        <h3>Estadísticas Globales</h3>
        <div class="stats">
            <div class="stat">
                <span class="number"><?= $openTickets ?></span>
                <span class="label">Tickets abiertos</span>
            </div>
            <div class="stat">
                <span class="number"><?= $resolvedTickets ?></span>
                <span class="label">Tickets resueltos</span>
            </div>
            <div class="stat">
                <span class="number"><?= $usersCount ?></span>
                <span class="label">Usuarios registrados</span>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="card">
        <h3>Acciones</h3>
        <div class="quick-actions">
            <a href="../admin/tickets.php" class="btn">Gestionar tickets</a>
            <a href="../admin/users.php" class="btn">Gestionar usuarios</a>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>