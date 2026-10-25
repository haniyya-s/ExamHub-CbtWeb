
<?php
session_start();
require_once "../koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: ../login.php?error=kosong");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id_admin, nama_admin, username, password
     FROM admin
     WHERE username = ?
     LIMIT 1"
);

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    if (password_verify($password, $admin["password"])) {
        session_regenerate_id(true);

        $_SESSION["id_admin"] = $admin["id_admin"];
        $_SESSION["nama_admin"] = $admin["nama_admin"];
        $_SESSION["username_admin"] = $admin["username"];

        header("Location: dashboard_admin.php");
        exit;
    }
}

header("Location: ../login.php?error=login_gagal");
exit;
?>
