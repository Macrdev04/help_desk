<?php
//Conexion a BD
header('Access-Control-Allow-Origin: *'); // Solo para desarrollo local
$servername = "localhost";
$username = "test1";
$password = "test.01";
$dbname = "helpdesk_db";
$port = 3306;

// Parámetros

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error de conexión a la base de datos"]);
    exit;
}
?>      