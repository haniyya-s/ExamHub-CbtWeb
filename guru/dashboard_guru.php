<?php

session_start();



require_once __DIR__ . '/../koneksi.php';



// Pastikan guru sudah login

if (!isset($_SESSION['id_guru'])) {

    header("Location: login_guru.php");

    exit;

}



// Amankan nama guru

$namaGuru = htmlspecialchars(

    $_SESSION['nama_guru'] ?? 'Guru',

    ENT_QUOTES,

    'UTF-8'

);

?>



<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">



    <title>Dashboard Guru - ExamHub</title>



    <link

        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"

        rel="stylesheet"

    >

    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">

</head>



<body>



<nav class="navbar navbar-dark">

    <div class="container">

        <span class="navbar-brand mb-0">ExamHub Guru</span>



        <a href="logout_guru.php" class="btn btn-outline-light btn-sm">

            Keluar

        </a>

    </div>

</nav>



<main class="container py-4 py-md-5">



    <section class="page-hero mb-5">

        <span class="eyebrow">✦ Teacher Space</span>

        <h1 class="mt-3 mb-2">Siap menginspirasi, <?= $namaGuru ?>?</h1>

        <p class="hero-copy mb-0">

            Rancang pengalaman ujian yang seru, siapkan soal terbaik,

            lalu lihat perkembangan setiap siswa dari sini.

        </p>

    </section>



    <section aria-labelledby="aksi-title">

        <p class="text-uppercase fw-bold text-muted small mb-1">

            Pilih aktivitas

        </p>

        <h2 id="aksi-title" class="h4 mb-3">Apa agenda hari ini?</h2>



        <div class="row g-3">

            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">🗓️</div>

                        <h3 class="h5">Kelola Ujian</h3>

                        <p class="card-text">

                            Susun jadwal dan pastikan ujian siap tepat waktu.

                        </p>

                        <a href="ujian_guru.php" class="btn btn-primary">

                            Lihat semua ujian →

                        </a>

                    </div>

                </div>

            </div>



            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">💡</div>

                        <h3 class="h5">Kelola Soal</h3>

                        <p class="card-text">

                            Bangun bank soal yang menantang dan berkualitas.

                        </p>

                        <a href="soal_guru.php" class="btn btn-primary">

                            Siapkan soal →

                        </a>

                    </div>

                </div>

            </div>



            <div class="col-md-6 col-lg-4">

                <div class="card menu-card h-100">

                    <div class="card-body">

                        <div class="menu-icon" aria-hidden="true">🏆</div>

                        <h3 class="h5">Nilai Siswa</h3>

                        <p class="card-text">

                            Pantau hasil dan apresiasi progres belajar siswa.

                        </p>

                        <a href="nilai_guru.php" class="btn btn-primary">

                            Jelajahi hasil →

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <aside class="card mt-4">

        <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">

            <div class="menu-icon mb-0 flex-shrink-0" aria-hidden="true">🌱</div>

            <div>

                <h2 class="h6 mb-1">Tip kecil, dampak besar</h2>

                <p class="text-muted mb-0">

                    Gunakan kalimat soal yang ringkas agar siswa bisa fokus

                    menunjukkan pemahamannya, bukan menebak maksud pertanyaan.

                </p>

            </div>

        </div>

    </aside>



</main>



</body>

</html>

