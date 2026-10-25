
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];
$idUjian = (int) ($_GET['id_ujian'] ?? $_POST['id_ujian'] ?? 0);
$pesan = '';

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Pastikan ujian milik guru yang login.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_ujian, nama_ujian, id_kelas,
            durasi_waktu, tanggal_ujian
     FROM ujian
     WHERE id_ujian = ? AND id_guru = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 'ii', $idUjian, $idGuru);
mysqli_stmt_execute($stmt);
$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujian) {
    http_response_code(404);
    exit('Ujian tidak ditemukan atau bukan milik Anda.');
}

// Ambil kelas yang sudah terhubung ke ujian.
$kelasTerpilih = [];

$stmtKelasTerpilih = mysqli_prepare(
    $conn,
    "SELECT id_kelas
     FROM ujian_kelas
     WHERE id_ujian = ?"
);

mysqli_stmt_bind_param($stmtKelasTerpilih, 'i', $idUjian);
mysqli_stmt_execute($stmtKelasTerpilih);

$resultKelasTerpilih = mysqli_stmt_get_result($stmtKelasTerpilih);

while ($baris = mysqli_fetch_assoc($resultKelasTerpilih)) {
    $kelasTerpilih[] = (int) $baris['id_kelas'];
}

mysqli_stmt_close($stmtKelasTerpilih);

// Jika relasi belum ada, gunakan kelas lama dari tabel ujian.
if (empty($kelasTerpilih) && (int) $ujian['id_kelas'] > 0) {
    $kelasTerpilih[] = (int) $ujian['id_kelas'];
}

// Proses perubahan data ujian.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_ujian'] ?? '');

    $idKelasDipilih = $_POST['id_kelas'] ?? [];
    if (!is_array($idKelasDipilih)) {
        $idKelasDipilih = [];
    }

    $idKelasDipilih = array_values(array_unique(array_filter(
        array_map('intval', $idKelasDipilih),
        function ($id) {
            return $id > 0;
        }
    )));

    $durasi = filter_var(
        $_POST['durasi_waktu'] ?? null,
        FILTER_VALIDATE_INT
    );

    $tanggal = trim($_POST['tanggal_ujian'] ?? '');
    $tanggalDB = str_replace('T', ' ', $tanggal);

    if (strlen($tanggalDB) === 16) {
        $tanggalDB .= ':00';
    }

    $waktuValid = DateTime::createFromFormat(
        'Y-m-d H:i:s',
        $tanggalDB
    );

    $formatValid = $waktuValid &&
        $waktuValid->format('Y-m-d H:i:s') === $tanggalDB;

    if (
        $nama === '' ||
        empty($idKelasDipilih) ||
        $durasi === false ||
        $durasi < 1 ||
        $durasi > 1440 ||
        $tanggal === '' ||
        !$formatValid
    ) {
        $pesan = 'Periksa kembali semua kolom. Pilih minimal satu kelas dan pastikan durasi 1–1440 menit.';
    } else {
        // Pastikan seluruh kelas yang dipilih benar-benar tersedia.
        $kelasValid = [];

        $stmtValidasiKelas = mysqli_prepare(
            $conn,
            "SELECT id_kelas FROM kelas WHERE id_kelas = ?"
        );

        foreach ($idKelasDipilih as $idKelas) {
            mysqli_stmt_bind_param(
                $stmtValidasiKelas,
                'i',
                $idKelas
            );

            mysqli_stmt_execute($stmtValidasiKelas);

            $hasil = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtValidasiKelas)
            );

            if ($hasil) {
                $kelasValid[] = (int) $hasil['id_kelas'];
            }
        }

        mysqli_stmt_close($stmtValidasiKelas);

        if (count($kelasValid) !== count($idKelasDipilih)) {
            $pesan = 'Ada kelas yang tidak ditemukan. Silakan pilih ulang.';
        } else {
            mysqli_begin_transaction($conn);

            try {
                // Simpan salah satu kelas pada kolom lama
                // untuk kompatibilitas halaman yang belum diperbarui.
                $idKelasUtama = $kelasValid[0];

                $stmtUpdate = mysqli_prepare(
                    $conn,
                    "UPDATE ujian
                     SET nama_ujian = ?,
                         id_kelas = ?,
                         durasi_waktu = ?,
                         tanggal_ujian = ?
                     WHERE id_ujian = ? AND id_guru = ?"
                );

                mysqli_stmt_bind_param(
                    $stmtUpdate,
                    'siisii',
                    $nama,
                    $idKelasUtama,
                    $durasi,
                    $tanggalDB,
                    $idUjian,
                    $idGuru
                );

                if (!mysqli_stmt_execute($stmtUpdate)) {
                    throw new Exception('Gagal memperbarui data ujian.');
                }

                mysqli_stmt_close($stmtUpdate);

                // Ganti seluruh relasi kelas dengan pilihan terbaru.
                $stmtHapusRelasi = mysqli_prepare(
                    $conn,
                    "DELETE FROM ujian_kelas WHERE id_ujian = ?"
                );

                mysqli_stmt_bind_param(
                    $stmtHapusRelasi,
                    'i',
                    $idUjian
                );

                if (!mysqli_stmt_execute($stmtHapusRelasi)) {
                    throw new Exception('Gagal memperbarui relasi kelas.');
                }

                mysqli_stmt_close($stmtHapusRelasi);

                $stmtTambahRelasi = mysqli_prepare(
                    $conn,
                    "INSERT INTO ujian_kelas (id_ujian, id_kelas)
                     VALUES (?, ?)"
                );

                foreach ($kelasValid as $idKelas) {
                    mysqli_stmt_bind_param(
                        $stmtTambahRelasi,
                        'ii',
                        $idUjian,
                        $idKelas
                    );

                    if (!mysqli_stmt_execute($stmtTambahRelasi)) {
                        throw new Exception('Gagal menyimpan pilihan kelas.');
                    }
                }

                mysqli_stmt_close($stmtTambahRelasi);

                mysqli_commit($conn);

                header('Location: ujian_guru.php?status=edit_berhasil');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                error_log('Edit ujian gagal: ' . $e->getMessage());

                $pesan = 'Gagal menyimpan perubahan. Periksa kembali database atau log PHP.';
            }
        }
    }

    // Jika validasi gagal, pertahankan nilai yang diinput.
    $ujian['nama_ujian'] = $nama;
    $ujian['durasi_waktu'] = $durasi === false
        ? ($_POST['durasi_waktu'] ?? '')
        : $durasi;

    $ujian['tanggal_ujian'] = $tanggalDB;
    $kelasTerpilih = $idKelasDipilih;
}

