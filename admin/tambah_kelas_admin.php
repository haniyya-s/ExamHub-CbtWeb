
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

$error = "";
$nama_kelas = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_kelas = trim($_POST["nama_kelas"] ?? "");

    if ($nama_kelas === "") {
        $error = "Nama kelas wajib diisi.";
    } else {
        // Periksa apakah nama kelas sudah ada
        $cek = $conn->prepare(
            "SELECT id_kelas
             FROM kelas
             WHERE nama_kelas = ?
             LIMIT 1"
        );
        $cek->bind_param("s", $nama_kelas);
        $cek->execute();
        $hasil = $cek->get_result();

        if ($hasil->num_rows > 0) {
            $error = "Nama kelas sudah terdaftar.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO kelas (nama_kelas) VALUES (?)"
            );
            $stmt->bind_param("s", $nama_kelas);

            if ($stmt->execute()) {
                $stmt->close();
                $cek->close();

                header("Location: kelas_admin.php?status=tambah_berhasil");
                exit;
            } else {
                $error = "Kelas gagal ditambahkan.";
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
    <title>Tambah Kelas - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Tambah Kelas</span>
        <a href="kelas_admin.php" class="btn btn-light btn-sm">
            Kembali
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <h3 class="mb-1">Tambah Kelas</h3>
            <p class="text-muted mb-4">
                Masukkan nama kelas baru.
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label for="nama_kelas" class="form-label">
                        Nama Kelas
                    </label>

                    <input
                        type="text"
                        id="nama_kelas"
                        name="nama_kelas"
                        class="form-control"
                        placeholder="Contoh: Kelas 8A"
                        value="<?= htmlspecialchars($nama_kelas, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Kelas
                </button>

                <a href="kelas_admin.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>

</body>
</html>

