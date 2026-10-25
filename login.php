
<?php
session_start();

// Tidak ada redirect otomatis ke dashboard.
// Pengguna selalu dapat melihat halaman login.

// Pesan error
$error = "";

// Tab login default
$jenisLogin = "siswa";

// Menentukan tab berdasarkan parameter URL
if (isset($_GET['jenis']) && $_GET['jenis'] === 'guru') {
    $jenisLogin = "guru";
}

// Menampilkan pesan error
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'kosong') {
        $error = "Username dan password wajib diisi.";
    } elseif (
        $_GET['error'] === 'gagal' ||
        $_GET['error'] === 'login_gagal'
    ) {
        $error = "Username atau password salah.";
    }
}
?>
