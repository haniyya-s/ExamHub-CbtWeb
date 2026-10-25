<?php

session_start();



// Pastikan Admin sudah login

if (!isset($_SESSION["id_admin"])) {

    header("Location: login_admin.php");

    exit;

}



require_once __DIR__ . "/../koneksi.php";



// Fungsi untuk mengamankan teks HTML

function h($value) {

    return htmlspecialchars(

        (string) $value,

        ENT_QUOTES,

        "UTF-8"

    );

}



// Menghitung jumlah data

$total_siswa = (int) $conn->query(

    "SELECT COUNT(*) AS total FROM siswa"

)->fetch_assoc()["total"];



$total_guru = (int) $conn->query(

    "SELECT COUNT(*) AS total FROM guru"

)->fetch_assoc()["total"];



$total_kelas = (int) $conn->query(

    "SELECT COUNT(*) AS total FROM kelas"

)->fetch_assoc()["total"];



$total_ujian = (int) $conn->query(

    "SELECT COUNT(*) AS total FROM ujian"

)->fetch_assoc()["total"];



// Nama Admin

$nama_admin = $_SESSION["nama_admin"] ?? "Administrator";

?>



<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <title>Dashboard Admin - ExamHub</title>



    <link

        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"

        rel="stylesheet"

    >

    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">

</head>



<body>



<nav class="navbar navbar-dark">

    <div class="container">

        <span class="navbar-brand fw-bold">ExamHub Admin</span>



        <a href="logout_admin.php" class="btn btn-outline-light btn-sm">

            Keluar

        </a>

    </div>

</nav>



<main class="container py-4 py-md-5">



    <section class="page-hero mb-4">

        <span class="eyebrow">⚡ Control Center</span>

        <h1 class="mt-3 mb-2">

            Halo, <?= h($nama_admin) ?>!

        </h1>

        <p class="hero-copy mb-0">

            Semua aktivitas sekolah ada di satu tempat. Pantau data,

            atur ujian, dan buat pengalaman belajar berjalan mulus.

        </p>

    </section>



    <section aria-labelledby="ringkasan-title" class="mb-5">

        <div class="d-flex align-items-end justify-content-between mb-3">

            <div>

                <p class="text-uppercase fw-bold text-muted small mb-1">

                    Snapshot hari ini

                </p>

                <h2 id="ringkasan-title" class="h4 mb-0">Ringkasan sistem</h2>

            </div>

        </div>



        <div class="row g-3">

            <div class="col-6 col-lg-3">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="stat-icon" aria-hidden="true">🧑‍🎓</div>

                        <p class="stat-value"><?= $total_siswa ?></p>

                        <p class="stat-label">Siswa aktif</p>

                    </div>

                </div>

            </div>



            <div class="col-6 col-lg-3">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="stat-icon" aria-hidden="true">🧑‍🏫</div>

                        <p class="stat-value"><?= $total_guru ?></p>

                        <p class="stat-label">Guru terdaftar</p>

                    </div>

                </div>

            </div>



            <div class="col-6 col-lg-3">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="stat-icon" aria-hidden="true">🏫</div>

                        <p class="stat-value"><?= $total_kelas ?></p>

                        <p class="stat-label">Kelas belajar</p>

                    </div>

                </div>

            </div>



            <div class="col-6 col-lg-3">

                <div class="card stat-card h-100">

                    <div class="card-body">

                        <div class="stat-icon" aria-hidden="true">📝</div>

                        <p class="stat-value"><?= $total_ujian ?></p>

                        <p class="stat-label">Ujian dibuat</p>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <section aria-labelledby="menu-title">

        <p class="text-uppercase fw-bold text-muted small mb-1">

            Mau mengerjakan apa?

        </p>

        <h2 id="menu-title" class="h4 mb-3">Jelajahi ruang kelola</h2>



        <div class="row g-3">

            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">🎒</div>

                        <h3 class="h5">Data Siswa</h3>

                        <p class="card-text">

                            Temukan, tambah, dan rapikan seluruh akun siswa.

                        </p>

                        <a href="siswa_admin.php" class="btn btn-primary">

                            Buka data siswa →

                        </a>

                    </div>

                </div>

            </div>



            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">✨</div>

                        <h3 class="h5">Data Guru</h3>

                        <p class="card-text">

                            Kelola profil dan akses para pengajar.

                        </p>

                        <a href="guru_admin.php" class="btn btn-primary">

                            Buka data guru →

                        </a>

                    </div>

                </div>

            </div>



            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">🪴</div>

                        <h3 class="h5">Data Kelas</h3>

                        <p class="card-text">

                            Susun ruang belajar agar semua siswa tepat sasaran.

                        </p>

                        <a href="kelas_admin.php" class="btn btn-primary">

                            Atur kelas →

                        </a>

                    </div>

                </div>

            </div>



            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">🚀</div>

                        <h3 class="h5">Data Ujian</h3>

                        <p class="card-text">

                            Atur jadwal, durasi, dan kelas peserta ujian.

                        </p>

                        <a href="ujian_admin.php" class="btn btn-primary">

                            Atur ujian →

                        </a>

                    </div>

                </div>

            </div>



            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">📈</div>

                        <h3 class="h5">Nilai Siswa</h3>

                        <p class="card-text">

                            Lihat pencapaian siswa dari semua kelas dan guru.

                        </p>

                        <a href="nilai_admin.php" class="btn btn-primary">

                            Lihat nilai →

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>



</main>



</body>

</html>

