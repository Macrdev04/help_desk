<?php
require 'database.php';
require 'includes/auth.php';

// Simular retardo ante intentos fallidos
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
    sleep(2); // Retardo de 2 segundos
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (loginUser($email, $password)) {
        // Resetear intentos fallidos
        unset($_SESSION['login_attempts']);
        
        // Redirigir según rol
        if (isAdmin()) header('Location: admin/dashboard.php');
        elseif (isAgent()) header('Location: agent/dashboard.php');
        else header('Location: user/dashboard.php');
        exit;
    } else {
        // Registrar intento fallido
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $error = "Credenciales incorrectas";
    }
}
?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Iniciar sesión</button>
    
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</form>