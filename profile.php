<?php
require './php/config.php';
require './php/verificar_sesion.php';

if (!isset($_GET['username'])) {
    die("Nombre de usuario no especificado.");
}

$username_visit = $_GET['username'];
$user_id_session = $_SESSION['id_user']; // ID del usuario en sesión

// Conectar a la base de datos
$conn = connectDatabase();

// Obtener información del usuario
$stmt = $conn->prepare("SELECT id, name, username, created_at FROM user WHERE username = ?");
$stmt->bind_param("s", $username_visit);
$stmt->execute();
$stmt->bind_result($user_id_visit, $name_visit, $user_visit, $created_at_visit);
$stmt->fetch();
$stmt->close();

// Verificar si el perfil es del usuario en sesión
$is_own_profile = ($user_id_session === $user_id_visit);

// Verificar si el usuario en sesión sigue al usuario visitado
$is_following = false;
if (!$is_own_profile) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE usuario = ? AND seguidor = ?");
    $stmt->bind_param("ii", $user_id_visit, $user_id_session);
    $stmt->execute();
    $stmt->bind_result($following_count);
    $stmt->fetch();
    $is_following = $following_count > 0;
    $stmt->close();
}

// Obtener el número de seguidores
$stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE usuario = ?");
$stmt->bind_param("i", $user_id_visit);
$stmt->execute();
$stmt->bind_result($followers_count);
$stmt->fetch();
$stmt->close();

// Obtener el número de seguidos
$stmt = $conn->prepare("SELECT COUNT(*) FROM seguidos WHERE seguidor = ?");
$stmt->bind_param("i", $user_id_visit);
$stmt->execute();
$stmt->bind_result($following_count);
$stmt->fetch();
$stmt->close();

// Obtener las publicaciones del usuario
$limit = 5;
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

