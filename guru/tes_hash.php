
<?php
$password = "TesPassword123!";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Tes Password PHP</h3>";

if (password_verify($password, $hash)) {
    echo "BERHASIL: fungsi hash dan verifikasi PHP berjalan.";
} else {
    echo "GAGAL: fungsi verifikasi password tidak cocok.";
}
?>
