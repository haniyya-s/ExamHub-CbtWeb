
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

// Penghapusan hanya boleh melalui form POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: guru_admin.php");
    exit;
}

$id_guru = filter_input(INPUT_POST, "id_guru", FILTER_VALIDATE_INT);

if (!$id_guru || $id_guru < 1) {
    header("Location: guru_admin.php?status=gagal");
    exit;
}

// Pastikan akun guru tersedia
$cek = $conn->prepare(
    "SELECT id_guru FROM guru WHERE id_guru = ? LIMIT 1"
);
$cek->bind_param("i", $id_guru);
$cek->execute();
$result = $cek->get_result();
$guru = $result->fetch_assoc();
$cek->close();

if (!$guru) {
    header("Location: guru_admin.php?status=gagal");
    exit;
}

// Hapus akun guru
$stmt = $conn->prepare(
    "DELETE FROM guru WHERE id_guru = ?"
);
$stmt->bind_param("i", $id_guru);

if ($stmt->execute() && $stmt->affected_rows === 1) {
    $stmt->close();
    header("Location: guru_admin.php?status=hapus_berhasil");
    exit;
}

$stmt->close();
header("Location: guru_admin.php?status=gagal");
exit;
?>
