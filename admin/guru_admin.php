
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

// Pencarian guru
$keyword = trim($_GET["keyword"] ?? "");

$sql = "SELECT id_guru, nama_guru, username FROM guru";
if ($keyword !== "") {
    $sql .= " WHERE nama_guru LIKE ? OR username LIKE ?";
}
$sql .= " ORDER BY id_guru DESC";

$stmt = $conn->prepare($sql);

if ($keyword !== "") {
    $search = "%" . $keyword . "%";
    $stmt->bind_param("ss", $search, $search);
}

$stmt->execute();
$result = $stmt->get_result();

$status = $_GET["status"] ?? "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Data Guru</span>
        <a href="dashboard_admin.php" class="btn btn-light btn-sm">
            Dashboard
        </a>
    </div>
</nav>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Data Guru</h3>
            <p class="text-muted mb-0">
                Kelola akun guru yang terdaftar.
            </p>
        </div>

        <a href="tambah_guru_admin.php" class="btn btn-primary">
            + Tambah Guru
        </a>
    </div>

    <?php if ($status === "tambah_berhasil"): ?>
        <div class="alert alert-success">
            Akun guru berhasil ditambahkan.
        </div>
    <?php elseif ($status === "edit_berhasil"): ?>
        <div class="alert alert-success">
            Data guru berhasil diperbarui.
        </div>
    <?php elseif ($status === "hapus_berhasil"): ?>
        <div class="alert alert-success">
            Akun guru berhasil dihapus.
        </div>
    <?php elseif ($status === "gagal"): ?>
        <div class="alert alert-danger">
            Operasi gagal. Silakan periksa kembali data guru.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Cari nama atau username guru..."
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        Cari
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>Nama Guru</th>
                            <th>Username</th>
                            <th style="min-width: 190px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($guru = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $guru["nama_guru"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $guru["username"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="edit_guru_admin.php?id=<?= (int) $guru["id_guru"] ?>"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="hapus_guru_admin.php"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus akun guru ini?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id_guru"
                                            value="<?= (int) $guru["id_guru"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Data guru tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="dashboard_admin.php" class="btn btn-secondary">
                Kembali
            </a>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

