
<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["id_admin"])) {
    header("Location: login_admin.php");
    exit;
}

// Penghapusan hanya boleh melalui POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ujian_admin.php");
    exit;
}

$id_ujian = filter_input(INPUT_POST, "id_ujian", FILTER_VALIDATE_INT);

if (!$id_ujian || $id_ujian < 1) {
    header("Location: ujian_admin.php?status=gagal");
    exit;
}

$conn->begin_transaction();

try {
    // Kunci data ujian selama proses penghapusan
    $cek_ujian = $conn->prepare(
        "SELECT id_ujian
         FROM ujian
         WHERE id_ujian = ?
         FOR UPDATE"
    );
    $cek_ujian->bind_param("i", $id_ujian);
    $cek_ujian->execute();

    $ujian_ada = $cek_ujian->get_result()->num_rows > 0;
    $cek_ujian->close();

    if (!$ujian_ada) {
        $conn->rollback();
        header("Location: ujian_admin.php?status=gagal");
        exit;
    }

    // Periksa apakah siswa sudah memiliki jawaban
    $cek_jawaban = $conn->prepare(
        "SELECT COUNT(*) AS jumlah
         FROM jawaban_siswa
         WHERE id_ujian = ?"
    );
    $cek_jawaban->bind_param("i", $id_ujian);
    $cek_jawaban->execute();

    $jumlah_jawaban = (int) $cek_jawaban
        ->get_result()
        ->fetch_assoc()["jumlah"];

    $cek_jawaban->close();

    // Periksa apakah ujian sudah memiliki riwayat selesai
    $cek_selesai = $conn->prepare(
        "SELECT COUNT(*) AS jumlah
         FROM ujian_selesai
         WHERE id_ujian = ?"
    );
    $cek_selesai->bind_param("i", $id_ujian);
    $cek_selesai->execute();

    $jumlah_selesai = (int) $cek_selesai
        ->get_result()
        ->fetch_assoc()["jumlah"];

    $cek_selesai->close();

    if ($jumlah_jawaban > 0 || $jumlah_selesai > 0) {
        $conn->rollback();
        header("Location: ujian_admin.php?status=ujian_digunakan");
        exit;
    }

    // Hapus soal yang terkait dengan ujian ini
    $hapus_soal = $conn->prepare(
        "DELETE FROM soal WHERE id_ujian = ?"
    );
    $hapus_soal->bind_param("i", $id_ujian);
    $hapus_soal->execute();
    $hapus_soal->close();

    // Hapus data ujiannya
    $hapus_ujian = $conn->prepare(
        "DELETE FROM ujian WHERE id_ujian = ?"
    );
    $hapus_ujian->bind_param("i", $id_ujian);
    $hapus_ujian->execute();

    if ($hapus_ujian->affected_rows !== 1) {
        $hapus_ujian->close();
        throw new Exception("Data ujian gagal dihapus.");
    }

    $hapus_ujian->close();

    $conn->commit();

    header("Location: ujian_admin.php?status=hapus_berhasil");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    header("Location: ujian_admin.php?status=gagal");
    exit;
}
