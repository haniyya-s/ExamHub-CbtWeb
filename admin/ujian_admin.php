
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

$keyword = trim($_GET["keyword"] ?? "");

$sql = "
    SELECT
        u.id_ujian,
        u.nama_ujian,
        u.durasi_waktu,
        u.tanggal_ujian,
        k.nama_kelas,
        g.nama_guru
    FROM ujian u
    LEFT JOIN kelas k ON u.id_kelas = k.id_kelas
    LEFT JOIN guru g ON u.id_guru = g.id_guru
";

if ($keyword !== "") {
    $sql .= "
        WHERE u.nama_ujian LIKE ?
        OR k.nama_kelas LIKE ?
        OR g.nama_guru LIKE ?
    ";
}

$sql .= " ORDER BY u.id_ujian DESC";

$stmt = $conn->prepare($sql);

if ($keyword !== "") {
    $search = "%" . $keyword . "%";
    $stmt->bind_param("sss", $search, $search, $search);
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
    <title>Data Ujian - ExamHub Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">ExamHub Admin - Data Ujian</span>
        <a href="dashboard_admin.php" class="btn btn-light btn-sm">
            Dashboard
        </a>
    </div>
</nav>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Data Ujian</h3>
            <p class="text-muted mb-0">
                Kelola daftar ujian dalam sistem ExamHub.
            </p>
        </div>

        <a href="tambah_ujian_admin.php" class="btn btn-primary">
            + Tambah Ujian
        </a>
    </div>

    <?php if ($status === "tambah_berhasil"): ?>
        <div class="alert alert-success">Ujian berhasil ditambahkan.</div>
    <?php elseif ($status === "edit_berhasil"): ?>
        <div class="alert alert-success">Data ujian berhasil diperbarui.</div>
    <?php elseif ($status === "hapus_berhasil"): ?>
        <div class="alert alert-success">Ujian berhasil dihapus.</div>
    <?php elseif ($status === "ujian_digunakan"): ?>
        <div class="alert alert-warning">
            Ujian tidak dapat dihapus karena sudah memiliki data jawaban siswa.
        </div>
    <?php elseif ($status === "gagal"): ?>
        <div class="alert alert-danger">
            Operasi gagal. Silakan periksa kembali data ujian.
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
                        placeholder="Cari nama ujian, kelas, atau guru..."
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
                            <th>No.</th>
                            <th>Nama Ujian</th>
                            <th>Kelas</th>
                            <th>Guru</th>
                            <th>Durasi</th>
                            <th>Tanggal Ujian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($ujian = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $ujian["nama_ujian"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $ujian["nama_kelas"] ?? "-",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $ujian["nama_guru"] ?? "-",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int) $ujian["durasi_waktu"] ?> menit
                                </td>

                                <td>
                                    <?php if (!empty($ujian["tanggal_ujian"])): ?>
                                        <?= htmlspecialchars(
                                            $ujian["tanggal_ujian"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td style="min-width: 145px;">
                                    <a
                                        href="edit_ujian_admin.php?id=<?= (int) $ujian["id_ujian"] ?>"
                                        class="btn btn-warning btn-sm mb-1"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="hapus_ujian_admin.php"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus ujian ini?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id_ujian"
                                            value="<?= (int) $ujian["id_ujian"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm mb-1"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Data ujian tidak ditemukan.
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

</body>
</html>

