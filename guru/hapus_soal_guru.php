
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

// Penghapusan hanya boleh melalui form POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: soal_guru.php');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];
$idSoal = (int) ($_POST['id_soal'] ?? 0);
$idUjian = (int) ($_POST['id_ujian'] ?? 0);

function kembali($idUjian, $status) {
    header(
        'Location: soal_guru.php?id_ujian=' . (int) $idUjian .
        '&status=' . urlencode($status)
    );
    exit;
}

if ($idSoal <= 0 || $idUjian <= 0) {
    kembali($idUjian, 'hapus_gagal');
}

// Pastikan soal berasal dari ujian milik guru yang login.
$stmt = mysqli_prepare(
    $conn,
    "SELECT s.id_soal
     FROM soal s
     INNER JOIN ujian u ON u.id_ujian = s.id_ujian
     WHERE s.id_soal = ?
       AND s.id_ujian = ?
       AND u.id_guru = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    'iii',
    $idSoal,
    $idUjian,
    $idGuru
);

mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$soalValid = mysqli_num_rows($hasil) > 0;

mysqli_stmt_close($stmt);

if (!$soalValid) {
    kembali($idUjian, 'hapus_gagal');
}

// Jangan hapus soal yang sudah memiliki jawaban siswa.
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM jawaban_siswa
     WHERE id_soal = ?"
);

mysqli_stmt_bind_param($stmt, 'i', $idSoal);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

if ((int) $data['jumlah'] > 0) {
    kembali($idUjian, 'hapus_memiliki_jawaban');
}

// Hapus soal hanya jika masih dimiliki oleh guru tersebut.
$stmt = mysqli_prepare(
    $conn,
    "DELETE s
     FROM soal s
     INNER JOIN ujian u ON u.id_ujian = s.id_ujian
     WHERE s.id_soal = ?
       AND s.id_ujian = ?
       AND u.id_guru = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    'iii',
    $idSoal,
    $idUjian,
    $idGuru
);

$berhasil = mysqli_stmt_execute($stmt)
    && mysqli_stmt_affected_rows($stmt) === 1;

mysqli_stmt_close($stmt);

kembali(
    $idUjian,
    $berhasil ? 'hapus_berhasil' : 'hapus_gagal'
);
