
<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya Admin yang bisa mengakses
if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

// Penghapusan hanya boleh melalui POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kelas_admin.php");
    exit;
}

$id_kelas = filter_input(INPUT_POST, "id_kelas", FILTER_VALIDATE_INT);

if (!$id_kelas || $id_kelas < 1) {
    header("Location: kelas_admin.php?status=gagal");
    exit;
}

$conn->begin_transaction();

try {
    // Kunci data kelas agar tidak berubah saat diperiksa
    $cek = $conn->prepare(
        "SELECT id_kelas
         FROM kelas
         WHERE id_kelas = ?
         FOR UPDATE"
    );
    $cek->bind_param("i", $id_kelas);
    $cek->execute();

    $kelas = $cek->get_result()->fetch_assoc();
    $cek->close();

    if (!$kelas) {
        throw new Exception("gagal");
    }

    // Periksa apakah kelas masih digunakan siswa
    $cek_siswa = $conn->prepare(
        "SELECT id_siswa
         FROM siswa
         WHERE id_kelas = ?
         LIMIT 1"
    );
    $cek_siswa->bind_param("i", $id_kelas);
    $cek_siswa->execute();

    $ada_siswa = $cek_siswa->get_result()->num_rows > 0;
    $cek_siswa->close();

    // Periksa apakah kelas masih digunakan ujian
    $cek_ujian = $conn->prepare(
        "SELECT id_ujian
         FROM ujian
         WHERE id_kelas = ?
         LIMIT 1"
    );
    $cek_ujian->bind_param("i", $id_kelas);
    $cek_ujian->execute();

    $ada_ujian = $cek_ujian->get_result()->num_rows > 0;
    $cek_ujian->close();

    if ($ada_siswa || $ada_ujian) {
        $conn->rollback();

        header("Location: kelas_admin.php?status=kelas_digunakan");
        exit;
    }

    // Hapus kelas jika tidak memiliki relasi siswa atau ujian
    $hapus = $conn->prepare(
        "DELETE FROM kelas WHERE id_kelas = ?"
    );
    $hapus->bind_param("i", $id_kelas);
    $hapus->execute();

    if ($hapus->affected_rows !== 1) {
        $hapus->close();
        throw new Exception("gagal");
    }

    $hapus->close();
    $conn->commit();

    header("Location: kelas_admin.php?status=hapus_berhasil");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    header("Location: kelas_admin.php?status=gagal");
    exit;
}
?>
