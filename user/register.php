<?php
require 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'user'; // Por defecto

    // Validaciones básicas
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido";
    } else {
        // Verificar si el email ya existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "El email ya está registrado";
        } else {
            // Hash de la contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $passwordHash, $role);
            
            if ($stmt->execute()) {
                header("Location: login.php?success=1");
                exit;
            } else {
                $error = "Error al registrar: " . $db->error;
            }
        }
    }
}
?>

<!-- Formulario HTML -->
<form method="POST">
    <input type="text" name="name" placeholder="Nombre completo" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Registrarse</button>
    
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</form>