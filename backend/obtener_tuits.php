<?php
require '../php/verificar_sesion.php';
require '../php/config.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

$conn = connectDatabase();

$user_id = $_SESSION['id_user'];

$sql = "
    SELECT t.*, u.username, u.name
    FROM publicaciones t
    JOIN user u ON t.user_id = u.id
    WHERE t.user_id = ? OR t.user_id IN (
        SELECT usuario FROM seguidos WHERE seguidor = ?
    )
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$tuits = [];
while ($row = $result->fetch_assoc()) {
    $tuits[] = $row;
}

echo json_encode(['tuits' => $tuits]);

$stmt->close();
$conn->close();