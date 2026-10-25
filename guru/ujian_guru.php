
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];
$pesan = '';
$jenisPesan = '';

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Ambil seluruh kelas.
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

// Proses tambah ujian.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaUjian = trim($_POST['nama_ujian'] ?? '');

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

    $formatTanggalValid = $waktuValid &&
        $waktuValid->format('Y-m-d H:i:s') === $tanggalDB;

    if (
        $namaUjian === '' ||
        empty($idKelasDipilih) ||
        $durasi === false ||
        $durasi < 1 ||
        $durasi > 1440 ||
        $tanggal === '' ||
        !$formatTanggalValid
    ) {
        $pesan = 'Periksa kembali semua kolom dan pilih minimal satu kelas.';
        $jenisPesan = 'danger';
    } else {
        // Validasi semua kelas yang dipilih.
        $kelasValid = [];
        $stmtKelas = mysqli_prepare(
            $conn,
            "SELECT id_kelas FROM kelas WHERE id_kelas = ?"
        );

        foreach ($idKelasDipilih as $idKelas) {
            mysqli_stmt_bind_param($stmtKelas, 'i', $idKelas);
            mysqli_stmt_execute($stmtKelas);

            $hasilKelas = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtKelas)
            );

            if ($hasilKelas) {
                $kelasValid[] = (int) $hasilKelas['id_kelas'];
            }
        }

        mysqli_stmt_close($stmtKelas);

        if (count($kelasValid) !== count($idKelasDipilih)) {
            $pesan = 'Ada kelas yang tidak ditemukan. Silakan pilih ulang.';
            $jenisPesan = 'danger';
        } else {
            mysqli_begin_transaction($conn);

            try {
                // Simpan satu data ujian.
                // id_kelas dipertahankan sementara untuk kompatibilitas lama.
                $idKelasUtama = $kelasValid[0];

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO ujian
                     (nama_ujian, id_kelas, durasi_waktu,
                      tanggal_ujian, id_guru)
                     VALUES (?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    'siisi',
                    $namaUjian,
                    $idKelasUtama,
                    $durasi,
                    $tanggalDB,
                    $idGuru
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Gagal menyimpan data ujian.');
                }

                $idUjianBaru = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                // Simpan seluruh kelas pada tabel penghubung.
                $stmtRelasi = mysqli_prepare(
                    $conn,
                    "INSERT INTO ujian_kelas (id_ujian, id_kelas)
                     VALUES (?, ?)"
                );

                foreach ($kelasValid as $idKelas) {
                    mysqli_stmt_bind_param(
                        $stmtRelasi,
                        'ii',
                        $idUjianBaru,
                        $idKelas
                    );

                    if (!mysqli_stmt_execute($stmtRelasi)) {
                        throw new Exception('Gagal menyimpan kelas ujian.');
                    }
                }

                mysqli_stmt_close($stmtRelasi);

                mysqli_commit($conn);

                header('Location: ujian_guru.php?status=berhasil');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                error_log('Tambah ujian gagal: ' . $e->getMessage());

                $pesan = 'Ujian gagal disimpan. Periksa kembali database atau log PHP.';
                $jenisPesan = 'danger';
            }
        }
    }
}

$status = $_GET['status'] ?? '';

$daftarPesan = [
    'berhasil' => ['Ujian berhasil ditambahkan!', 'success'],
    'edit_berhasil' => ['Ujian berhasil diperbarui!', 'success'],
    'hapus_berhasil' => ['Ujian berhasil dihapus!', 'success'],
    'hapus_memiliki_data' => [
        'Ujian tidak dapat dihapus karena sudah memiliki jawaban atau riwayat penyelesaian siswa.',
        'warning'
    ],
    'hapus_gagal' => [
        'Ujian gagal dihapus atau tidak ditemukan.',
        'danger'
    ]
];

if (isset($daftarPesan[$status])) {
    $pesan = $daftarPesan[$status][0];
    $jenisPesan = $daftarPesan[$status][1];
}

