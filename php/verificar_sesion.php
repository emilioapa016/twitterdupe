<?php
// Verificar si la sesión ya ha sido iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si la variable de sesión 'username' está establecida
if (!isset($_SESSION['username'])) {
    // Redirigir al login si no hay sesión
    header("Location: ../login.html");
    exit();
}
