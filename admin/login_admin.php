

<?php

session_start();



// Jika admin sudah login, langsung ke dashboard.

if (isset($_SESSION["id_admin"])) {

    header("Location: dashboard_admin.php");

    exit;

}



$error = "";



if (isset($_GET["error"])) {

    if ($_GET["error"] === "kosong") {

        $error = "Username dan password wajib diisi.";

    } elseif ($_GET["error"] === "login_gagal") {

        $error = "Username atau password salah.";

    }

}

?>



<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">



    <title>Administrator - ExamHub</title>



    <link

        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"

        rel="stylesheet"

    >



    <style>

        body {

            background-color: #f5f7fb;

        }



        .login-card {

            width: 100%;

            max-width: 400px;

            border: none;

            border-radius: 14px;

        }



        .login-icon {

            font-size: 42px;

        }



        .form-control {

            padding: 11px 12px;

        }



        .btn-login {

            padding: 11px;

        }

    </style>



    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">

</head>



<body>



<div class="container min-vh-100 d-flex justify-content-center align-items-center py-4">



    <div class="card login-card shadow-sm">

        <div class="card-body p-4">



            <div class="text-center mb-4">

                <div class="login-icon mb-2">🔐</div>



                <h3 class="fw-bold mb-2">Administrator</h3>



                <p class="text-muted mb-0">

                    Masuk ke panel administrasi

                </p>

            </div>



            <?php if ($error !== ""): ?>

                <div class="alert alert-danger" role="alert">

                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>

                </div>

            <?php endif; ?>



            <form action="proses_login_admin.php" method="POST">



                <div class="mb-3">

                    <label for="username" class="form-label">

                        Username Admin

                    </label>



                    <input

                        type="text"

                        class="form-control"

                        id="username"

                        name="username"

                        placeholder="Masukkan username"

                        autocomplete="username"

                        required

                        autofocus

                    >

                </div>



                <div class="mb-4">

                    <label for="password" class="form-label">

                        Password

                    </label>



                    <input

                        type="password"

                        class="form-control"

                        id="password"

                        name="password"

                        placeholder="Masukkan password"

                        autocomplete="current-password"

                        required

                    >

                </div>



                <button

                    type="submit"

                    class="btn btn-dark btn-login w-100"

                >

                    MASUK KE ADMIN

                </button>



            </form>



            <!-- Tombol Keluar menuju halaman login siswa -->

            <a

                href="../siswa/login_siswa.php"

                class="btn btn-outline-secondary w-100 mt-2"

            >

                Keluar

            </a>



        </div>

    </div>



</div>



</body>

</html>



