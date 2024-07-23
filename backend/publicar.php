<?php
session_start();
require '../php/verificar_sesion.php';
require '../php/config.php';

// Obtener datos del formulario
$content = $_POST['content'] ?? '';

// Verificar que el contenido no esté vacío y no exceda los 280 caracteres
if (empty($content)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El contenido del tuit no puede estar vacío.'
    ]);
    exit();
}

if (strlen($content) > 280) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El contenido del tuit no puede exceder los 280 caracteres.'
    ]);
    exit();
}

// Conectar a la base de datos
$conn = connectDatabase();

// Preparar y ejecutar la consulta para insertar el tuit
$stmt = $conn->prepare("INSERT INTO publicaciones (user_id, contenido) VALUES (?, ?)");
$stmt->bind_param("is", $_SESSION['id_user'], $content);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Tuit publicado con éxito.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo publicar el tuit.'
    ]);
}

$stmt->close();
$conn->close();
