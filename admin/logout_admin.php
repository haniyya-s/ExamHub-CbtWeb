
<?php
session_start();

// Hapus seluruh session.
$_SESSION = [];

// Hapus cookie session dari browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session di server.
session_destroy();

// Kembali ke halaman login utama.
header("Location: ../admin/login_admin.php");
exit;
?>
