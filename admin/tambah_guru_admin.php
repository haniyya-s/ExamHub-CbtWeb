
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

$error = "";
$nama_guru = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_guru = trim($_POST["nama_guru"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nama_guru === "" || $username === "" || $password === "") {
        $error = "Semua kolom wajib diisi.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Periksa apakah username sudah digunakan
        $cek = $conn->prepare(
            "SELECT id_guru FROM guru WHERE username = ? LIMIT 1"
        );
        $cek->bind_param("s", $username);
        $cek->execute();
        $hasil = $cek->get_result();

        if ($hasil->num_rows > 0) {
            $error = "Username sudah digunakan. Gunakan username lain.";
        } else {
            // Hash password agar sesuai dengan password_verify()
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO guru (nama_guru, username, password)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param(
                "sss",
                $nama_guru,
                $username,
                $password_hash
            );

            if ($stmt->execute()) {
                header("Location: guru_admin.php?status=tambah_berhasil");
                exit;
            } else {
                $error = "Akun guru gagal ditambahkan.";
            }

            $stmt->close();
        }

        $cek->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Guru - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Tambah Guru</span>
        <a href="guru_admin.php" class="btn btn-light btn-sm">
            Kembali
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <h3 class="mb-1">Tambah Guru</h3>
            <p class="text-muted mb-4">
                Masukkan informasi untuk membuat akun guru baru.
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama_guru" class="form-label">
                        Nama Guru
                    </label>
                    <input
                        type="text"
                        id="nama_guru"
                        name="nama_guru"
                        class="form-control"
                        value="<?= htmlspecialchars($nama_guru, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        minlength="6"
                        required
                    >
                    <div class="form-text">
                        Minimal 6 karakter.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Guru
                    </button>
                    <a href="guru_admin.php" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>

