<?php
session_start();

// Hapus session siswa
unset(
    $_SESSION['id_siswa'],
    $_SESSION['nama'],
    $_SESSION['username'],
    $_SESSION['id_kelas']
);

// Kembali ke halaman login siswa
header('Location: login_siswa.php');
exit;