
<?php
session_start();

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

require_once "../koneksi.php";

$error = "";
$nama_siswa = "";
$username = "";
$id_kelas = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_siswa = trim($_POST["nama_siswa"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $id_kelas = $_POST["id_kelas"] ?? "";

    if (
        $nama_siswa === "" ||
        $username === "" ||
        $password === "" ||
        $id_kelas === ""
    ) {
        $error = "Semua kolom wajib diisi.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif (!ctype_digit($id_kelas)) {
        $error = "Kelas yang dipilih tidak valid.";
    } else {
        // Pastikan username belum digunakan
        $stmt = $conn->prepare(
            "SELECT id_siswa FROM siswa WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $hasil = $stmt->get_result();

        if ($hasil->num_rows > 0) {
            $error = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            // Pastikan kelas tersedia
            $stmt_kelas = $conn->prepare(
                "SELECT id_kelas FROM kelas WHERE id_kelas = ? LIMIT 1"
            );
            $stmt_kelas->bind_param("i", $id_kelas);
            $stmt_kelas->execute();
            $hasil_kelas = $stmt_kelas->get_result();

            if ($hasil_kelas->num_rows === 0) {
                $error = "Kelas tidak ditemukan.";
            } else {
                /*
                 * Sementara mengikuti format login siswa saat ini,
                 * yang masih menggunakan password teks biasa.
                 */
                $stmt_tambah = $conn->prepare(
                    "INSERT INTO siswa
                    (nama_siswa, username, password, id_kelas)
                    VALUES (?, ?, ?, ?)"
                );

                $id_kelas_int = (int) $id_kelas;
                $stmt_tambah->bind_param(
                    "sssi",
                    $nama_siswa,
                    $username,
                    $password,
                    $id_kelas_int
                );

                if ($stmt_tambah->execute()) {
                    header("Location: siswa_admin.php?status=tambah_berhasil");
                    exit;
                } else {
                    $error = "Gagal menambahkan siswa. Silakan coba lagi.";
                }

                $stmt_tambah->close();
            }

            $stmt_kelas->close();
        }

        $stmt->close();
    }
}

// Mengambil daftar kelas untuk pilihan
$daftar_kelas = $conn->query(
    "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Siswa - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand fw-bold">ExamHub Admin</span>
        <a href="logout_admin.php" class="btn btn-outline-light btn-sm">
            Logout
        </a>
    </div>
</nav>

<div class="container py-4" style="max-width: 700px;">

    <div class="mb-4">
        <h3>Tambah Akun Siswa</h3>
        <p class="text-muted mb-0">
            Isi informasi siswa untuk membuat akun baru.
        </p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <?php if ($daftar_kelas && $daftar_kelas->num_rows === 0): ?>
                <div class="alert alert-warning">
                    Belum ada data kelas. Tambahkan kelas terlebih dahulu.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nama_siswa" class="form-label">
                        Nama Siswa
                    </label>
                    <input
                        type="text"
                        name="nama_siswa"
                        id="nama_siswa"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($nama_siswa, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">
                        Username
                    </label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        maxlength="50"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password Awal
                    </label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        minlength="6"
                        autocomplete="new-password"
                        required
                    >
                    <div class="form-text">
                        Minimal 6 karakter. Siswa sebaiknya mengganti password
                        setelah login pertama.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="id_kelas" class="form-label">
                        Kelas
                    </label>
                    <select
                        name="id_kelas"
                        id="id_kelas"
                        class="form-select"
                        required
                    >
                        <option value="">-- Pilih Kelas --</option>

                        <?php if ($daftar_kelas): ?>
                            <?php while ($kelas = $daftar_kelas->fetch_assoc()): ?>
                                <option
                                    value="<?= (int) $kelas["id_kelas"] ?>"
                                    <?= (string) $id_kelas === (string) $kelas["id_kelas"] ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($kelas["nama_kelas"], ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                        <?= (!$daftar_kelas || $daftar_kelas->num_rows === 0) ? "disabled" : "" ?>
                    >
                        Simpan Siswa
                    </button>

                    <a href="siswa_admin.php" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </form>

        </div>
    </div>

</div>

</body>
</html>

