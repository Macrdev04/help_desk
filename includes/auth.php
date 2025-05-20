<?php
session_start();

function loginUser($email, $password) {
    global $db;
    
    // 1. Buscar usuario por email
    $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    // 2. Verificar si existe Y si la contraseña coincide
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function isAgent() {
    return isLoggedIn() && ($_SESSION['user_role'] === 'agent' || isAdmin());
}

function logout() {
    session_unset();
    session_destroy();
}
?>