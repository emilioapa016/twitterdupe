<?php
require '../php/config.php';
require '../php/verificar_sesion.php';

$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($searchQuery)) {
    $conn = connectDatabase();

    $stmt = $conn->prepare("SELECT id, name, username, created_at FROM user WHERE username LIKE ?");
    $searchTerm = "%$searchQuery%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($searchResults as &$user) {
        $userId = $user['id'];

        // Obtener la cantidad de seguidores
        $stmt = $conn->prepare("SELECT COUNT(*) AS followers FROM seguidos WHERE usuario = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($followers);
        $stmt->fetch();
        $stmt->close();
        $user['followers'] = $followers;

        // Obtener la cantidad de seguidos
        $stmt = $conn->prepare("SELECT COUNT(*) AS following FROM seguidos WHERE seguidor = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($following);
        $stmt->fetch();
        $stmt->close();
        $user['following'] = $following;

        // Verificar si el usuario actual sigue al usuario encontrado
        $stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE usuario = ? AND seguidor = ?");
        $stmt->bind_param("ii", $userId, $_SESSION['id_user']);
        $stmt->execute();
        $stmt->bind_result($is_following_count);
        $stmt->fetch();
        $user['is_following'] = $is_following_count > 0;
        $stmt->close();
    }

    $conn->close();

    echo json_encode($searchResults);
} else {
    echo json_encode([]);
}
?>
