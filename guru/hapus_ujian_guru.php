
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ujian_guru.php');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];
$idUjian = (int) ($_POST['id_ujian'] ?? 0);

function kembali($status) {
    header('Location: ujian_guru.php?status=' . urlencode($status));
    exit;
}

if ($idUjian <= 0) {
    kembali('hapus_gagal');
}

// Pastikan ujian dimiliki oleh guru yang login.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_ujian
     FROM ujian
     WHERE id_ujian = ? AND id_guru = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 'ii', $idUjian, $idGuru);
mysqli_stmt_execute($stmt);
$ujianValid = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujianValid) {
    kembali('hapus_gagal');
}

try {
    mysqli_begin_transaction($conn);

    // Kunci baris ujian selama pemeriksaan dan penghapusan.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_ujian
         FROM ujian
         WHERE id_ujian = ? AND id_guru = ?
         FOR UPDATE"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $idUjian, $idGuru);
    mysqli_stmt_execute($stmt);
    $ujianTerkunci = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$ujianTerkunci) {
        throw new RuntimeException('Ujian tidak ditemukan.');
    }

    // Lindungi jawaban siswa.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS jumlah
         FROM jawaban_siswa
         WHERE id_ujian = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idUjian);
    mysqli_stmt_execute($stmt);
    $dataJawaban = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Lindungi catatan penyelesaian ujian.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS jumlah
         FROM ujian_selesai
         WHERE id_ujian = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idUjian);
    mysqli_stmt_execute($stmt);
    $dataSelesai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (
        (int) $dataJawaban['jumlah'] > 0 ||
        (int) $dataSelesai['jumlah'] > 0
    ) {
        mysqli_rollback($conn);
        kembali('hapus_memiliki_data');
    }

    // Hapus soal terkait setelah dipastikan tidak ada riwayat pengerjaan.
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM soal WHERE id_ujian = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idUjian);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Hapus ujian milik guru yang bersangkutan.
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM ujian
         WHERE id_ujian = ? AND id_guru = ?"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $idUjian, $idGuru);
    mysqli_stmt_execute($stmt);

    $berhasil = mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);

    if (!$berhasil) {
        throw new RuntimeException('Ujian gagal dihapus.');
    }

    mysqli_commit($conn);
    kembali('hapus_berhasil');

} catch (Throwable $e) {
    mysqli_rollback($conn);
    kembali('hapus_gagal');
}
