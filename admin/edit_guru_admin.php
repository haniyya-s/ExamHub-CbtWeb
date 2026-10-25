
<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

$error = "";
$id_guru = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_guru || $id_guru < 1) {
    header("Location: guru_admin.php?status=gagal");
    exit;
}

// Ambil data guru
$stmt = $conn->prepare(
    "SELECT id_guru, nama_guru, username
     FROM guru
     WHERE id_guru = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();
$guru = $result->fetch_assoc();
$stmt->close();

if (!$guru) {
    header("Location: guru_admin.php?status=gagal");
    exit;
}

$nama_guru = $guru["nama_guru"];
$username = $guru["username"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_guru = trim($_POST["nama_guru"] ?? "");
    $username = trim($_POST["username"] ?? "");

    if ($nama_guru === "" || $username === "") {
        $error = "Nama guru dan username wajib diisi.";
    } else {
        // Pastikan username tidak dipakai guru lain
        $cek = $conn->prepare(
            "SELECT id_guru
             FROM guru
             WHERE username = ? AND id_guru != ?
             LIMIT 1"
        );
        $cek->bind_param("si", $username, $id_guru);
        $cek->execute();
        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {
            $error = "Username sudah digunakan guru lain.";
        } else {
            $update = $conn->prepare(
                "UPDATE guru
                 SET nama_guru = ?, username = ?
                 WHERE id_guru = ?"
            );
            $update->bind_param("ssi", $nama_guru, $username, $id_guru);

            if ($update->execute()) {
                $update->close();
                $cek->close();

                header("Location: guru_admin.php?status=edit_berhasil");
                exit;
            } else {
                $error = "Data guru gagal diperbarui.";
            }

            $update->close();
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
    <title>Edit Guru - ExamHub Admin</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Edit Guru</span>
        <a href="guru_admin.php" class="btn btn-light btn-sm">
            Kembali
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <h3 class="mb-1">Edit Data Guru</h3>
            <p class="text-muted mb-4">
                Perbarui nama atau username guru.
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

                <div class="mb-4">
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

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
                <a href="guru_admin.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>

</body>
</html>

