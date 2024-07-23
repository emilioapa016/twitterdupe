<?php
require './php/config.php';
require './php/verificar_sesion.php';

$pageTitle = "Buscar Usuarios";
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

        .search-card {
            max-width: 80%;
            margin: 20px auto;
        }

        .result-card {
            max-width: 80%;
            margin: 20px auto;
        }

        .no-results-card {
            max-width: 80%;
            margin: 20px auto;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include 'aside.php'; ?>
        <main>
            <h1 class="text-center mb-4"><?php echo $pageTitle; ?></h1>
            <div class="card search-card">
                <div class="card-body">
                    <form id="searchForm">
                        <div class="input-group">
                            <input type="text" id="query" class="form-control" placeholder="Buscar usuarios por nombre de usuario..." required>
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="searchResults"></div>
        </main>
    </div>
    <script src="./assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/sweetalert/js/sweetalert2.all.min.js"></script>
    <script>
        document.getElementById('searchForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const query = document.getElementById('query').value;

            fetch(`./backend/buscar_usuario.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const searchResults = document.getElementById('searchResults');
                    searchResults.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(user => {
                            const card = document.createElement('div');
                            card.className = 'card result-card';
                            card.innerHTML = `
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div>
                                                <h5 class="card-title"><a class="me-3" href="./${user.username}">${user.name}</a><span class="text-muted" style="font-size: 0.75em;">@${user.username}</span></h5>
                                            </div>
                                            <p class="card-text text-muted">Seguidores: ${user.followers}  Seguidos: ${user.following}</p>
                                            <p class="card-text text-muted">Miembro desde: ${user.created_at}</p>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center justify-content-end">
                                            <button class="btn ${user.is_following ? 'btn-success' : 'btn-primary'} follow-btn" data-username="${user.username}" ${user.is_following ? 'disabled' : ''}>
                                                ${user.is_following ? 'Siguiendo' : 'Seguir'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                            searchResults.appendChild(card);
                        });

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
                                                window.location.href = './'+usernameToFollow;
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
                    } else {
                        const noResultsCard = document.createElement('div');
                        noResultsCard.className = 'card no-results-card';
                        noResultsCard.innerHTML = `
                            <div class="card-body text-center">
                                <p>No se encontraron coincidencias.</p>
                            </div>
                        `;
                        searchResults.appendChild(noResultsCard);
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
    </script>
</body>

</html>
