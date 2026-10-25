
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Periksa login siswa
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['id_kelas'])
) {
    header("Location: login_siswa.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];

$id_ujian = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_ujian || $id_ujian < 1) {
    header("Location: kelas_siswa.php");
    exit;
}

// 2. Pastikan ujian terdaftar untuk kelas siswa
$stmt = mysqli_prepare(
    $conn,
    "SELECT u.id_ujian, u.nama_ujian
     FROM ujian u
     INNER JOIN ujian_kelas uk
        ON uk.id_ujian = u.id_ujian
     WHERE u.id_ujian = ?
       AND uk.id_kelas = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal mengambil informasi ujian.');
}

mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_kelas);
mysqli_stmt_execute($stmt);

$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujian) {
    header("Location: kelas_siswa.php");
    exit;
}

// 3. Pastikan siswa benar-benar sudah menyelesaikan ujian
$stmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM ujian_selesai
     WHERE id_siswa = ?
       AND id_ujian = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal memeriksa status ujian.');
}

mysqli_stmt_bind_param($stmt, "ii", $id_siswa, $id_ujian);
mysqli_stmt_execute($stmt);

$ujian_selesai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujian_selesai) {
    header("Location: kelas_siswa.php");
    exit;
}

// 4. Ambil seluruh soal dan jawaban siswa
$query = "
    SELECT
        so.id_soal,
        so.tipe_soal,
        so.jawaban_benar,
        j.jawaban_terpilih,
        j.nilai_esai
    FROM soal so
    LEFT JOIN jawaban_siswa j
        ON j.id_soal = so.id_soal
        AND j.id_ujian = so.id_ujian
        AND j.id_siswa = ?
    WHERE so.id_ujian = ?
    ORDER BY so.id_soal ASC
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal mengambil jawaban siswa.');
}

mysqli_stmt_bind_param($stmt, "ii", $id_siswa, $id_ujian);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// 5. Hitung nilai pilihan ganda dan esai
$total = 0;
$total_pg = 0;
$benar_pg = 0;
$total_esai = 0;
$esai_dinilai = 0;
$total_poin = 0;

while ($soal = mysqli_fetch_assoc($result)) {
    $total++;

    $jawaban = trim(
        (string) ($soal['jawaban_terpilih'] ?? '')
    );

    if ($soal['tipe_soal'] === 'pilihan_ganda') {
        $total_pg++;

        $kunci = strtoupper(
            trim((string) $soal['jawaban_benar'])
        );

        if (
            $jawaban !== '' &&
            strtoupper($jawaban) === $kunci
        ) {
            $benar_pg++;
            $total_poin += 100;
        }
    } elseif ($soal['tipe_soal'] === 'esai') {
        $total_esai++;

        if ($soal['nilai_esai'] !== null) {
            $esai_dinilai++;
            $total_poin += (float) $soal['nilai_esai'];
        }
    }
}

mysqli_stmt_close($stmt);

// 6. Tentukan apakah penilaian sudah lengkap
$penilaian_lengkap = (
    $total > 0 &&
    $esai_dinilai === $total_esai
);

$nilai = $penilaian_lengkap
    ? round($total_poin / $total, 2)
    : null;

// 7. Amankan nama ujian
$namaUjian = htmlspecialchars(
    $ujian['nama_ujian'],
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Hasil Ujian - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex
            justify-content-center align-items-center py-5">

    <div class="card shadow-sm border-0 w-100"
         style="max-width: 500px;">

        <div class="card-body text-center p-4 p-md-5">

            <h3 class="mb-2">Ujian Selesai!</h3>

            <p class="text-muted mb-4">
                <?= $namaUjian ?>
            </p>

            <hr>

            <p class="mb-1">Jawaban pilihan ganda benar</p>

            <h4>
                <?= $benar_pg ?> dari <?= $total_pg ?> soal
            </h4>

            <?php if ($total_esai > 0): ?>
                <p class="text-muted mt-3 mb-1">
                    Penilaian esai
                </p>

                <h5>
                    <?= $esai_dinilai ?> dari <?= $total_esai ?>
                    soal sudah dinilai
                </h5>
            <?php endif; ?>

            <hr>

            <?php if ($total === 0): ?>

                <h5 class="text-danger">
                    Ujian belum memiliki soal.
                </h5>

            <?php elseif (!$penilaian_lengkap): ?>

                <div class="mb-3">
                    <span class="badge text-bg-warning fs-6">
                        Menunggu penilaian guru
                    </span>
                </div>

                <p class="text-muted">
                    Jawaban esai kamu masih diperiksa oleh guru.
                    Nilai akhir akan ditampilkan setelah seluruh
                    jawaban esai selesai dinilai.
                </p>

            <?php else: ?>

                <p class="mt-4 mb-1">Nilai akhir kamu</p>

                <h1 class="display-3 fw-bold">
                    <?= number_format($nilai, 2, ',', '.') ?>
                </h1>

                <p class="text-muted">
                    Nilai dihitung dari rata-rata nilai seluruh soal.
                </p>

            <?php endif; ?>

            <a href="kelas_siswa.php"
               class="btn btn-primary w-100 mt-3">
                Kembali ke Halaman Kelas
            </a>

        </div>
    </div>
</div>

</body>
</html>

