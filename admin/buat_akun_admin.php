
<?php
require_once "../koneksi.php";

$nama_admin = "Administrator";
$username = "admin";
$password = "GantiPassword123!";

// Cek apakah username sudah digunakan
$cek = $conn->prepare(
    "SELECT id_admin FROM admin WHERE username = ?"
);
$cek->bind_param("s", $username);
$cek->execute();
$hasil = $cek->get_result();

if ($hasil->num_rows > 0) {
    exit("Akun admin sudah dibuat. Silakan hapus file ini setelah selesai.");
}

// Hash password sebelum disimpan
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO admin (nama_admin, username, password)
     VALUES (?, ?, ?)"
);
$stmt->bind_param(
    "sss",
    $nama_admin,
    $username,
    $password_hash
);

if ($stmt->execute()) {
    echo "Akun admin berhasil dibuat!<br>";
    echo "Username: " . htmlspecialchars($username) . "<br>";
    echo "Password: " . htmlspecialchars($password) . "<br>";
    echo "Segera hapus file buat_akun_admin.php demi keamanan.";
} else {
    echo "Gagal membuat akun admin.";
}
?>
