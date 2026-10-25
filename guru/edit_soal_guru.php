
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];
$idSoal = (int) ($_GET['id_soal'] ?? $_POST['id_soal'] ?? 0);

$pesan = '';
$jenisPesan = 'danger';

function h($nilai) {
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

// Ambil soal yang termasuk ujian milik guru.
$stmt = mysqli_prepare(
    $conn,
    "SELECT s.id_soal, s.id_ujian, s.teks_soal,
            s.opsi_a, s.opsi_b, s.opsi_c, s.opsi_d,
            s.jawaban_benar, u.nama_ujian
     FROM soal s
     INNER JOIN ujian u ON u.id_ujian = s.id_ujian
     WHERE s.id_soal = ? AND u.id_guru = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 'ii', $idSoal, $idGuru);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$soal = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

if (!$soal) {
    http_response_code(404);
    exit('Soal tidak ditemukan atau bukan milik ujian Anda.');
}

$idUjian = (int) $soal['id_ujian'];

// Proses pembaruan soal.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teksSoal = trim($_POST['teks_soal'] ?? '');
    $opsiA = trim($_POST['opsi_a'] ?? '');
    $opsiB = trim($_POST['opsi_b'] ?? '');
    $opsiC = trim($_POST['opsi_c'] ?? '');
    $opsiD = trim($_POST['opsi_d'] ?? '');
    $jawabanBenar = $_POST['jawaban_benar'] ?? '';

    if (
        $teksSoal === '' ||
        $opsiA === '' ||
        $opsiB === '' ||
        $opsiC === '' ||
        $opsiD === '' ||
        !in_array($jawabanBenar, ['A', 'B', 'C', 'D'], true)
    ) {
        $pesan = 'Semua kolom wajib diisi dengan benar.';
    } else {
        $update = mysqli_prepare(
            $conn,
            "UPDATE soal
             SET teks_soal = ?,
                 opsi_a = ?,
                 opsi_b = ?,
                 opsi_c = ?,
                 opsi_d = ?,
                 jawaban_benar = ?
             WHERE id_soal = ?
               AND id_ujian = ?
               AND EXISTS (
                   SELECT 1
                   FROM ujian
                   WHERE ujian.id_ujian = soal.id_ujian
                     AND ujian.id_guru = ?
               )"
        );

        mysqli_stmt_bind_param(
            $update,
            'ssssssiii',
            $teksSoal,
            $opsiA,
            $opsiB,
            $opsiC,
            $opsiD,
            $jawabanBenar,
            $idSoal,
            $idUjian,
            $idGuru
        );

        if (mysqli_stmt_execute($update)) {
            mysqli_stmt_close($update);

            header(
                'Location: soal_guru.php?id_ujian=' .
                $idUjian . '&status=edit_berhasil'
            );
            exit;
        }

        mysqli_stmt_close($update);
        $pesan = 'Soal gagal diperbarui. Silakan coba lagi.';
    }

    // Pertahankan isian jika validasi gagal.
    $soal['teks_soal'] = $teksSoal;
    $soal['opsi_a'] = $opsiA;
    $soal['opsi_b'] = $opsiB;
    $soal['opsi_c'] = $opsiC;
    $soal['opsi_d'] = $opsiD;
    $soal['jawaban_benar'] = $jawabanBenar;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Soal - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
        <div class="card-header fw-bold">
            Edit Soal
        </div>

        <div class="card-body">
            <p class="text-muted">
                Ujian: <?= h($soal['nama_ujian']) ?>
            </p>

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-<?= h($jenisPesan) ?>">
                    <?= h($pesan) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input
                    type="hidden"
                    name="id_soal"
                    value="<?= $idSoal ?>"
                >

                <div class="mb-3">
                    <label class="form-label">Teks Soal</label>
                    <textarea
                        name="teks_soal"
                        class="form-control"
                        rows="3"
                        required
                    ><?= h($soal['teks_soal']) ?></textarea>
                </div>

                <?php foreach (['a', 'b', 'c', 'd'] as $opsi): ?>
                    <div class="mb-3">
                        <label class="form-label">
                            Pilihan <?= strtoupper($opsi) ?>
                        </label>

                        <input
                            type="text"
                            name="opsi_<?= $opsi ?>"
                            class="form-control"
                            value="<?= h($soal['opsi_' . $opsi]) ?>"
                            required
                        >
                    </div>
                <?php endforeach; ?>

                <div class="mb-3">
                    <label class="form-label">Jawaban Benar</label>

                    <select
                        name="jawaban_benar"
                        class="form-select"
                        required
                    >
                        <?php foreach (['A', 'B', 'C', 'D'] as $opsi): ?>
                            <option
                                value="<?= $opsi ?>"
                                <?= $soal['jawaban_benar'] === $opsi
                                    ? 'selected' : '' ?>
                            >
                                <?= $opsi ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a
                        href="soal_guru.php?id_ujian=<?= $idUjian ?>"
                        class="btn btn-outline-secondary"
                    >
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>

