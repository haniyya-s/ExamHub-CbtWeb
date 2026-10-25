
<?php
session_start();

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

require_once "../koneksi.php";

$keyword = trim($_GET["keyword"] ?? "");
$status = $_GET["status"] ?? "";

// Mengambil data siswa dan nama kelas
$sql = "SELECT
            s.id_siswa,
            s.nama_siswa,
            s.username,
            s.id_kelas,
            k.nama_kelas
        FROM siswa s
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas";

if ($keyword !== "") {
    $sql .= " WHERE s.nama_siswa LIKE ?
              OR s.username LIKE ?
              OR k.nama_kelas LIKE ?";
}

$sql .= " ORDER BY s.nama_siswa ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query data siswa gagal disiapkan.");
}

if ($keyword !== "") {
    $cari = "%" . $keyword . "%";
    $stmt->bind_param("sss", $cari, $cari, $cari);
}

$stmt->execute();
$result = $stmt->get_result();
$jumlah_siswa = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Siswa - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a href="dashboard_admin.php"
           class="navbar-brand fw-bold text-decoration-none">
            ExamHub Admin
        </a>

        <a href="logout_admin.php" class="btn btn-outline-light btn-sm">
            Logout
        </a>
    </div>
</nav>

<div class="container py-4">

    <!-- Judul dan tombol tambah -->
    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-2 mb-4">

        <div>
            <h3 class="mb-1">Data Siswa</h3>
            <p class="text-muted mb-0">
                Lihat dan kelola akun siswa.
            </p>
        </div>

        <a href="tambah_siswa_admin.php" class="btn btn-success">
            + Tambah Siswa
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if ($status === "tambah_berhasil"): ?>
        <div class="alert alert-success">
            Akun siswa berhasil ditambahkan.
        </div>
    <?php elseif ($status === "edit_berhasil"): ?>
        <div class="alert alert-success">
            Data siswa berhasil diperbarui.
        </div>
    <?php elseif ($status === "hapus_berhasil"): ?>
        <div class="alert alert-success">
            Data siswa berhasil dihapus.
        </div>
    <?php elseif ($status === "hapus_gagal"): ?>
        <div class="alert alert-danger">
            Data siswa gagal dihapus.
        </div>
    <?php elseif ($status === "id_tidak_valid"): ?>
        <div class="alert alert-warning">
            ID siswa tidak valid.
        </div>
    <?php elseif ($status === "siswa_tidak_ditemukan"): ?>
        <div class="alert alert-warning">
            Data siswa tidak ditemukan.
        </div>
    <?php endif; ?>

    <!-- Form pencarian -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" action="siswa_admin.php">
                <div class="row g-2">

                    <div class="col-md-9">
                        <input
                            type="text"
                            name="keyword"
                            class="form-control"
                            placeholder="Cari nama, username, atau kelas..."
                            value="<?= htmlspecialchars(
                                $keyword,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit"
                                class="btn btn-primary w-100">
                            Cari
                        </button>

                        <a href="siswa_admin.php"
                           class="btn btn-secondary w-100">
                            Reset
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- Tabel data siswa -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between
                        align-items-center mb-3">

                <h5 class="mb-0">Daftar Siswa</h5>

                <span class="badge text-bg-primary">
                    <?= $jumlah_siswa ?> siswa
                </span>

            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped
                              table-hover align-middle">

                    <thead class="table-primary">
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Nama Siswa</th>
                            <th>Username</th>
                            <th>Kelas</th>
                            <th style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($jumlah_siswa > 0): ?>

                            <?php $no = 1; ?>

                            <?php while ($siswa = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $siswa["nama_siswa"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $siswa["username"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $siswa["nama_kelas"] ?? "Belum ada kelas",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">

                                            <!-- Tombol Edit -->
                                            <a
                                                href="edit_siswa_admin.php?id=<?= (int) $siswa["id_siswa"] ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Edit
                                            </a>

                                            <!-- Tombol Hapus -->
                                            <form
                                                action="hapus_siswa_admin.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus akun siswa ini beserta riwayat jawaban dan ujiannya?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id_siswa"
                                                    value="<?= (int) $siswa["id_siswa"] ?>"
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
                                <td colspan="5"
                                    class="text-center text-muted py-4">
                                    <?php if ($keyword !== ""): ?>
                                        Data siswa tidak ditemukan.
                                    <?php else: ?>
                                        Belum ada data siswa.
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

            <!-- Kembali ke dashboard -->
            <a href="dashboard_admin.php"
               class="btn btn-secondary mt-2">
                &larr; Kembali ke Dashboard
            </a>

        </div>
    </div>

</div>

</body>
</html>