// Ambil ujian milik guru dan semua kelas yang terkait.
$stmtUjian = mysqli_prepare(
    $conn,
    "SELECT
        u.id_ujian,
        u.nama_ujian,
        u.durasi_waktu,
        u.tanggal_ujian,
        GROUP_CONCAT(
            DISTINCT k.nama_kelas
            ORDER BY k.nama_kelas
            SEPARATOR ', '
        ) AS daftar_kelas
     FROM ujian u
     LEFT JOIN ujian_kelas uk ON uk.id_ujian = u.id_ujian
     LEFT JOIN kelas k ON k.id_kelas = uk.id_kelas
     WHERE u.id_guru = ?
     GROUP BY
        u.id_ujian,
        u.nama_ujian,
        u.durasi_waktu,
        u.tanggal_ujian
     ORDER BY u.tanggal_ujian DESC, u.id_ujian DESC"
);

mysqli_stmt_bind_param($stmtUjian, 'i', $idGuru);
mysqli_stmt_execute($stmtUjian);
$daftarUjian = mysqli_stmt_get_result($stmtUjian);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Ujian - ExamHub</title>

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
            <h2>Kelola Ujian</h2>
            <p class="text-muted mb-0">
                Buat ujian, atur jadwal, dan kelola ujian Anda.
            </p>
        </div>
        <a href="dashboard_guru.php" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= aman($jenisPesan) ?>">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            Tambah Ujian Baru
        </div>

        <div class="card-body">
            <form method="POST" action="ujian_guru.php">
                <div class="mb-3">
                    <label for="nama_ujian" class="form-label">
                        Nama Ujian
                    </label>
                    <input
                        type="text"
                        name="nama_ujian"
                        id="nama_ujian"
                        class="form-control"
                        maxlength="100"
                        value="<?= aman($_POST['nama_ujian'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Pilih Kelas</label>

                    <div class="border rounded p-3">
                        <?php if (count($kelasList) > 0): ?>
                            <?php foreach ($kelasList as $kelas): ?>
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="id_kelas[]"
                                        id="kelas_<?= (int) $kelas['id_kelas'] ?>"
                                        value="<?= (int) $kelas['id_kelas'] ?>"
                                        <?= in_array(
                                            (int) $kelas['id_kelas'],
                                            array_map(
                                                'intval',
                                                is_array($_POST['id_kelas'] ?? null)
                                                    ? $_POST['id_kelas']
                                                    : []
                                            ),
                                            true
                                        ) ? 'checked' : '' ?>
                                    >
                                    <label
                                        class="form-check-label"
                                        for="kelas_<?= (int) $kelas['id_kelas'] ?>"
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
                        Pilih satu kelas atau lebih untuk ujian ini.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="durasi_waktu" class="form-label">
                        Durasi Ujian (menit)
                    </label>
                    <input
                        type="number"
                        name="durasi_waktu"
                        id="durasi_waktu"
                        class="form-control"
                        min="1"
                        max="1440"
                        placeholder="Contoh: 60"
                        value="<?= aman($_POST['durasi_waktu'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="tanggal_ujian" class="form-label">
                        Tanggal dan Jam Mulai
                    </label>
                    <input
                        type="datetime-local"
                        name="tanggal_ujian"
                        id="tanggal_ujian"
                        class="form-control"
                        value="<?= aman($_POST['tanggal_ujian'] ?? '') ?>"
                        required
                    >
                    <div class="form-text">
                        Pilih tanggal dan jam mulai ujian.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Ujian
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">
            Daftar Ujian Saya
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Nama Ujian</th>
                        <th>Kelas</th>
                        <th>Durasi</th>
                        <th>Jadwal Mulai</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (mysqli_num_rows($daftarUjian) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($ujian = mysqli_fetch_assoc($daftarUjian)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= aman($ujian['nama_ujian']) ?></td>
                                <td><?= aman($ujian['daftar_kelas'] ?? '-') ?></td>
                                <td><?= (int) $ujian['durasi_waktu'] ?> menit</td>
                                <td>
                                    <?php
                                    $timestamp = strtotime($ujian['tanggal_ujian']);
                                    echo $timestamp
                                        ? aman(date('d-m-Y H:i', $timestamp))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a
                                            href="edit_ujian_guru.php?id_ujian=<?= (int) $ujian['id_ujian'] ?>"
                                            class="btn btn-warning btn-sm"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="hapus_ujian_guru.php"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus ujian ini? Soal yang terkait juga akan dihapus jika belum ada riwayat pengerjaan.');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id_ujian"
                                                value="<?= (int) $ujian['id_ujian'] ?>"
                                            >
                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada ujian yang dibuat.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>

<?php
mysqli_stmt_close($stmtUjian);
?>

