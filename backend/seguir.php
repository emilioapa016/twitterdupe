<?php
session_start();
require '../php/verificar_sesion.php';
require '../php/config.php';

// Obtener el username del usuario a seguir desde el formulario
$username_to_follow = $_POST['username'] ?? '';

// Verificar que el username no esté vacío
if (empty($username_to_follow)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El nombre de usuario no puede estar vacío.'
    ]);
    exit();
}

// Conectar a la base de datos
$conn = connectDatabase();

// Obtener el ID del usuario a seguir
$stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
$stmt->bind_param("s", $username_to_follow);
$stmt->execute();
$stmt->bind_result($user_id_to_follow);
$stmt->fetch();
$stmt->close();

if (empty($user_id_to_follow)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El usuario a seguir no existe.'
    ]);
    exit();
}

// Obtener el ID del usuario en sesión
$user_id_session = $_SESSION['id_user'];

// Verificar si el usuario ya está siguiendo a la persona
$stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE usuario = ? AND seguidor = ?");
$stmt->bind_param("ii", $user_id_to_follow, $user_id_session);
$stmt->execute();
$stmt->bind_result($following_count);
$stmt->fetch();
$stmt->close();

if ($following_count > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ya sigues a este usuario.'
    ]);
    exit();
}

// Insertar la relación de seguimiento en la base de datos
$stmt = $conn->prepare("INSERT INTO seguidos (usuario, seguidor) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id_to_follow, $user_id_session);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Ahora sigues a ' . htmlspecialchars($username_to_follow) . '.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo seguir al usuario.'
    ]);
}

$stmt->close();
$conn->close();
