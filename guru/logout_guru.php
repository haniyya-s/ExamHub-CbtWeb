<?php
session_start();

// Hapus session guru
unset(
    $_SESSION['id_guru'],
    $_SESSION['nama_guru'],
    $_SESSION['username_guru']
);

// Kembali ke halaman login siswa
header('Location: ../siswa/login_siswa.php');
exit;