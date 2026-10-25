
<?php
session_start();

require_once __DIR__ . '/../koneksi.php';

// Akun ini hanya untuk persiapan awal.
// Jalankan melalui browser, lalu hapus file ini setelah selesai.

$nama = "Guru Test";
$username = "gurutest";
$password = "guru123";

// Periksa apakah username sudah digunakan
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_guru FROM guru WHERE username = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$sudahAda = mysqli_num_rows($hasil) > 0;

mysqli_stmt_close($stmt);

if ($sudahAda) {
    exit(
        "Username gurutest sudah tersedia. " .
        "Akun tidak dibuat ulang."
    );
}

// Hash password sebelum disimpan
$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// Masukkan akun guru ke database
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO guru (nama_guru, username, password)
     VALUES (?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $nama,
    $username,
    $passwordHash
);

if (mysqli_stmt_execute($stmt)) {
    echo "Akun guru berhasil dibuat.<br>";
    echo "Username: gurutest<br>";
    echo "Password: guru123<br>";
    echo "<strong>Hapus file buat_akun_guru.php setelah selesai.</strong>";
} else {
    http_response_code(500);
    echo "Gagal membuat akun guru. Periksa struktur tabel guru.";
}

mysqli_stmt_close($stmt);
?>
