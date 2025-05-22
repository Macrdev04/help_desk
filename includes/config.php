<?php
// Configuración de la DB
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'helpdesk_db');

// Iniciar sesión segura
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true // Solo en HTTPS
]);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error de conexión a la base de datos"]);
    exit;
}
?>