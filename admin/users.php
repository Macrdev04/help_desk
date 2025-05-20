<?php
require '../includes/config.php';
require '../includes/auth.php';

if (!isAdmin()) {
  header('Location: ../login.php');
  exit;
}

// Cambiar rol de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
  $userId = $_POST['user_id'];
  $newRole = $_POST['new_role'];
  $db->query("UPDATE users SET role = '$newRole' WHERE id = $userId");
}

// Listar usuarios
$users = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include '../templates/header.php';
?>

<h2>Gestión de Usuarios</h2>

<table class="admin-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Email</th>
      <th>Rol</th>
      <th>Fecha Registro</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $user): ?>
    <tr>
      <td><?= $user['id'] ?></td>
      <td><?= htmlspecialchars($user['name']) ?></td>
      <td><?= htmlspecialchars($user['email']) ?></td>
      <td>
        <form method="post" class="inline-form">
          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
          <select name="new_role" onchange="this.form.submit()">
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
            <option value="agent" <?= $user['role'] === 'agent' ? 'selected' : '' ?>>Agente</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
        </form>
      </td>
      <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
      <td>
        <a href="#" class="btn">Editar</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include '../templates/footer.php'; ?>