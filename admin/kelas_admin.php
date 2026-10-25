
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

// Pencarian kelas
$keyword = trim($_GET["keyword"] ?? "");

$sql = "SELECT id_kelas, nama_kelas FROM kelas";

if ($keyword !== "") {
    $sql .= " WHERE nama_kelas LIKE ?";
}

$sql .= " ORDER BY id_kelas DESC";

$stmt = $conn->prepare($sql);

if ($keyword !== "") {
    $search = "%" . $keyword . "%";
    $stmt->bind_param("s", $search);
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
    <title>Data Kelas - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Data Kelas</span>
        <a href="dashboard_admin.php" class="btn btn-light btn-sm">
            Dashboard
        </a>
    </div>
</nav>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Data Kelas</h3>
            <p class="text-muted mb-0">
                Kelola daftar kelas yang tersedia.
            </p>
        </div>

        <a href="tambah_kelas_admin.php" class="btn btn-primary">
            + Tambah Kelas
        </a>
    </div>

    <?php if ($status === "tambah_berhasil"): ?>
        <div class="alert alert-success">
            Kelas berhasil ditambahkan.
        </div>
    <?php elseif ($status === "edit_berhasil"): ?>
        <div class="alert alert-success">
            Data kelas berhasil diperbarui.
        </div>
    <?php elseif ($status === "hapus_berhasil"): ?>
        <div class="alert alert-success">
            Kelas berhasil dihapus.
        </div>
    <?php elseif ($status === "kelas_digunakan"): ?>
        <div class="alert alert-warning">
            Kelas tidak dapat dihapus karena masih digunakan oleh siswa atau ujian.
        </div>
    <?php elseif ($status === "gagal"): ?>
        <div class="alert alert-danger">
            Operasi gagal. Periksa kembali data kelas.
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
                        placeholder="Cari nama kelas..."
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, "UTF-8") ?>"
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
                            <th style="width: 80px;">No.</th>
                            <th>Nama Kelas</th>
                            <th style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($kelas = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $kelas["nama_kelas"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="edit_kelas_admin.php?id=<?= (int) $kelas["id_kelas"] ?>"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="hapus_kelas_admin.php"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus kelas ini?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id_kelas"
                                            value="<?= (int) $kelas["id_kelas"] ?>"
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
                            <td colspan="3" class="text-center text-muted">
                                Data kelas tidak ditemukan.
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

