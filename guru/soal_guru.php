
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Pastikan guru sudah login
if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];
$pesan = '';
$jenisPesan = 'info';

// Ambil pesan hasil import Excel
if (isset($_SESSION['pesan_import_soal'])) {
    $pesan = $_SESSION['pesan_import_soal'];
    $jenisPesan = $_SESSION['jenis_pesan_import_soal'] ?? 'info';

    unset(
        $_SESSION['pesan_import_soal'],
        $_SESSION['jenis_pesan_import_soal']
    );
}

// Token keamanan form
if (empty($_SESSION['csrf_soal_guru'])) {
    $_SESSION['csrf_soal_guru'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_soal_guru'];

// Fungsi bantuan
function h($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

// Memastikan ujian milik guru yang login
function ujianMilikGuru($conn, $idUjian, $idGuru)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_ujian
         FROM ujian
         WHERE id_ujian = ? AND id_guru = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $idUjian, $idGuru);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $valid = mysqli_num_rows($hasil) > 0;

    mysqli_stmt_close($stmt);

    return $valid;
}

// Memastikan soal termasuk ujian milik guru
function soalMilikGuru($conn, $idSoal, $idUjian, $idGuru)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT s.id_soal
         FROM soal s
         INNER JOIN ujian u ON s.id_ujian = u.id_ujian
         WHERE s.id_soal = ?
           AND s.id_ujian = ?
           AND u.id_guru = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iii',
        $idSoal,
        $idUjian,
        $idGuru
    );

    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $valid = mysqli_num_rows($hasil) > 0;

    mysqli_stmt_close($stmt);

    return $valid;
}

// Memeriksa apakah soal sudah memiliki jawaban siswa
function soalSudahDijawab($conn, $idSoal)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_jawaban
         FROM jawaban_siswa
         WHERE id_soal = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return true;
    }

    mysqli_stmt_bind_param($stmt, 'i', $idSoal);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $adaJawaban = mysqli_num_rows($hasil) > 0;

    mysqli_stmt_close($stmt);

    return $adaJawaban;
}

// Ambil ujian milik guru
$daftarUjian = [];

$stmtUjian = mysqli_prepare(
    $conn,
    "SELECT id_ujian, nama_ujian
     FROM ujian
     WHERE id_guru = ?
     ORDER BY id_ujian DESC"
);

if (!$stmtUjian) {
    die('Gagal mengambil daftar ujian.');
}

mysqli_stmt_bind_param($stmtUjian, 'i', $idGuru);
mysqli_stmt_execute($stmtUjian);

$hasilUjian = mysqli_stmt_get_result($stmtUjian);

while ($row = mysqli_fetch_assoc($hasilUjian)) {
    $daftarUjian[] = $row;
}

mysqli_stmt_close($stmtUjian);

// Tentukan ujian aktif
$idUjianAktif = (int) (
    $_POST['id_ujian'] ?? $_GET['id_ujian'] ?? 0
);

$ujianAktifValid = (
    $idUjianAktif > 0
    && ujianMilikGuru($conn, $idUjianAktif, $idGuru)
);

if (!$ujianAktifValid) {
    $idUjianAktif = 0;
}

// Nilai awal formulir
$nilaiForm = [
    'tipe_soal' => 'pilihan_ganda',
    'teks_soal' => '',
    'opsi_a' => '',
    'opsi_b' => '',
    'opsi_c' => '',
    'opsi_d' => '',
    'jawaban_benar' => ''
];

$soalEdit = null;
$idEdit = (int) ($_GET['edit'] ?? 0);

