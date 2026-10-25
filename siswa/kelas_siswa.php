<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Pastikan siswa sudah login
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['nama']) ||
    !isset($_SESSION['id_kelas'])
) {
    header('Location: login_siswa.php');
    exit;
}

// 2. Data siswa
$nama = htmlspecialchars(
    $_SESSION['nama'],
    ENT_QUOTES,
    'UTF-8'
);

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];

// 3. Ambil ujian yang terhubung dengan kelas siswa
$query = "
    SELECT DISTINCT
        u.id_ujian,
        u.nama_ujian,
        u.durasi_waktu,
        u.tanggal_ujian
    FROM ujian u
    INNER JOIN ujian_kelas uk
        ON u.id_ujian = uk.id_ujian
    WHERE uk.id_kelas = ?
    ORDER BY u.tanggal_ujian ASC
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil daftar ujian.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_kelas);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$jumlahUjian = mysqli_num_rows($result);

// 4. Waktu saat ini
$sekarang = new DateTime();

// 5. Ambil daftar ujian yang sudah diselesaikan siswa
$stmtSelesai = mysqli_prepare(
    $conn,
    "SELECT id_ujian
     FROM ujian_selesai
     WHERE id_siswa = ?"
);

if (!$stmtSelesai) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Terjadi kesalahan saat memeriksa status ujian.');
}

mysqli_stmt_bind_param($stmtSelesai, 'i', $id_siswa);
mysqli_stmt_execute($stmtSelesai);

$resultSelesai = mysqli_stmt_get_result($stmtSelesai);

$ujianSelesai = [];

while ($baris = mysqli_fetch_assoc($resultSelesai)) {
    $ujianSelesai[(int) $baris['id_ujian']] = true;
}

mysqli_stmt_close($stmtSelesai);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Daftar Ujian - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body>

<div class="container py-4 py-md-5">

    <section class="page-hero mb-4">
        <span class="eyebrow">✦ Student Lounge</span>

        <h1 class="mt-3 mb-2">
            Hai, <?= $nama ?>! 👋
        </h1>

        <p class="hero-copy mb-0">
            Tarik napas, fokus, dan tunjukkan yang sudah kamu pelajari.
            Semua jadwal ujianmu ada di sini.
        </p>
    </section>

    <!-- Daftar ujian -->
    <div class="card mt-4">

        <div class="card-header d-flex flex-column flex-md-row
                    align-items-md-center justify-content-between gap-2">

            <div>
                <p class="text-uppercase fw-bold text-muted small mb-1">
                    Your next move
                </p>

                <h2 class="h5 mb-0">
                    Jadwal dan daftar ujian
                </h2>
            </div>

            <span class="badge bg-light text-dark border">
                <?= $jumlahUjian ?> ujian tersedia
            </span>

        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Ujian</th>
                            <th>Tanggal dan Jam</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($jumlahUjian > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($ujian = mysqli_fetch_assoc($result)): ?>

                            <?php
                            $idUjian = (int) $ujian['id_ujian'];

                            $tanggal = new DateTime(
                                $ujian['tanggal_ujian']
                            );

                            $durasi = (int) $ujian['durasi_waktu'];

                            $waktuSelesai = clone $tanggal;
                            $waktuSelesai->modify(
                                "+{$durasi} minutes"
                            );

                            $sudahDikerjakan = isset(
                                $ujianSelesai[$idUjian]
                            );

                            if ($sudahDikerjakan) {
                                // Ujian sudah selesai dikerjakan.
                                // Siswa dapat membuka hasil tanpa mengulang.
                                $status = 'Sudah Dikerjakan';
                                $badge = 'bg-primary';

                            } elseif ($sekarang < $tanggal) {
                                $status = 'Belum dimulai';
                                $badge = 'bg-warning text-dark';

                            } elseif ($sekarang < $waktuSelesai) {
                                $status = 'Sedang berlangsung';
                                $badge = 'bg-success';

                            } else {
                                $status = 'Selesai';
                                $badge = 'bg-secondary';
                            }
                            ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $ujian['nama_ujian'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $tanggal->format('d-m-Y H:i') ?>
                                </td>

                                <td>
                                    <?= $durasi ?> menit
                                </td>

                                <td>
                                    <span class="badge <?= $badge ?>">
                                        <?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($sudahDikerjakan): ?>

                                        <!-- Ujian sudah dikerjakan:
                                             tampilkan tombol hasil -->
                                        <a
                                            href="hasil_siswa.php?id=<?= $idUjian ?>"
                                            class="btn btn-success btn-sm"
                                        >
                                            Lihat Hasil
                                        </a>

                                    <?php elseif (
                                        $sekarang >= $tanggal &&
                                        $sekarang < $waktuSelesai
                                    ): ?>

                                        <!-- Ujian masih berlangsung -->
                                        <a
                                            href="ujian_siswa.php?id=<?= $idUjian ?>"
                                            class="btn btn-primary btn-sm"
                                        >
                                            Mulai Tes
                                        </a>

                                    <?php elseif ($sekarang < $tanggal): ?>

                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            disabled
                                        >
                                            Belum Tersedia
                                        </button>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            disabled
                                        >
                                            Ujian Berakhir
                                        </button>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada ujian untuk kelas kamu.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Tombol keluar -->
    <div class="d-flex justify-content-between align-items-center mt-4">

        <a href="logout_siswa.php" class="btn btn-outline-danger">
            Keluar
        </a>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
```
