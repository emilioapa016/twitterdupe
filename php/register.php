<?php
require 'config.php';

function validatePassword($password) {
    return strlen($password) >= 8 && !preg_match('/\s/', $password);
}

$conn = connectDatabase();

$username = $_POST['user'] ?? '';
$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';

// Verificar que todos los campos estén completos
if (empty($username) || empty($password) || empty($email) || empty($name)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos los campos son obligatorios.'
    ]);
    exit;
}

// Validar la contraseña
if (!validatePassword($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'La contraseña debe tener al menos 8 caracteres y no contener espacios.'
    ]);
    exit;
}

// Verificar si el nombre de usuario o el correo electrónico ya existen
$stmt = $conn->prepare("SELECT COUNT(*) FROM user WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    // Determinar si el conflicto es con el nombre de usuario o el correo electrónico
    $stmt = $conn->prepare("SELECT username, email FROM user WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->bind_result($existingUsername, $existingEmail);
    $stmt->fetch();
    $stmt->close();

    if ($username === $existingUsername) {
        echo json_encode([
            'status' => 'error',
            'message' => 'El nombre de usuario ya existe. Por favor, elija otro.'
        ]);
    } elseif ($email === $existingEmail) {
        echo json_encode([
            'status' => 'error',
            'message' => 'El correo electrónico ya está registrado. Por favor, utilice otro.'
        ]);
    }
    exit;
}

// Encriptar la contraseña
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO user (name, username, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $username, $email, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Nuevo usuario creado exitosamente.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