// Ambil seluruh kelas untuk checkbox.
$daftarKelas = mysqli_query(
    $conn,
    "SELECT id_kelas, nama_kelas
     FROM kelas
     ORDER BY nama_kelas ASC"
);

$kelasList = [];

if ($daftarKelas) {
    while ($kelas = mysqli_fetch_assoc($daftarKelas)) {
        $kelasList[] = $kelas;
    }
}

// Format tanggal untuk input datetime-local.
$nilaiTanggal = '';

if (!empty($ujian['tanggal_ujian'])) {
    $timestamp = strtotime($ujian['tanggal_ujian']);

    if ($timestamp !== false) {
        $nilaiTanggal = date('Y-m-d\TH:i', $timestamp);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nilaiTanggal = $_POST['tanggal_ujian'] ?? $nilaiTanggal;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Ujian - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
        <div class="card-header fw-bold">Edit Ujian</div>

        <div class="card-body">
            <?php if ($pesan !== ''): ?>
                <div class="alert alert-danger">
                    <?= aman($pesan) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input
                    type="hidden"
                    name="id_ujian"
                    value="<?= $idUjian ?>"
                >

                <div class="mb-3">
                    <label class="form-label" for="nama_ujian">
                        Nama Ujian
                    </label>

                    <input
                        type="text"
                        id="nama_ujian"
                        name="nama_ujian"
                        class="form-control"
                        maxlength="100"
                        value="<?= aman($ujian['nama_ujian']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Pilih Kelas</label>

                    <div class="border rounded p-3">
                        <?php if (count($kelasList) > 0): ?>
                            <?php foreach ($kelasList as $kelas): ?>
                                <?php
                                $idKelasCheckbox = (int) $kelas['id_kelas'];
                                $tercentang = in_array(
                                    $idKelasCheckbox,
                                    $kelasTerpilih,
                                    true
                                );
                                ?>

                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="id_kelas[]"
                                        id="kelas_<?= $idKelasCheckbox ?>"
                                        value="<?= $idKelasCheckbox ?>"
                                        <?= $tercentang ? 'checked' : '' ?>
                                    >

                                    <label
                                        class="form-check-label"
                                        for="kelas_<?= $idKelasCheckbox ?>"
                                    >
                                        <?= aman($kelas['nama_kelas']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0">
                                Belum ada kelas yang tersedia.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-text">
                        Kelas yang sudah dipilih akan otomatis tercentang.
                        Pilih minimal satu kelas.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="durasi_waktu">
                        Durasi Ujian (menit)
                    </label>

                    <input
                        type="number"
                        id="durasi_waktu"
                        name="durasi_waktu"
                        class="form-control"
                        min="1"
                        max="1440"
                        value="<?= aman($ujian['durasi_waktu']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label" for="tanggal_ujian">
                        Tanggal dan Jam Mulai
                    </label>

                    <input
                        type="datetime-local"
                        id="tanggal_ujian"
                        name="tanggal_ujian"
                        class="form-control"
                        value="<?= aman($nilaiTanggal) ?>"
                        required
                    >
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a
                        href="ujian_guru.php"
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