$stmt = $conn->prepare("SELECT id, contenido, created_at FROM publicaciones WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id_visit, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$publications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Contar el total de publicaciones
$stmt = $conn->prepare("SELECT COUNT(*) FROM publicaciones WHERE user_id = ?");
$stmt->bind_param("i", $user_id_visit);
$stmt->execute();
$stmt->bind_result($total_publications);
$stmt->fetch();
$stmt->close();

// Obtener los seguidores y verificar si el usuario actual los sigue
$followers_stmt = $conn->prepare("
    SELECT u.username, u.name, 
           (SELECT COUNT(*) FROM seguidos s2 WHERE s2.usuario = u.id AND s2.seguidor = ?) AS is_following
    FROM seguidos s
    JOIN user u ON s.seguidor = u.id
    WHERE s.usuario = ?
    ORDER BY u.name DESC
    LIMIT ? OFFSET ?
");
$followers_stmt->bind_param("iiii", $_SESSION['id_user'], $user_id_visit, $limit, $offset);
$followers_stmt->execute();
$followers_result = $followers_stmt->get_result();
$followers = $followers_result->fetch_all(MYSQLI_ASSOC);
$followers_stmt->close();

// Obtener los seguidos
$following_stmt = $conn->prepare("
    SELECT u.username, u.name 
    FROM seguidos s 
    JOIN user u ON s.usuario = u.id 
    WHERE s.seguidor = ? 
    ORDER BY name DESC 
    LIMIT ? OFFSET ?
");
$following_stmt->bind_param("iii", $user_id_visit, $limit, $offset);
$following_stmt->execute();
$following_result = $following_stmt->get_result();
$following = $following_result->fetch_all(MYSQLI_ASSOC);
$following_stmt->close();

$conn->close();

// Definir el título de la página
$pageTitle = $name_visit ? "Perfil de " . htmlspecialchars($name_visit) : "Usuario no encontrado";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="icon.ico" type="image/x-icon">
    <link href="./assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/fontsawesome/css/all.min.css" rel="stylesheet">
    <title><?php echo $pageTitle; ?></title>
    <style>
        #sidebar {
            min-height: 100vh;
            width: 250px;
            position: fixed;
        }

        main {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .profile-card {
            max-width: 80%;
            margin: 20px auto;
        }

        .publication-card,
        .following-card,
        .followers-card {
            margin-bottom: 20px;
            max-height: calc(66vh - 20px);
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include 'aside.php'; ?>
        <main>
            <h1 class="text-center mb-4"><?php echo $pageTitle; ?></h1>
            <?php if (!$name_visit): ?>
                <div class="alert alert-warning text-center">
                    <p>Usuario no encontrado.</p>
                </div>
            <?php else: ?>
                <div class="card profile-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title"><?php echo htmlspecialchars($name_visit); ?> <span
                                        class="text-muted">@<?php echo htmlspecialchars($user_visit); ?></span></h5>
                                <div class="d-flex mb-2">
                                    <p class="text-muted mb-0 me-3">Seguidores:
                                        <?php echo htmlspecialchars(count($followers)); ?>
                                    </p>
                                    <p class="text-muted mb-0">Siguiendo: <?php echo htmlspecialchars(count($following)); ?>
                                    </p>
                                </div>
                                <p class="card-text">Miembro desde: <?php echo htmlspecialchars($created_at_visit); ?></p>
                            </div>
                            <div class="col-md-4 d-flex align-items-center justify-content-end">
                                <?php if (!$is_own_profile): ?>
                                    <?php if ($is_following): ?>
                                        <button class="btn btn-success" disabled>Siguiendo</button>
                                    <?php else: ?>
                                        <button class="btn btn-primary" id="boton-seguir">Seguir</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="card publication-card">
                            <div class="card-body">
                                <h4 class="card-title">Publicaciones</h4>
                                <?php if (empty($publications)): ?>
                                    <p>No hay publicaciones.</p>
                                <?php else: ?>
                                    <?php foreach ($publications as $publication): ?>
                                        <div class="mb-3">
                                            <p class="card-text"><?php echo htmlspecialchars($publication['contenido']); ?></p>
                                            <p class="text-muted"><?php echo htmlspecialchars($publication['created_at']); ?></p>
                                            <hr>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($total_publications > $offset + $limit): ?>
                                        <button id="loadMorePublications" class="btn btn-primary">Cargar más</button>
                                    <?php else: ?>
                                        <p>No hay más publicaciones.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card following-card">
                            <div class="card-body">
                                <h4 class="card-title">Seguidos</h4>
                                <?php if (empty($following)): ?>
                                    <p>No sigues a nadie.</p>
                                <?php else: ?>
                                    <?php foreach ($following as $user): ?>
                                        <div class="mb-3">
                                            <p class="card-text mb-0"><a
                                                    href="./<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['name']); ?></a>
                                            </p>
                                            <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                                            <hr>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($following_count > $offset + $limit): ?>
                                        <button id="loadMoreFollowing" class="btn btn-primary">Cargar más</button>
                                    <?php else: ?>
                                        <p>No hay más seguidos.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card followers-card">
                            <div class="card-body">
                                <h4 class="card-title">Seguidores</h4>
                                <?php if (empty($followers)): ?>
                                    <p>No tienes seguidores.</p>
                                <?php else: ?>
                                    <?php foreach ($followers as $user): ?>
                                        <div class="mb-3 d-flex align-items-center justify-content-between">
                                            <div>
                                                <p class="card-text mb-0"><a
                                                        href="./<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['name']); ?></a>
                                                </p>
                                                <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                                            </div>
                                            <?php if ($user['is_following'] == 0 && $_SESSION['username'] != $user['username']): ?>
                                                <button class="btn btn-primary follow-btn"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">Seguir</button>
                                            <?php endif; ?>
                                        </div>
                                        <hr>
                                    <?php endforeach; ?>
                                    <?php if ($followers_count > $offset + $limit): ?>
                                        <button id="loadMoreFollowers" class="btn btn-primary">Cargar más</button>
                                    <?php else: ?>
                                        <p>No hay más seguidores.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="./assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/sweetalert/js/sweetalert2.all.min.js"></script>
    <script>
        document.getElementById('loadMorePublications')?.addEventListener('click', function () {
            const currentUrl = new URL(window.location.href);
            const offset = currentUrl.searchParams.get('offset') || 0;
            const newOffset = parseInt(offset) + 5;
            currentUrl.searchParams.set('offset', newOffset);
            window.location.href = currentUrl.toString();
        });

        document.getElementById('loadMoreFollowing')?.addEventListener('click', function () {
            const currentUrl = new URL(window.location.href);
            const offset = currentUrl.searchParams.get('offsetFollowing') || 0;
            const newOffset = parseInt(offset) + 5;
            currentUrl.searchParams.set('offsetFollowing', newOffset);
            window.location.href = currentUrl.toString();
        });

        document.getElementById('loadMoreFollowers')?.addEventListener('click', function () {
            const currentUrl = new URL(window.location.href);
            const offset = currentUrl.searchParams.get('offsetFollowers') || 0;
            const newOffset = parseInt(offset) + 5;
            currentUrl.searchParams.set('offsetFollowers', newOffset);
            window.location.href = currentUrl.toString();
        });

        const followButton = document.getElementById('boton-seguir');
        if (followButton) {
            followButton.addEventListener('click', function (e) {
                e.preventDefault();
                const usernameToFollow = '<?php echo htmlspecialchars($username_visit); ?>';

                if (!usernameToFollow) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "El nombre de usuario no puede estar vacío.",
                    });
                    return;
                }

                const formData = new FormData();
                formData.append('username', usernameToFollow);

                fetch('./backend/seguir.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: "success",
                                title: "Genial!, ahora lo sigues",
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                followButton.style.display = 'none'; // Esconder el botón
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Ha ocurrido un error. Por favor, inténtalo de nuevo más tarde.",
                        });
                    });
            });
        }

        document.querySelectorAll('.follow-btn').forEach(button => {
            button.addEventListener('click', function () {
                const usernameToFollow = this.getAttribute('data-username');

                const formData = new FormData();
                formData.append('username', usernameToFollow);

                fetch('./backend/seguir.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: "success",
                                title: "Genial!, ahora lo sigues",
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                this.style.display = 'none';
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Ha ocurrido un error. Por favor, inténtalo de nuevo más tarde.",
                        });
                    });
            });
        });


    </script>
</body>

</html>