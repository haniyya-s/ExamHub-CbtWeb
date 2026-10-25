
<?php
session_start();

require_once __DIR__ . '/../koneksi.php';

// Pastikan login dikirim melalui form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cbt_web/login.php?jenis=guru');
    exit;
}

// Ambil data dari form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if ($username === '' || $password === '') {
    header('Location: /cbt_web/login.php?jenis=guru&error=kosong');
    exit;
}

// Cari akun guru
$query = "SELECT id_guru, nama_guru, username, password
          FROM guru
          WHERE username = ?
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat menyiapkan query login guru.');
}

mysqli_stmt_bind_param($stmt, 's', $username);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Terjadi kesalahan saat memeriksa akun guru.');
}

$result = mysqli_stmt_get_result($stmt);
$guru = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Verifikasi username dan password
if ($guru && password_verify($password, $guru['password'])) {

    // Buat session baru setelah login
    session_regenerate_id(true);

    $_SESSION['id_guru'] = $guru['id_guru'];
    $_SESSION['nama_guru'] = $guru['nama_guru'];
    $_SESSION['username_guru'] = $guru['username'];

    // Masuk ke dashboard guru
    header('Location: /cbt_web/guru/dashboard_guru.php');
    exit;
}

// Login gagal
header('Location: /cbt_web/login.php?jenis=guru&error=gagal');
exit;
?>
