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
if ($db->connect_error) die("Error de conexión: " . $db->connect_error);
?>