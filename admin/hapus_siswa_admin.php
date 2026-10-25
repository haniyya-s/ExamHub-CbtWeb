
<?php
session_start();

// Pastikan hanya admin yang sudah login dapat menghapus siswa
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

require_once "../koneksi.php";

// Penghapusan hanya boleh melalui metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: siswa_admin.php");
    exit;
}

// Ambil dan validasi ID siswa
$id_siswa = filter_input(
    INPUT_POST,
    "id_siswa",
    FILTER_VALIDATE_INT
);

if (!$id_siswa || $id_siswa < 1) {
    header("Location: siswa_admin.php?status=id_tidak_valid");
    exit;
}

// Pastikan siswa tersedia
$stmt = $conn->prepare(
    "SELECT id_siswa FROM siswa WHERE id_siswa = ? LIMIT 1"
);

$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();

    header("Location: siswa_admin.php?status=siswa_tidak_ditemukan");
    exit;
}

$stmt->close();

// Hapus data terkait dan akun dalam satu transaksi
$conn->begin_transaction();

try {
    // Hapus jawaban siswa
    $stmt = $conn->prepare(
        "DELETE FROM jawaban_siswa WHERE id_siswa = ?"
    );
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();
    $stmt->close();

    // Hapus riwayat ujian selesai
    $stmt = $conn->prepare(
        "DELETE FROM ujian_selesai WHERE id_siswa = ?"
    );
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();
    $stmt->close();

    // Hapus akun siswa
    $stmt = $conn->prepare(
        "DELETE FROM siswa WHERE id_siswa = ?"
    );
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Akun siswa tidak berhasil dihapus.");
    }

    $stmt->close();

    $conn->commit();

    header("Location: siswa_admin.php?status=hapus_berhasil");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    error_log("Penghapusan siswa gagal: " . $e->getMessage());

    header("Location: siswa_admin.php?status=hapus_gagal");
    exit;
}
?>