// Proses permintaan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenDikirim = $_POST['csrf_token'] ?? '';

    if (
        !is_string($tokenDikirim)
        || !hash_equals($csrfToken, $tokenDikirim)
    ) {
        $pesan = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
        $jenisPesan = 'danger';
    } elseif (!$ujianAktifValid) {
        $pesan = 'Ujian tidak ditemukan atau bukan milik akun guru ini.';
        $jenisPesan = 'danger';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        // HAPUS SOAL
        if ($aksi === 'hapus') {
            $idSoal = (int) ($_POST['id_soal'] ?? 0);

            if (
                $idSoal <= 0
                || !soalMilikGuru(
                    $conn,
                    $idSoal,
                    $idUjianAktif,
                    $idGuru
                )
            ) {
                $pesan = 'Soal tidak ditemukan atau tidak dapat diakses.';
                $jenisPesan = 'danger';
            } elseif (soalSudahDijawab($conn, $idSoal)) {
                $pesan = 'Soal tidak dapat dihapus karena sudah memiliki jawaban siswa.';
                $jenisPesan = 'warning';
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM soal
                     WHERE id_soal = ? AND id_ujian = ?"
                );

                if (!$stmt) {
                    $pesan = 'Gagal menyiapkan proses penghapusan soal.';
                    $jenisPesan = 'danger';
                } else {
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ii',
                        $idSoal,
                        $idUjianAktif
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);

                        header(
                            'Location: soal_guru.php?id_ujian='
                            . $idUjianAktif . '&status=hapus'
                        );
                        exit;
                    }

                    mysqli_stmt_close($stmt);

                    $pesan = 'Soal gagal dihapus.';
                    $jenisPesan = 'danger';
                }
            }
        }

        // TAMBAH ATAU EDIT SOAL
        elseif ($aksi === 'tambah' || $aksi === 'edit') {
            $idSoal = (int) ($_POST['id_soal'] ?? 0);

            $teksSoal = trim($_POST['teks_soal'] ?? '');
            $tipeSoal = $_POST['tipe_soal'] ?? '';

            $opsiA = trim($_POST['opsi_a'] ?? '');
            $opsiB = trim($_POST['opsi_b'] ?? '');
            $opsiC = trim($_POST['opsi_c'] ?? '');
            $opsiD = trim($_POST['opsi_d'] ?? '');
            $jawabanBenar = strtoupper(
                trim($_POST['jawaban_benar'] ?? '')
            );

            // Simpan isian agar tidak hilang jika validasi gagal
            $nilaiForm = [
                'tipe_soal' => $tipeSoal,
                'teks_soal' => $teksSoal,
                'opsi_a' => $opsiA,
                'opsi_b' => $opsiB,
                'opsi_c' => $opsiC,
                'opsi_d' => $opsiD,
                'jawaban_benar' => $jawabanBenar
            ];

            // Validasi jenis dan teks soal
            if (
                $teksSoal === ''
                || !in_array(
                    $tipeSoal,
                    ['pilihan_ganda', 'esai'],
                    true
                )
            ) {
                $pesan = 'Teks soal wajib diisi dan jenis soal harus dipilih.';
                $jenisPesan = 'danger';
            } else {
                // Soal esai tidak membutuhkan opsi dan kunci jawaban
                if ($tipeSoal === 'esai') {
                    $opsiA = '';
                    $opsiB = '';
                    $opsiC = '';
                    $opsiD = '';
                    $jawabanBenar = '';
                }

                // Validasi khusus pilihan ganda
                if (
                    $tipeSoal === 'pilihan_ganda'
                    && (
                        $opsiA === ''
                        || $opsiB === ''
                        || $opsiC === ''
                        || $opsiD === ''
                        || !in_array(
                            $jawabanBenar,
                            ['A', 'B', 'C', 'D'],
                            true
                        )
                    )
                ) {
                    $pesan = 'Untuk pilihan ganda, semua opsi A–D dan kunci jawaban wajib diisi.';
                    $jenisPesan = 'danger';
                } else {
                    // EDIT SOAL
                    if ($aksi === 'edit') {
                        if (
                            $idSoal <= 0
                            || !soalMilikGuru(
                                $conn,
                                $idSoal,
                                $idUjianAktif,
                                $idGuru
                            )
                        ) {
                            $pesan = 'Soal tidak ditemukan atau tidak dapat diakses.';
                            $jenisPesan = 'danger';
                        } elseif (soalSudahDijawab($conn, $idSoal)) {
                            $pesan = 'Soal tidak dapat diedit karena sudah memiliki jawaban siswa.';
                            $jenisPesan = 'warning';
                        } else {
                            $stmt = mysqli_prepare(
                                $conn,
                                "UPDATE soal
                                 SET teks_soal = ?,
                                     tipe_soal = ?,
                                     opsi_a = ?,
                                     opsi_b = ?,
                                     opsi_c = ?,
                                     opsi_d = ?,
                                     jawaban_benar = ?
                                 WHERE id_soal = ? AND id_ujian = ?"
                            );

                            if (!$stmt) {
                                $pesan = 'Gagal menyiapkan perubahan soal.';
                                $jenisPesan = 'danger';
                            } else {
                                mysqli_stmt_bind_param(
                                    $stmt,
                                    'sssssssii',
                                    $teksSoal,
                                    $tipeSoal,
                                    $opsiA,
                                    $opsiB,
                                    $opsiC,
                                    $opsiD,
                                    $jawabanBenar,
                                    $idSoal,
                                    $idUjianAktif
                                );

                                if (mysqli_stmt_execute($stmt)) {
                                    mysqli_stmt_close($stmt);

                                    header(
                                        'Location: soal_guru.php?id_ujian='
                                        . $idUjianAktif . '&status=edit'
                                    );
                                    exit;
                                }

                                mysqli_stmt_close($stmt);

                                $pesan = 'Perubahan soal gagal disimpan.';
                                $jenisPesan = 'danger';
                            }
                        }
                    }

                    // TAMBAH SOAL
                    else {
                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO soal
                             (
                                 id_ujian,
                                 id_guru,
                                 teks_soal,
                                 tipe_soal,
                                 opsi_a,
                                 opsi_b,
                                 opsi_c,
                                 opsi_d,
                                 jawaban_benar
                             )
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        if (!$stmt) {
                            $pesan = 'Gagal menyiapkan penyimpanan soal.';
                            $jenisPesan = 'danger';
                        } else {
                            mysqli_stmt_bind_param(
                                $stmt,
                                'iisssssss',
                                $idUjianAktif,
                                $idGuru,
                                $teksSoal,
                                $tipeSoal,
                                $opsiA,
                                $opsiB,
                                $opsiC,
                                $opsiD,
                                $jawabanBenar
                            );

                            if (mysqli_stmt_execute($stmt)) {
                                mysqli_stmt_close($stmt);

                                header(
                                    'Location: soal_guru.php?id_ujian='
                                    . $idUjianAktif . '&status=tambah'
                                );
                                exit;
                            }

                            mysqli_stmt_close($stmt);

                            $pesan = 'Soal gagal ditambahkan. Periksa struktur tabel database.';
                            $jenisPesan = 'danger';
                        }
                    }
                }
            }
        } else {
            $pesan = 'Aksi tidak dikenali.';
            $jenisPesan = 'danger';
        }
    }
}

