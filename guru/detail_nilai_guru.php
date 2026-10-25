
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

$id_ujian = filter_input(INPUT_GET, 'id_ujian', FILTER_VALIDATE_INT);
$id_siswa = filter_input(INPUT_GET, 'id_siswa', FILTER_VALIDATE_INT);

if (!$id_ujian || !$id_siswa) {
    http_response_code(400);
    exit('Data ujian atau siswa tidak valid.');
}

// 2. Ambil informasi ujian dan siswa.
// Gunakan ujian_kelas agar siswa dari semua kelas peserta ujian dapat ditemukan.
$query_info = "
    SELECT
        u.id_ujian,
        u.nama_ujian,
        u.tanggal_ujian,
        s.id_siswa,
        s.nama_siswa,
        us.waktu_selesai
    FROM ujian u
    INNER JOIN ujian_kelas uk
        ON uk.id_ujian = u.id_ujian
    INNER JOIN siswa s
        ON s.id_kelas = uk.id_kelas
    INNER JOIN ujian_selesai us
        ON us.id_ujian = u.id_ujian
        AND us.id_siswa = s.id_siswa
    WHERE u.id_ujian = ?
      AND s.id_siswa = ?
      AND u.id_guru = ?
    LIMIT 1
";

$stmt_info = mysqli_prepare($conn, $query_info);

if (!$stmt_info) {
    http_response_code(500);
    exit('Gagal menyiapkan data ujian.');
}

mysqli_stmt_bind_param(
    $stmt_info,
    'iii',
    $id_ujian,
    $id_siswa,
    $id_guru
);

mysqli_stmt_execute($stmt_info);
$result_info = mysqli_stmt_get_result($stmt_info);
$info = mysqli_fetch_assoc($result_info);
mysqli_stmt_close($stmt_info);

if (!$info) {
    http_response_code(404);
    exit('Data ujian atau siswa tidak ditemukan.');
}

// 3. Token keamanan form penilaian
if (empty($_SESSION['csrf_detail_nilai'])) {
    $_SESSION['csrf_detail_nilai'] = bin2hex(random_bytes(32));
}

$pesan = '';
$jenis_pesan = '';

// 4. Simpan nilai esai
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $id_jawaban = filter_input(
        INPUT_POST,
        'id_jawaban',
        FILTER_VALIDATE_INT
    );
    $nilai_input = trim($_POST['nilai_esai'] ?? '');

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['csrf_detail_nilai'], $csrf)
    ) {
        $pesan = 'Permintaan tidak valid. Muat ulang halaman.';
        $jenis_pesan = 'danger';
    } elseif (
        !$id_jawaban ||
        $nilai_input === '' ||
        !is_numeric($nilai_input)
    ) {
        $pesan = 'Nilai harus diisi dengan angka.';
        $jenis_pesan = 'danger';
    } elseif (
        (float) $nilai_input < 0 ||
        (float) $nilai_input > 100
    ) {
        $pesan = 'Nilai harus berada di antara 0 sampai 100.';
        $jenis_pesan = 'danger';
    } else {
        $nilai_esai = (float) $nilai_input;

        // Pastikan jawaban esai milik siswa dan ujian yang benar,
        // serta ujian tersebut milik guru yang sedang login.
        $cek_query = "
            SELECT j.id_jawaban
            FROM jawaban_siswa j
            INNER JOIN soal so
                ON so.id_soal = j.id_soal
                AND so.id_ujian = j.id_ujian
            INNER JOIN ujian u
                ON u.id_ujian = j.id_ujian
            WHERE j.id_jawaban = ?
              AND j.id_siswa = ?
              AND j.id_ujian = ?
              AND u.id_guru = ?
              AND so.tipe_soal = 'esai'
            LIMIT 1
        ";

        $stmt_cek = mysqli_prepare($conn, $cek_query);

        if (!$stmt_cek) {
            $pesan = 'Gagal memeriksa jawaban.';
            $jenis_pesan = 'danger';
        } else {
            mysqli_stmt_bind_param(
                $stmt_cek,
                'iiii',
                $id_jawaban,
                $id_siswa,
                $id_ujian,
                $id_guru
            );

            mysqli_stmt_execute($stmt_cek);
            $result_cek = mysqli_stmt_get_result($stmt_cek);
            $jawaban_valid = mysqli_fetch_assoc($result_cek);
            mysqli_stmt_close($stmt_cek);

            if (!$jawaban_valid) {
                $pesan = 'Jawaban esai tidak ditemukan.';
                $jenis_pesan = 'danger';
            } else {
                $update_query = "
                    UPDATE jawaban_siswa
                    SET nilai_esai = ?
                    WHERE id_jawaban = ?
                ";

                $stmt_update = mysqli_prepare($conn, $update_query);

                if (!$stmt_update) {
                    $pesan = 'Gagal menyiapkan penyimpanan nilai.';
                    $jenis_pesan = 'danger';
                } else {
                    mysqli_stmt_bind_param(
                        $stmt_update,
                        'di',
                        $nilai_esai,
                        $id_jawaban
                    );

                    if (mysqli_stmt_execute($stmt_update)) {
                        $pesan = 'Nilai esai berhasil disimpan.';
                        $jenis_pesan = 'success';
                    } else {
                        $pesan = 'Nilai esai gagal disimpan.';
                        $jenis_pesan = 'danger';
                    }

                    mysqli_stmt_close($stmt_update);
                }
            }
        }
    }
}

