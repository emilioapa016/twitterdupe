<?php

$conn = connectDatabase();
$username = $_SESSION['username'];

// Obtener la información del usuario
$stmt = $conn->prepare("SELECT id, name, username FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $name, $user);
$stmt->fetch();
$stmt->close();

// Obtener el contador de seguidores
$stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE usuario = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($followers_count);
$stmt->fetch();
$stmt->close();

// Obtener el contador de seguidos
$stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE seguidor = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($following_count);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<aside class="bg-light border-end d-flex flex-column" id="sidebar">
    <div class="d-flex flex-column align-items-center p-3 flex-grow-1">
        <a href="dashboard.php" class="mb-3">
            <img src="./assets/images/twitter-1_icon-icons.com_71061.svg" alt="Logo" width="50">
        </a>
        <div class="text-center mb-3">
            <h4 class="fw-bold mb-0"><?php echo $name; ?></h4>
            <p class="text-muted mb-0">@<?php echo $user; ?></p>
            <div class="d-flex justify-content-center">
                <p class="text-muted mb-0 me-3">Seguidores: <?php echo $followers_count; ?></p>
                <p class="text-muted mb-0">Siguiendo: <?php echo $following_count; ?></p>
            </div>
        </div>
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link active d-flex align-items-center" href="dashboard.php">
                    <span class="d-flex align-items-center justify-content-center" style="width: 30px;">
                        <i class="fa-solid fa-house"></i>
                    </span>
                    <span class="ms-2">Inicio</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="./<?php echo $user; ?>">
                    <span class="d-flex align-items-center justify-content-center" style="width: 30px;">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="ms-2">Perfil</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="search.php">
                    <span class="d-flex align-items-center justify-content-center" style="width: 30px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <span class="ms-2">Buscar usuario</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="p-3">
        <a class="nav-link text-danger d-flex align-items-center" href="./php/logout.php">
            <span class="d-flex align-items-center justify-content-center" style="width: 30px;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
            <span class="ms-2">Cerrar sesión</span>
        </a>
    </div>
</aside>
