
<?php
session_start();

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

require_once "../koneksi.php";

$error = "";
$id_siswa = 0;
$nama_siswa = "";
$username = "";
$id_kelas = "";

// Memeriksa ID siswa dari URL
$id_siswa = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_siswa = filter_input(
        INPUT_POST,
        "id_siswa",
        FILTER_VALIDATE_INT
    );

    $nama_siswa = trim($_POST["nama_siswa"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $id_kelas = trim($_POST["id_kelas"] ?? "");

    if (!$id_siswa || $id_siswa < 1) {
        header("Location: siswa_admin.php?status=id_tidak_valid");
        exit;
    }

    if (
        $nama_siswa === "" ||
        $username === "" ||
        $id_kelas === ""
    ) {
        $error = "Semua kolom wajib diisi.";
    } elseif (!ctype_digit($id_kelas)) {
        $error = "Kelas yang dipilih tidak valid.";
    } else {
        // Pastikan siswa tersedia
        $stmt = $conn->prepare(
            "SELECT id_siswa FROM siswa WHERE id_siswa = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id_siswa);
        $stmt->execute();
        $cek_siswa = $stmt->get_result();

        if ($cek_siswa->num_rows === 0) {
            $error = "Data siswa tidak ditemukan.";
        }

        $stmt->close();

        // Pastikan username tidak digunakan siswa lain
        if ($error === "") {
            $stmt = $conn->prepare(
                "SELECT id_siswa
                 FROM siswa
                 WHERE username = ? AND id_siswa <> ?
                 LIMIT 1"
            );
            $stmt->bind_param("si", $username, $id_siswa);
            $stmt->execute();
            $cek_username = $stmt->get_result();

            if ($cek_username->num_rows > 0) {
                $error = "Username sudah digunakan akun siswa lain.";
            }

            $stmt->close();
        }

        // Pastikan kelas tersedia
        if ($error === "") {
            $id_kelas_int = (int) $id_kelas;

            $stmt = $conn->prepare(
                "SELECT id_kelas FROM kelas WHERE id_kelas = ? LIMIT 1"
            );
            $stmt->bind_param("i", $id_kelas_int);
            $stmt->execute();
            $cek_kelas = $stmt->get_result();

            if ($cek_kelas->num_rows === 0) {
                $error = "Kelas tidak ditemukan.";
            }

            $stmt->close();
        }

        // Simpan perubahan tanpa mengubah password
        if ($error === "") {
            $stmt = $conn->prepare(
                "UPDATE siswa
                 SET nama_siswa = ?, username = ?, id_kelas = ?
                 WHERE id_siswa = ?"
            );

            $stmt->bind_param(
                "ssii",
                $nama_siswa,
                $username,
                $id_kelas_int,
                $id_siswa
            );

            if ($stmt->execute()) {
                $stmt->close();

                header("Location: siswa_admin.php?status=edit_berhasil");
                exit;
            } else {
                $error = "Gagal memperbarui data siswa.";
                $stmt->close();
            }
        }
    }
}

// Jika belum POST, ambil data siswa dari database
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if (!$id_siswa || $id_siswa < 1) {
        header("Location: siswa_admin.php?status=id_tidak_valid");
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT id_siswa, nama_siswa, username, id_kelas
         FROM siswa
         WHERE id_siswa = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        header("Location: siswa_admin.php?status=siswa_tidak_ditemukan");
        exit;
    }

    $siswa = $result->fetch_assoc();

    $nama_siswa = $siswa["nama_siswa"];
    $username = $siswa["username"];
    $id_kelas = (string) $siswa["id_kelas"];

    $stmt->close();
}

// Ambil daftar kelas
$daftar_kelas = $conn->query(
    "SELECT id_kelas, nama_kelas
     FROM kelas
     ORDER BY nama_kelas ASC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Siswa - ExamHub Admin</title>

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

<div class="container py-4" style="max-width: 700px;">

    <div class="mb-4">
        <h3>Edit Data Siswa</h3>
        <p class="text-muted mb-0">
            Perbarui informasi akun siswa di bawah ini.
        </p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="edit_siswa_admin.php">

                <input
                    type="hidden"
                    name="id_siswa"
                    value="<?= (int) $id_siswa ?>"
                >

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
                                    <?= htmlspecialchars(
                                        $kelas["nama_kelas"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a href="siswa_admin.php" class="btn btn-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>

