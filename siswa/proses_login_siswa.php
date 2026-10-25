
<?php
session_start();

// Koneksi database.
require_once __DIR__ . "/../koneksi.php";

// Pastikan data dikirim melalui POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login_siswa.php");
    exit;
}

// Ambil data login.
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

// Validasi input.
if ($username === "" || $password === "") {
    header("Location: login_siswa.php?error=kosong");
    exit;
}

// Cari akun siswa.
$query = "SELECT id_siswa, nama_siswa, username, password, id_kelas
          FROM siswa
          WHERE username = ?
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit("Terjadi kesalahan pada sistem. Silakan coba kembali.");
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$siswa = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Verifikasi password sesuai format database saat ini.
if ($siswa && $password === $siswa["password"]) {

    // Perbarui ID session.
    session_regenerate_id(true);

    // Simpan identitas siswa.
    $_SESSION["id_siswa"] = $siswa["id_siswa"];
    $_SESSION["nama"] = $siswa["nama_siswa"];
    $_SESSION["username"] = $siswa["username"];
    $_SESSION["id_kelas"] = $siswa["id_kelas"];

    // Masuk ke halaman kelas siswa.
    header("Location: kelas_siswa.php");
    exit;
}

// Jika username atau password salah.
header("Location: login_siswa.php?error=gagal");
exit;
?>
