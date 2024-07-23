<?php
session_start();
require 'config.php';

// Obtener datos del formulario
$username = $_POST['user'] ?? '';
$password = $_POST['password'] ?? '';

// Verificar que los campos no estén vacíos
if (empty($username) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos los campos son obligatorios.'
    ]);
    exit;
}

$conn = connectDatabase();

// Preparar y ejecutar la consulta
$stmt = $conn->prepare("SELECT id, username, password FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Usuario no encontrado.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Verificar la contraseña
if (password_verify($password, $user['password'])) {
    // Establecer la sesión del usuario
    $_SESSION['username'] = $username;
    $_SESSION['id_user'] = $user['id'];
    echo json_encode([
        'status' => 'success',
        'message' => 'Inicio de sesión exitoso.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Contraseña incorrecta.'
    ]);
}

$stmt->close();
$conn->close();