// Pesan setelah proses berhasil
$status = $_GET['status'] ?? '';

if ($pesan === '') {
    if ($status === 'tambah') {
        $pesan = 'Soal berhasil ditambahkan!';
        $jenisPesan = 'success';
    } elseif ($status === 'edit') {
        $pesan = 'Soal berhasil diperbarui!';
        $jenisPesan = 'success';
    } elseif ($status === 'hapus') {
        $pesan = 'Soal berhasil dihapus!';
        $jenisPesan = 'success';
    }
}

// Ambil data soal untuk diedit
$idEdit = (int) ($_GET['edit'] ?? 0);

if ($idUjianAktif > 0 && $idEdit > 0) {
    if (
        soalMilikGuru(
            $conn,
            $idEdit,
            $idUjianAktif,
            $idGuru
        )
    ) {
        if (soalSudahDijawab($conn, $idEdit)) {
            if ($pesan === '') {
                $pesan = 'Soal tidak bisa diedit karena sudah memiliki jawaban siswa.';
                $jenisPesan = 'warning';
            }
        } else {
            $stmtEdit = mysqli_prepare(
                $conn,
                "SELECT id_soal, teks_soal, tipe_soal,
                        opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar
                 FROM soal
                 WHERE id_soal = ? AND id_ujian = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $stmtEdit,
                'ii',
                $idEdit,
                $idUjianAktif
            );

            mysqli_stmt_execute($stmtEdit);

            $hasilEdit = mysqli_stmt_get_result($stmtEdit);
            $soalEdit = mysqli_fetch_assoc($hasilEdit) ?: null;

            mysqli_stmt_close($stmtEdit);

            if ($soalEdit !== null) {
                $nilaiForm = array_merge($nilaiForm, $soalEdit);
            }
        }
    } else {
        if ($pesan === '') {
            $pesan = 'Soal tidak ditemukan atau bukan bagian dari ujian milikmu.';
            $jenisPesan = 'danger';
        }
    }
}