// 5. Ambil semua soal dan jawaban siswa
$query_soal = "
    SELECT
        so.id_soal,
        so.teks_soal,
        so.tipe_soal,
        so.opsi_a,
        so.opsi_b,
        so.opsi_c,
        so.opsi_d,
        so.jawaban_benar,
        j.id_jawaban,
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

$stmt_soal = mysqli_prepare($conn, $query_soal);

if (!$stmt_soal) {
    http_response_code(500);
    exit('Gagal mengambil soal dan jawaban siswa.');
}

mysqli_stmt_bind_param($stmt_soal, 'ii', $id_siswa, $id_ujian);
mysqli_stmt_execute($stmt_soal);
$result_soal = mysqli_stmt_get_result($stmt_soal);

$daftar_soal = [];

while ($baris = mysqli_fetch_assoc($result_soal)) {
    $daftar_soal[] = $baris;
}

mysqli_stmt_close($stmt_soal);

// 6. Hitung ringkasan nilai
$total_soal = count($daftar_soal);
$total_pg = 0;
$benar_pg = 0;
$total_esai = 0;
$esai_dinilai = 0;
$total_poin = 0;

foreach ($daftar_soal as $soal) {
    $jawaban = trim((string) ($soal['jawaban_terpilih'] ?? ''));

    if ($soal['tipe_soal'] === 'pilihan_ganda') {
        $total_pg++;

        if (
            $jawaban !== '' &&
            strtoupper($jawaban) ===
            strtoupper(trim((string) $soal['jawaban_benar']))
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

$penilaian_lengkap = (
    $total_soal > 0 &&
    $esai_dinilai === $total_esai
);

$nilai_akhir = $penilaian_lengkap
    ? round($total_poin / $total_soal, 2)
    : null;

// 7. Fungsi pengaman teks
function h($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Detail Penilaian Siswa</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand mb-0">
            Detail Jawaban Siswa
        </span>

        <a href="nilai_guru.php" class="btn btn-light btn-sm">
            Kembali ke Daftar Nilai
        </a>
    </div>
</nav>

<div class="container py-4">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h3 class="mb-3"><?= h($info['nama_siswa']) ?></h3>

            <p class="mb-1">
                <strong>Nama Ujian:</strong>
                <?= h($info['nama_ujian']) ?>
            </p>

            <p class="mb-1">
                <strong>Waktu Selesai:</strong>
                <?= date(
                    'd-m-Y H:i:s',
                    strtotime($info['waktu_selesai'])
                ) ?>
            </p>

            <p class="mb-0">
                <strong>Soal:</strong>
                <?= $total_soal ?>
                |
                <strong>PG Benar:</strong>
                <?= $benar_pg ?>/<?= $total_pg ?>
                |
                <strong>Esai Dinilai:</strong>
                <?= $esai_dinilai ?>/<?= $total_esai ?>
            </p>

            <hr>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <strong>Nilai Akhir:</strong>

                <?php if ($nilai_akhir !== null): ?>
                    <span class="fs-4 fw-bold text-success">
                        <?= number_format($nilai_akhir, 2, ',', '.') ?>
                    </span>
                    <span class="badge text-bg-success">
                        Selesai dinilai
                    </span>
                <?php else: ?>
                    <span class="text-muted">
                        Menunggu penilaian esai
                    </span>
                    <span class="badge text-bg-warning">
                        Belum lengkap
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= h($jenis_pesan) ?>">
            <?= h($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($daftar_soal)): ?>

        <div class="alert alert-info">
            Belum ada soal untuk ujian ini.
        </div>

    <?php else: ?>

        <?php foreach ($daftar_soal as $index => $soal): ?>

            <?php
            $jawaban = trim(
                (string) ($soal['jawaban_terpilih'] ?? '')
            );

            $is_pg = $soal['tipe_soal'] === 'pilihan_ganda';

            $pg_benar = $is_pg &&
                $jawaban !== '' &&
                strtoupper($jawaban) ===
                strtoupper(trim((string) $soal['jawaban_benar']));
            ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">

                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <h5 class="mb-0">
                            Soal <?= $index + 1 ?>
                        </h5>

                        <?php if ($is_pg): ?>
                            <span class="badge text-bg-primary">
                                Pilihan Ganda
                            </span>
                        <?php else: ?>
                            <span class="badge text-bg-info">
                                Esai
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <?= nl2br(h($soal['teks_soal'])) ?>
                    </div>

                    <?php if ($is_pg): ?>

                        <div class="mb-3">
                            <?php
                            $opsi = [
                                'A' => $soal['opsi_a'],
                                'B' => $soal['opsi_b'],
                                'C' => $soal['opsi_c'],
                                'D' => $soal['opsi_d']
                            ];
                            ?>

                            <?php foreach ($opsi as $huruf => $teks_opsi): ?>
                                <div class="border rounded p-2 mb-2
                                    <?php
                                    if (
                                        $huruf === strtoupper(
                                            trim((string) $soal['jawaban_benar'])
                                        )
                                    ) {
                                        echo 'border-success bg-success-subtle';
                                    } elseif (
                                        $jawaban !== '' &&
                                        $huruf === strtoupper($jawaban)
                                    ) {
                                        echo 'border-danger bg-danger-subtle';
                                    }
                                    ?>">
                                    <strong><?= $huruf ?>.</strong>
                                    <?= nl2br(h($teks_opsi)) ?>

                                    <?php if (
                                        $huruf === strtoupper(
                                            trim((string) $soal['jawaban_benar'])
                                        )
                                    ): ?>
                                        <span class="badge text-bg-success float-end">
                                            Kunci Jawaban
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <p class="mb-1">
                            <strong>Jawaban Siswa:</strong>
                            <?= $jawaban !== ''
                                ? h(strtoupper($jawaban))
                                : 'Tidak dijawab' ?>
                        </p>

                        <p class="mb-0">
                            <strong>Hasil:</strong>

                            <?php if ($jawaban === ''): ?>
                                <span class="badge text-bg-secondary">
                                    Tidak dijawab
                                </span>
                            <?php elseif ($pg_benar): ?>
                                <span class="badge text-bg-success">
                                    Benar
                                </span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">
                                    Salah
                                </span>
                            <?php endif; ?>
                        </p>

                    <?php else: ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Jawaban Siswa
                            </label>

                            <div class="border rounded bg-light p-3">
                                <?php if ($jawaban === ''): ?>
                                    <span class="text-muted">
                                        Siswa tidak mengisi jawaban.
                                    </span>
                                <?php else: ?>
                                    <?= nl2br(h($jawaban)) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($soal['id_jawaban'])): ?>

                            <form method="POST" class="row g-3 align-items-end">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= h($_SESSION['csrf_detail_nilai']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id_jawaban"
                                    value="<?= (int) $soal['id_jawaban'] ?>"
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
                                        value="<?= $soal['nilai_esai'] === null
                                            ? ''
                                            : h($soal['nilai_esai']) ?>"
                                        placeholder="Contoh: 85"
                                    >
                                </div>

                                <div class="col-sm-auto">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        Simpan Nilai Esai
                                    </button>
                                </div>
                            </form>

                        <?php else: ?>

                            <div class="alert alert-warning mb-0">
                                Data jawaban siswa untuk soal ini tidak ditemukan.
                                Nilai esai belum dapat diberikan.
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <a href="nilai_guru.php" class="btn btn-outline-secondary mb-4">
        Kembali ke Daftar Nilai
    </a>

</div>

</body>
</html>
