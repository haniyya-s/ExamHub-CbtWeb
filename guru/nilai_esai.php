
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Pastikan guru sudah login
if (!isset($_SESSION['id_guru'])) {
    header('Location: login_guru.php');
    exit;
}

$id_guru = (int) $_SESSION['id_guru'];

// 2. Buat token keamanan form
if (empty($_SESSION['csrf_nilai_esai'])) {
    $_SESSION['csrf_nilai_esai'] = bin2hex(random_bytes(32));
}

$pesan = '';
$jenis_pesan = '';

// 3. Proses penyimpanan nilai esai
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $id_jawaban = filter_input(INPUT_POST, 'id_jawaban', FILTER_VALIDATE_INT);
    $nilai_input = trim($_POST['nilai_esai'] ?? '');

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['csrf_nilai_esai'], $csrf)
    ) {
        $pesan = 'Permintaan tidak valid. Silakan muat ulang halaman.';
        $jenis_pesan = 'danger';
    } elseif (!$id_jawaban || $nilai_input === '' || !is_numeric($nilai_input)) {
        $pesan = 'Data nilai tidak valid.';
        $jenis_pesan = 'danger';
    } elseif ((float) $nilai_input < 0 || (float) $nilai_input > 100) {
        $pesan = 'Nilai harus berada di antara 0 sampai 100.';
        $jenis_pesan = 'danger';
    } else {
        $nilai = (float) $nilai_input;

        // Pastikan jawaban ini berasal dari ujian milik guru yang login
        $cek = mysqli_prepare($conn, "
            SELECT j.id_jawaban
            FROM jawaban_siswa j
            INNER JOIN ujian u
                ON u.id_ujian = j.id_ujian
            INNER JOIN soal so
                ON so.id_soal = j.id_soal
                AND so.id_ujian = j.id_ujian
            WHERE j.id_jawaban = ?
              AND u.id_guru = ?
              AND so.tipe_soal = 'esai'
            LIMIT 1
        ");

        if (!$cek) {
            $pesan = 'Terjadi kesalahan saat memeriksa jawaban.';
            $jenis_pesan = 'danger';
        } else {
            mysqli_stmt_bind_param($cek, 'ii', $id_jawaban, $id_guru);
            mysqli_stmt_execute($cek);
            $hasil_cek = mysqli_stmt_get_result($cek);
            $jawaban_valid = mysqli_fetch_assoc($hasil_cek);
            mysqli_stmt_close($cek);

            if (!$jawaban_valid) {
                $pesan = 'Jawaban tidak ditemukan atau bukan milik ujian Anda.';
                $jenis_pesan = 'danger';
            } else {
                $update = mysqli_prepare($conn, "
                    UPDATE jawaban_siswa
                    SET nilai_esai = ?
                    WHERE id_jawaban = ?
                ");

                if (!$update) {
                    $pesan = 'Nilai gagal disiapkan untuk disimpan.';
                    $jenis_pesan = 'danger';
                } else {
                    mysqli_stmt_bind_param(
                        $update,
                        'di',
                        $nilai,
                        $id_jawaban
                    );

                    if (mysqli_stmt_execute($update)) {
                        $pesan = 'Nilai esai berhasil disimpan.';
                        $jenis_pesan = 'success';
                    } else {
                        $pesan = 'Nilai esai gagal disimpan.';
                        $jenis_pesan = 'danger';
                    }

                    mysqli_stmt_close($update);
                }
            }
        }
    }
}

// 4. Ambil seluruh jawaban esai untuk ujian milik guru
$query = "
    SELECT
        j.id_jawaban,
        j.nilai_esai,
        j.jawaban_terpilih,
        s.nama_siswa,
        u.id_ujian,
        u.nama_ujian,
        so.id_soal,
        so.teks_soal
    FROM jawaban_siswa j
    INNER JOIN siswa s
        ON s.id_siswa = j.id_siswa
    INNER JOIN ujian u
        ON u.id_ujian = j.id_ujian
    INNER JOIN soal so
        ON so.id_soal = j.id_soal
        AND so.id_ujian = j.id_ujian
    WHERE u.id_guru = ?
      AND so.tipe_soal = 'esai'
    ORDER BY
        u.nama_ujian ASC,
        s.nama_siswa ASC,
        so.id_soal ASC
";

$stmt = mysqli_prepare($conn, $query);
$daftar_jawaban = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id_guru);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);

    while ($baris = mysqli_fetch_assoc($hasil)) {
        $daftar_jawaban[] = $baris;
    }

    mysqli_stmt_close($stmt);
}

function e($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Esai</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Penilaian Jawaban Esai</h2>
            <p class="text-muted mb-0">
                Periksa jawaban siswa dan berikan nilai 0–100.
            </p>
        </div>

        <a href="nilai_siswa.php" class="btn btn-outline-secondary">
            Kembali ke Nilai Siswa
        </a>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= e($jenis_pesan) ?> alert-dismissible fade show">
            <?= e($pesan) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($daftar_jawaban)): ?>
        <div class="alert alert-info">
            Belum ada jawaban esai siswa yang dapat diperiksa.
        </div>
    <?php else: ?>

        <div class="row g-4">
            <?php foreach ($daftar_jawaban as $jawaban): ?>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">
                                        <?= e($jawaban['nama_siswa']) ?>
                                    </h5>
                                    <div class="text-muted">
                                        Ujian: <?= e($jawaban['nama_ujian']) ?>
                                    </div>
                                </div>

                                <div>
                                    <?php if ($jawaban['nilai_esai'] === null): ?>
                                        <span class="badge text-bg-warning">
                                            Belum Dinilai
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">
                                            Nilai: <?= e($jawaban['nilai_esai']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold mb-1">
                                    Soal Esai
                                </div>
                                <div class="p-3 bg-light border rounded">
                                    <?= nl2br(e($jawaban['teks_soal'])) ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold mb-1">
                                    Jawaban Siswa
                                </div>
                                <div class="p-3 border rounded">
                                    <?php if (trim($jawaban['jawaban_terpilih']) === ''): ?>
                                        <span class="text-muted">
                                            Siswa tidak mengisi jawaban.
                                        </span>
                                    <?php else: ?>
                                        <?= nl2br(e($jawaban['jawaban_terpilih'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form method="POST" class="row g-3 align-items-end">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($_SESSION['csrf_nilai_esai']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id_jawaban"
                                    value="<?= (int) $jawaban['id_jawaban'] ?>"
                                >

                                <div class="col-sm-4 col-md-3">
                                    <label class="form-label fw-semibold">
                                        Nilai Esai (0–100)
                                    </label>
                                    <input
                                        type="number"
                                        name="nilai_esai"
                                        class="form-control"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        required
                                        value="<?= $jawaban['nilai_esai'] === null
                                            ? ''
                                            : e($jawaban['nilai_esai']) ?>"
                                        placeholder="Contoh: 85"
                                    >
                                </div>

                                <div class="col-sm-auto">
                                    <button type="submit" class="btn btn-primary">
                                        Simpan Nilai
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
