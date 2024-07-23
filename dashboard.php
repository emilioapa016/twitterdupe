<?php
require './php/config.php';
require './php/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="icon.ico" type="image/x-icon">
    <link href="./assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/fontsawesome/css/all.min.css" rel="stylesheet">
    <link href="./assets/sweetalert/css/sweetalert2.min.css" rel="stylesheet" />
    <title>Dashboard</title>
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

        .tweet-card {
            max-width: 80%;
            margin: 20px auto;
        }

        .char-counter {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .username {
            font-size: 0.75em;
            font-weight: normal;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include 'aside.php'; ?>
        <main>
            <h1 class="text-center mb-4">Inicio</h1>
            <div class="card tweet-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">¿Qué estás pensando?</h5>
                    <form id="tweet-form">
                        <div class="mb-3">
                            <textarea id="tweet-text" class="form-control" rows="2" maxlength="280"
                                placeholder="Escribe tu tweet aquí..."></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small id="char-count" class="char-counter">0/280</small>
                            <button type="submit" class="btn btn-primary">Publicar</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="tweets-container"></div>
            <div id="load-more-container" class="text-center mt-4">
                <button id="load-more" class="btn btn-secondary">Cargar más publicaciones</button>
                <p id="no-more-tweets" class="d-none">¡Ya has visto todas las publicaciones!</p>
            </div>
        </main>
    </div>
    <script src="./assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/sweetalert/js/sweetalert2.all.min.js"></script>
    <script>
        const tweetText = document.getElementById('tweet-text');
        const charCount = document.getElementById('char-count');
        const tweetsContainer = document.getElementById('tweets-container');
        const loadMoreButton = document.getElementById('load-more');
        const noMoreTweetsText = document.getElementById('no-more-tweets');
        let offset = 0;
        const limit = 5;

        tweetText.addEventListener('input', function () {
            const remaining = tweetText.value.length;
            charCount.textContent = `${remaining}/280`;
        });

        document.getElementById('tweet-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const tweetContent = tweetText.value;

            if (!tweetContent) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "El contenido del tuit no puede estar vacío.",
                });
                return;
            }

            if (tweetContent.length > 280) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "El tuit no puede exceder los 280 caracteres.",
                });
                return;
            }

            const formData = new FormData();
            formData.append('content', tweetContent);

            fetch('./backend/publicar.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: "success",
                            title: "Tuit publicado",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        }).then(() => {
                            charCount.textContent = `0/280`;
                            tweetText.value = '';
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

        function cargarTuits(limit, offset) {
            fetch(`./backend/obtener_tuits.php?limit=${limit}&offset=${offset}`)
                .then(response => response.json())
                .then(data => {
                    if (data.tuits.length > 0) {
                        data.tuits.forEach(tuit => {
                            const tweetCard = document.createElement('div');
                            tweetCard.className = 'card tweet-card';
                            tweetCard.innerHTML = `
                                <div class="card-body">
                                    <h5 class="card-title">
                                        ${tuit.name} <span class="username">@${tuit.username}</span>
                                    </h5>
                                    <p class="card-text">${tuit.contenido}</p>
                                    <p class="card-text"><small class="text-muted">${tuit.created_at}</small></p>
                                </div>
                            `;
                            tweetsContainer.appendChild(tweetCard);
                        });
                    } else {
                        loadMoreButton.classList.add('d-none');
                        noMoreTweetsText.classList.remove('d-none');
                    }

                    if (data.tuits.length < limit) {
                        loadMoreButton.classList.add('d-none');
                        noMoreTweetsText.classList.remove('d-none');
                    } else {
                        loadMoreButton.classList.remove('d-none');
                        noMoreTweetsText.classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            cargarTuits(limit, offset);
        });

        loadMoreButton.addEventListener('click', function () {
            offset += limit;
            cargarTuits(limit, offset);
        });
    </script>
</body>

</html>
