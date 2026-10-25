
<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

$error = "";
$nama_ujian = "";
$id_kelas = "";
$id_guru = "";
$durasi_waktu = "";
$tanggal_ujian = "";

// Ambil daftar kelas
$daftar_kelas = $conn->query(
    "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas"
);

// Ambil daftar guru
$daftar_guru = $conn->query(
    "SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_ujian = trim($_POST["nama_ujian"] ?? "");
    $id_kelas = (int) ($_POST["id_kelas"] ?? 0);
    $id_guru = (int) ($_POST["id_guru"] ?? 0);
    $durasi_waktu = filter_var(
        $_POST["durasi_waktu"] ?? "",
        FILTER_VALIDATE_INT
    );
    $tanggal_ujian = trim($_POST["tanggal_ujian"] ?? "");

    if (
        $nama_ujian === "" ||
        $id_kelas < 1 ||
        $id_guru < 1 ||
        $durasi_waktu === false ||
        $durasi_waktu < 1 ||
        $tanggal_ujian === ""
    ) {
        $error = "Semua kolom wajib diisi dengan data yang valid.";
    } else {
        // Pastikan kelas tersedia
        $cek_kelas = $conn->prepare(
            "SELECT id_kelas FROM kelas WHERE id_kelas = ?"
        );
        $cek_kelas->bind_param("i", $id_kelas);
        $cek_kelas->execute();
        $kelas_valid = $cek_kelas->get_result()->num_rows === 1;
        $cek_kelas->close();

        // Pastikan guru tersedia
        $cek_guru = $conn->prepare(
            "SELECT id_guru FROM guru WHERE id_guru = ?"
        );
        $cek_guru->bind_param("i", $id_guru);
        $cek_guru->execute();
        $guru_valid = $cek_guru->get_result()->num_rows === 1;
        $cek_guru->close();

        // Validasi format tanggal dan waktu
        $tanggal_valid = false;
        $format_tanggal = DateTime::createFromFormat(
            "Y-m-d\TH:i",
            $tanggal_ujian
        );

        if (
            $format_tanggal !== false &&
            $format_tanggal->format("Y-m-d\TH:i") === $tanggal_ujian
        ) {
            $tanggal_valid = true;
        }

        if (!$kelas_valid || !$guru_valid) {
            $error = "Kelas atau guru yang dipilih tidak tersedia.";
        } elseif (!$tanggal_valid) {
            $error = "Format tanggal ujian tidak valid.";
        } else {
            $tanggal_db = str_replace("T", " ", $tanggal_ujian) . ":00";


            $stmt = $conn->prepare(
                "INSERT INTO ujian
                    (nama_ujian, id_kelas, durasi_waktu, id_guru, tanggal_ujian)
                 VALUES (?, ?, ?, ?, ?)"
            );
 
            $stmt->bind_param(
                "siiis",
                $nama_ujian,
                $id_kelas,
                $durasi_waktu,
                $id_guru,
                $tanggal_db
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: ujian_admin.php?status=tambah_berhasil");
                exit;
            } else {
                $error = "Ujian gagal ditambahkan. Periksa struktur tabel database.";
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ujian - ExamHub Admin</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Tambah Ujian</span>
        <a href="ujian_admin.php" class="btn btn-light btn-sm">
            Kembali
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="card shadow-sm mx-auto" style="max-width: 700px;">
        <div class="card-body p-4">
            <h3 class="mb-1">Tambah Ujian</h3>
            <p class="text-muted mb-4">
                Lengkapi informasi ujian yang akan dibuat.
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <?php if ($daftar_kelas->num_rows === 0 || $daftar_guru->num_rows === 0): ?>
                <div class="alert alert-warning">
                    Pastikan sudah ada minimal satu kelas dan satu akun guru sebelum membuat ujian.
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama_ujian" class="form-label">Nama Ujian</label>
                    <input
                        type="text"
                        id="nama_ujian"
                        name="nama_ujian"
                        class="form-control"
                        value="<?= htmlspecialchars($nama_ujian, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="id_kelas" class="form-label">Kelas</label>
                    <select id="id_kelas" name="id_kelas" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php while ($kelas = $daftar_kelas->fetch_assoc()): ?>
                            <option
                                value="<?= (int) $kelas["id_kelas"] ?>"
                                <?= (string) $id_kelas === (string) $kelas["id_kelas"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($kelas["nama_kelas"], ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="id_guru" class="form-label">Guru Pembuat</label>
                    <select id="id_guru" name="id_guru" class="form-select" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php while ($guru = $daftar_guru->fetch_assoc()): ?>
                            <option
                                value="<?= (int) $guru["id_guru"] ?>"
                                <?= (string) $id_guru === (string) $guru["id_guru"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($guru["nama_guru"], ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="durasi_waktu" class="form-label">
                        Durasi (menit)
                    </label>
                    <input
                        type="number"
                        id="durasi_waktu"
                        name="durasi_waktu"
                        class="form-control"
                        min="1"
                        value="<?= htmlspecialchars((string) $durasi_waktu, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="tanggal_ujian" class="form-label">
                        Tanggal dan Waktu Ujian
                    </label>
                    <input
                        type="datetime-local"
                        id="tanggal_ujian"
                        name="tanggal_ujian"
                        class="form-control"
                        value="<?= htmlspecialchars($tanggal_ujian, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    <?= ($daftar_kelas->num_rows === 0 || $daftar_guru->num_rows === 0) ? "disabled" : "" ?>
                >
                    Simpan Ujian
                </button>

                <a href="ujian_admin.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>

</body>
</html>

