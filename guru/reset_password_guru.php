
<?php
require_once "koneksi.php";

$username = "gurutest";
$passwordBaru = "guru123";
$passwordHash = password_hash($passwordBaru, PASSWORD_DEFAULT);

$stmt = mysqli_prepare(
    $conn,
    "UPDATE guru SET password = ? WHERE username = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $passwordHash,
    $username
);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "Password akun gurutest berhasil diperbarui.";
    } else {
        echo "Tidak ada perubahan. Periksa apakah username ditemukan atau password sudah sama.";
    }
} else {
    echo "Gagal memperbarui password.";
}
?>
