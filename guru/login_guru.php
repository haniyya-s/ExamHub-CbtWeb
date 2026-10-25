

<?php

session_start();



// Jika guru sudah login, arahkan ke dashboard.

if (isset($_SESSION['id_guru'])) {

    header("Location: dashboard_guru.php");

    exit;

}



// Jika siswa sudah login, arahkan ke halaman kelas.

if (isset($_SESSION['id_siswa'])) {

    header("Location: ../siswa/kelas_siswa.php");

    exit;

}



$error = "";



if (isset($_GET['error'])) {

    if ($_GET['error'] === 'kosong') {

        $error = "Username dan password wajib diisi.";

    } elseif ($_GET['error'] === 'gagal') {

        $error = "Username atau password salah.";

    }

}

?>



<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <title>Login Guru - ExamHub</title>



    <link

        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"

        rel="stylesheet"

    >



    <style>

        body {

            background-color: #f5f7fb;

        }



        .login-card {

            width: 500px !important;

            max-width: calc(100vw - 32px) !important;

            border-radius: 14px;

            overflow: hidden;

            box-sizing: border-box;

        }



        .login-tabs {

            display: flex;

            border-bottom: 1px solid #dee2e6;

        }



        .login-tabs a {

            width: 50%;

            padding: 15px;

            text-align: center;

            text-decoration: none;

            color: #6c757d;

            font-weight: 500;

            border-bottom: 3px solid transparent;

            margin-bottom: -1px;

        }



        .login-tabs a.active {

            color: #0d6efd;

            border-bottom-color: #0d6efd;

        }



        .login-tabs a:hover {

            color: #0d6efd;

        }



        .login-icon {

            font-size: 42px;

            margin-bottom: 8px;

        }



        .admin-link {

            display: inline-block;

            margin-top: 14px;

            font-size: 12px;

            color: #8a8f98;

            text-decoration: none;

        }



        .admin-link:hover {

            color: #0d6efd;

            text-decoration: underline;

        }

    </style>

    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">

</head>



<body>



<div class="container min-vh-100 d-flex justify-content-center align-items-center py-4">



    <div class="text-center">



        <div class="card login-card shadow-sm border-0 text-start">



            <!-- TAB LOGIN SISWA DAN GURU -->

            <div class="login-tabs">

                <a href="../siswa/login_siswa.php">

                    Siswa

                </a>



                <a href="login_guru.php" class="active">

                    Guru

                </a>

            </div>



            <div class="card-body p-4">



                <!-- HEADER LOGIN -->

                <div class="text-center mb-4">

                    <div class="login-icon">🎓</div>



                    <h3 class="fw-bold mb-2">ExamHub</h3>



                    <p class="text-muted mb-0">

                        Silakan masuk menggunakan akun Anda

                    </p>

                </div>



                <!-- PESAN ERROR -->

                <?php if ($error !== ""): ?>

                    <div class="alert alert-danger" role="alert">

                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

                    </div>

                <?php endif; ?>



                <!-- FORM LOGIN GURU -->

                <form action="proses_login_guru.php" method="POST">



                    <div class="mb-3">

                        <label for="username" class="form-label">

                            Username

                        </label>



                        <input

                            type="text"

                            class="form-control"

                            id="username"

                            name="username"

                            placeholder="Masukkan username"

                            autocomplete="username"

                            required

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

                        class="btn btn-primary w-100 py-2"

                    >

                        MASUK SEBAGAI GURU

                    </button>



                </form>



            </div>

        </div>



        <!-- TAUTAN ADMINISTRATOR DI BAWAH CARD -->

        <div>

            <a href="../admin/login_admin.php" class="admin-link">

                Administrator

            </a>

        </div>



    </div>



</div>



</body>

</html>



