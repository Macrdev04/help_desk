<?php
require '../etc/database.php';

// Datos del super usuario
$nombre = "administrador"; // Nombre de usuario
$email = "manuelcarreno.r@gmail.com"; // Correo electrónico
$passwrd = "1234";  // PIN temporal, luego puedes cambiarlo
$role = "admin"; // Nivel de acceso

// Hashear el PIN con bcrypt
$pass_bcrypt = password_hash($passwrd, PASSWORD_BCRYPT);

// Inserción
$sql = "INSERT INTO users (u_name, email, passwrd, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $nombre, $email, $pass_bcrypt, $role);

if ($stmt->execute()) {
    echo "✅ Usuario administrador creado correctamente.";
} else {
    echo "❌ Error al crear el usuario: " . $stmt->error;
}
?>