// Ambil daftar soal ujian aktif
$daftarSoal = null;

if ($idUjianAktif > 0) {
    $stmtSoal = mysqli_prepare(
        $conn,
        "SELECT id_soal, teks_soal, tipe_soal,
                opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar
         FROM soal
         WHERE id_ujian = ?
         ORDER BY id_soal ASC"
    );

    if ($stmtSoal) {
        mysqli_stmt_bind_param($stmtSoal, 'i', $idUjianAktif);
        mysqli_stmt_execute($stmtSoal);
        $daftarSoal = mysqli_stmt_get_result($stmtSoal);
    }
}

$sedangEdit = $soalEdit !== null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Soal - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2>Kelola Soal</h2>
            <p class="text-muted mb-0">
                Tambah, lihat, edit, hapus, dan import soal ujian.
            </p>
        </div>

        <a href="dashboard_guru.php" class="btn btn-outline-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= h($jenisPesan) ?> alert-dismissible fade show">
            <?= h($pesan) ?>
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Tutup"
            ></button>
        </div>
    <?php endif; ?>

    <!-- PILIH UJIAN -->
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold">Pilih Ujian</div>

        <div class="card-body">
            <?php if (count($daftarUjian) > 0): ?>
                <form method="GET">
                    <label for="id_ujian_pilih" class="form-label">
                        Nama Ujian
                    </label>

                    <select
                        name="id_ujian"
                        id="id_ujian_pilih"
                        class="form-select mb-3"
                        required
                    >
                        <option value="">-- Pilih ujian --</option>

                        <?php foreach ($daftarUjian as $ujian): ?>
                            <option
                                value="<?= (int) $ujian['id_ujian'] ?>"
                                <?= $idUjianAktif === (int) $ujian['id_ujian']
                                    ? 'selected' : '' ?>
                            >
                                <?= h($ujian['nama_ujian']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        Pilih Ujian
                    </button>
                </form>
            <?php else: ?>
                <p class="text-muted mb-0">
                    Kamu belum memiliki ujian. Buat ujian terlebih dahulu.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($idUjianAktif > 0): ?>

        <!-- IMPORT SOAL DARI EXCEL -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold">
                Import Soal dari Excel
            </div>

            <div class="card-body">
                <p class="text-muted">
                    Masukkan soal pilihan ganda dan esai sekaligus
                    menggunakan file Excel.
                </p>

                <a
                    href="template_import_soal.php"
                    class="btn btn-outline-success mb-3"
                >
                    Download Template Excel
                </a>

                <form
                    action="import_soal.php"
                    method="POST"
                    enctype="multipart/form-data"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="id_ujian"
                        value="<?= (int) $idUjianAktif ?>"
                    >

                    <div class="mb-3">
                        <label for="file_excel" class="form-label">
                            Pilih File Excel (.xlsx)
                        </label>

                        <input
                            type="file"
                            name="file_excel"
                            id="file_excel"
                            class="form-control"
                            accept=".xlsx"
                            required
                        >

                        <div class="form-text">
                            Ukuran maksimal 5 MB. Gunakan template
                            yang disediakan dan jangan mengubah nama
                            atau urutan kolom.
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-success"
                        onclick="return confirm('Yakin ingin mengimpor soal dari file Excel ini? Soal lama tidak akan dihapus.');"
                    >
                        Import Soal
                    </button>
                </form>
            </div>
        </div>

        <!-- FORM TAMBAH / EDIT SOAL -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold">
                <?= $sedangEdit ? 'Edit Soal' : 'Tambah Soal Baru' ?>
            </div>

            <div class="card-body">
                <form
                    method="POST"
                    action="soal_guru.php?id_ujian=<?= $idUjianAktif ?><?= $sedangEdit ? '&edit=' . (int) $soalEdit['id_soal'] : '' ?>"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="id_ujian"
                        value="<?= $idUjianAktif ?>"
                    >

                    <input
                        type="hidden"
                        name="aksi"
                        value="<?= $sedangEdit ? 'edit' : 'tambah' ?>"
                    >

                    <?php if ($sedangEdit): ?>
                        <input
                            type="hidden"
                            name="id_soal"
                            value="<?= (int) $soalEdit['id_soal'] ?>"
                        >
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="tipe_soal" class="form-label">
                            Jenis Soal
                        </label>

                        <select
                            id="tipe_soal"
                            name="tipe_soal"
                            class="form-select"
                            required
                        >
                            <option
                                value="pilihan_ganda"
                                <?= $nilaiForm['tipe_soal'] === 'pilihan_ganda'
                                    ? 'selected' : '' ?>
                            >
                                Pilihan Ganda
                            </option>

                            <option
                                value="esai"
                                <?= $nilaiForm['tipe_soal'] === 'esai'
                                    ? 'selected' : '' ?>
                            >
                                Esai
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="teks_soal" class="form-label">
                            Teks Soal
                        </label>

                        <textarea
                            id="teks_soal"
                            name="teks_soal"
                            class="form-control"
                            rows="3"
                            required
                        ><?= h($nilaiForm['teks_soal']) ?></textarea>
                    </div>

                    <div id="bagian_pilihan_ganda">
                        <hr>
                        <h5 class="mb-3">Pilihan Jawaban</h5>

                        <div class="mb-3">
                            <label for="opsi_a" class="form-label">
                                Pilihan A
                            </label>
                            <textarea
                                id="opsi_a"
                                name="opsi_a"
                                class="form-control"
                                rows="2"
                            ><?= h($nilaiForm['opsi_a']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="opsi_b" class="form-label">
                                Pilihan B
                            </label>
                            <textarea
                                id="opsi_b"
                                name="opsi_b"
                                class="form-control"
                                rows="2"
                            ><?= h($nilaiForm['opsi_b']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="opsi_c" class="form-label">
                                Pilihan C
                            </label>
                            <textarea
                                id="opsi_c"
                                name="opsi_c"
                                class="form-control"
                                rows="2"
                            ><?= h($nilaiForm['opsi_c']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="opsi_d" class="form-label">
                                Pilihan D
                            </label>
                            <textarea
                                id="opsi_d"
                                name="opsi_d"
                                class="form-control"
                                rows="2"
                            ><?= h($nilaiForm['opsi_d']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="jawaban_benar" class="form-label">
                                Jawaban Benar
                            </label>

                            <select
                                id="jawaban_benar"
                                name="jawaban_benar"
                                class="form-select"
                            >
                                <option value="">
                                    -- Pilih jawaban --
                                </option>

                                <?php foreach (['A', 'B', 'C', 'D'] as $opsi): ?>
                                    <option
                                        value="<?= $opsi ?>"
                                        <?= $nilaiForm['jawaban_benar'] === $opsi
                                            ? 'selected' : '' ?>
                                    >
                                        <?= $opsi ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="keterangan_esai" class="alert alert-info d-none">
                        Soal esai hanya membutuhkan teks pertanyaan.
                        Siswa akan mengetik jawabannya saat ujian.
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-success">
                            <?= $sedangEdit ? 'Simpan Perubahan' : 'Simpan Soal' ?>
                        </button>

                        <?php if ($sedangEdit): ?>
                            <a
                                href="soal_guru.php?id_ujian=<?= $idUjianAktif ?>"
                                class="btn btn-outline-secondary"
                            >
                                Batal Edit
                            </a>
                        <?php else: ?>
                            <a
                                href="dashboard_guru.php"
                                class="btn btn-outline-secondary"
                            >
                                Kembali
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- DAFTAR SOAL -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Daftar Soal</div>

            <div class="card-body">
                <?php if ($daftarSoal && mysqli_num_rows($daftarSoal) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">No.</th>
                                    <th>Soal dan Jawaban</th>
                                    <th style="width: 150px;">Jenis Soal</th>
                                    <th style="width: 180px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php $no = 1; ?>

                            <?php while ($soal = mysqli_fetch_assoc($daftarSoal)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td>
                                        <strong>
                                            <?= nl2br(h($soal['teks_soal'])) ?>
                                        </strong>

                                        <?php if ($soal['tipe_soal'] === 'pilihan_ganda'): ?>
                                            <div class="mt-2">
                                                A. <?= nl2br(h($soal['opsi_a'])) ?>
                                            </div>
                                            <div>
                                                B. <?= nl2br(h($soal['opsi_b'])) ?>
                                            </div>
                                            <div>
                                                C. <?= nl2br(h($soal['opsi_c'])) ?>
                                            </div>
                                            <div>
                                                D. <?= nl2br(h($soal['opsi_d'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted mt-2">
                                                Jawaban berupa uraian/esai.
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($soal['tipe_soal'] === 'pilihan_ganda'): ?>
                                            <span class="badge text-bg-primary">
                                                Pilihan Ganda
                                            </span>

                                            <div class="small mt-2">
                                                Kunci:
                                                <strong>
                                                    <?= h($soal['jawaban_benar']) ?>
                                                </strong>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge text-bg-info">
                                                Esai
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a
                                                href="soal_guru.php?id_ujian=<?= $idUjianAktif ?>&edit=<?= (int) $soal['id_soal'] ?>"
                                                class="btn btn-sm btn-warning"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="soal_guru.php?id_ujian=<?= $idUjianAktif ?>"
                                                onsubmit="return confirm('Yakin ingin menghapus soal ini? Jika soal sudah dijawab siswa, penghapusan akan ditolak.');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= h($csrfToken) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id_ujian"
                                                    value="<?= $idUjianAktif ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id_soal"
                                                    value="<?= (int) $soal['id_soal'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="aksi"
                                                    value="hapus"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        Belum ada soal untuk ujian ini. Tambahkan soal
                        melalui formulir di atas atau gunakan fitur import Excel.
                    </p>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>

        <div class="alert alert-info">
            Pilih ujian milikmu terlebih dahulu untuk mulai mengelola soal.
        </div>

    <?php endif; ?>

</div>

<script>
const tipeSoal = document.getElementById('tipe_soal');
const bagianPG = document.getElementById('bagian_pilihan_ganda');
const keteranganEsai = document.getElementById('keterangan_esai');

const kolomPG = [
    document.getElementById('opsi_a'),
    document.getElementById('opsi_b'),
    document.getElementById('opsi_c'),
    document.getElementById('opsi_d'),
    document.getElementById('jawaban_benar')
];

function aturFormSoal() {
    // Halaman bisa dibuka tanpa memilih ujian
    if (!tipeSoal || !bagianPG || !keteranganEsai) {
        return;
    }

    const pilihanGanda = tipeSoal.value === 'pilihan_ganda';

    bagianPG.classList.toggle('d-none', !pilihanGanda);
    keteranganEsai.classList.toggle('d-none', pilihanGanda);

    kolomPG.forEach(function(kolom) {
        if (kolom) {
            kolom.required = pilihanGanda;
            kolom.disabled = !pilihanGanda;
        }
    });
}

if (tipeSoal) {
    tipeSoal.addEventListener('change', aturFormSoal);
    aturFormSoal();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

