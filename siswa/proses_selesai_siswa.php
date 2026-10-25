
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Pastikan siswa sudah login.
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['id_kelas'])
) {
    header('Location: login_siswa.php');
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];

$id_ujian = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// 2. Pastikan permintaan berasal dari proses ujian.
if (
    !$id_ujian ||
    $id_ujian < 1 ||
    empty($_SESSION['siap_diproses'][$id_ujian])
) {
    header('Location: kelas_siswa.php');
    exit;
}

// 3. Pastikan ujian terhubung dengan kelas siswa.
$query = "
    SELECT
        u.id_ujian,
        u.nama_ujian,
        u.durasi_waktu,
        u.tanggal_ujian
    FROM ujian u
    INNER JOIN ujian_kelas uk
        ON u.id_ujian = uk.id_ujian
    WHERE u.id_ujian = ?
      AND uk.id_kelas = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memeriksa ujian.');
}

mysqli_stmt_bind_param($stmt, 'ii', $id_ujian, $id_kelas);
mysqli_stmt_execute($stmt);

$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujian) {
    unset($_SESSION['siap_diproses'][$id_ujian]);

    header('Location: kelas_siswa.php');
    exit;
}

// 4. Periksa waktu ujian.
$waktuMulai = new DateTime($ujian['tanggal_ujian']);

$waktuBerakhir = clone $waktuMulai;
$waktuBerakhir->modify(
    '+' . (int) $ujian['durasi_waktu'] . ' minutes'
);

$waktuSekarang = new DateTime();

if (
    $waktuSekarang < $waktuMulai ||
    $waktuSekarang >= $waktuBerakhir
) {
    unset($_SESSION['siap_diproses'][$id_ujian]);

    exit('Waktu ujian sudah berakhir atau belum dimulai.');
}

// 5. Periksa apakah siswa sudah menyelesaikan ujian.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM ujian_selesai
     WHERE id_siswa = ? AND id_ujian = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memeriksa status ujian.');
}

mysqli_stmt_bind_param($stmt, 'ii', $id_siswa, $id_ujian);
mysqli_stmt_execute($stmt);

$sudahSelesai = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

if ($sudahSelesai) {
    unset($_SESSION['jawaban_ujian'][$id_ujian]);
    unset($_SESSION['siap_diproses'][$id_ujian]);

    header('Location: hasil_siswa.php?id=' . $id_ujian);
    exit;
}

// 6. Ambil jawaban sementara dari session.
$jawaban = $_SESSION['jawaban_ujian'][$id_ujian] ?? [];

if (!is_array($jawaban)) {
    exit('Data jawaban tidak valid.');
}

// 7. Ambil seluruh soal beserta jenisnya.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_soal, tipe_soal
     FROM soal
     WHERE id_ujian = ?
     ORDER BY id_soal ASC"
);

if (!$stmt) {
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil soal.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_ujian);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$daftarSoal = [];

while ($baris = mysqli_fetch_assoc($result)) {
    $idSoal = (int) $baris['id_soal'];
    $daftarSoal[$idSoal] = $baris['tipe_soal'];
}

mysqli_stmt_close($stmt);

if (empty($daftarSoal)) {
    exit('Soal untuk ujian ini tidak ditemukan.');
}

// 8. Pastikan semua soal sudah dijawab.
foreach ($daftarSoal as $id_soal => $tipeSoal) {

    if (
        !array_key_exists($id_soal, $jawaban) ||
        !is_string($jawaban[$id_soal])
    ) {
        exit(
            'Masih ada soal yang belum dijawab. '
            . '<a href="ujian_siswa.php?id='
            . (int) $id_ujian
            . '">Kembali ke ujian</a>'
        );
    }

    $jawabanSementara = $jawaban[$id_soal];

    if ($tipeSoal === 'pilihan_ganda') {

        $pilihan = strtoupper(trim($jawabanSementara));

        if (!in_array($pilihan, ['A', 'B', 'C', 'D'], true)) {
            exit(
                'Jawaban pilihan ganda tidak valid. '
                . '<a href="ujian_siswa.php?id='
                . (int) $id_ujian
                . '">Kembali ke ujian</a>'
            );
        }

    } elseif ($tipeSoal === 'esai') {

        if (trim($jawabanSementara) === '') {
            exit(
                'Masih ada soal esai yang belum dijawab. '
                . '<a href="ujian_siswa.php?id='
                . (int) $id_ujian
                . '">Kembali ke ujian</a>'
            );
        }

    } else {
        exit('Jenis soal tidak dikenali.');
    }
}

// 9. Pastikan tidak ada jawaban dari soal lain.
foreach ($jawaban as $id_soal => $isiJawaban) {

    if (!isset($daftarSoal[(int) $id_soal])) {
        exit('Terdapat jawaban yang tidak sesuai dengan ujian.');
    }

    if (!is_string($isiJawaban)) {
        exit('Format jawaban tidak valid.');
    }
}

$transaksiDimulai = false;

try {

    // 10. Mulai transaksi database.
    if (!mysqli_begin_transaction($conn)) {
        throw new RuntimeException('Gagal memulai transaksi.');
    }

    $transaksiDimulai = true;

    // Hapus jawaban lama jika ada.
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM jawaban_siswa
         WHERE id_siswa = ? AND id_ujian = ?"
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Gagal menyiapkan penghapusan jawaban.'
        );
    }

    mysqli_stmt_bind_param($stmt, 'ii', $id_siswa, $id_ujian);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Gagal menghapus jawaban lama.');
    }

    mysqli_stmt_close($stmt);

    // 11. Simpan jawaban pilihan ganda dan esai.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO jawaban_siswa
            (id_siswa, id_ujian, id_soal, jawaban_terpilih)
         VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Gagal menyiapkan penyimpanan jawaban.'
        );
    }

    foreach ($jawaban as $id_soal => $isiJawaban) {

        $id_soal = (int) $id_soal;

        if ($daftarSoal[$id_soal] === 'pilihan_ganda') {
            $jawabanSimpan = strtoupper(trim($isiJawaban));
        } else {
            $jawabanSimpan = $isiJawaban;
        }

        mysqli_stmt_bind_param(
            $stmt,
            'iiis',
            $id_siswa,
            $id_ujian,
            $id_soal,
            $jawabanSimpan
        );

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Gagal menyimpan jawaban.');
        }
    }

    mysqli_stmt_close($stmt);

    // 12. Catat penyelesaian ujian.
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO ujian_selesai (id_siswa, id_ujian)
         VALUES (?, ?)"
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Gagal menyiapkan catatan penyelesaian.'
        );
    }

    mysqli_stmt_bind_param($stmt, 'ii', $id_siswa, $id_ujian);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'Gagal mencatat penyelesaian ujian.'
        );
    }

    mysqli_stmt_close($stmt);

    // 13. Simpan transaksi.
    if (!mysqli_commit($conn)) {
        throw new RuntimeException('Gagal menyimpan transaksi.');
    }

    $transaksiDimulai = false;

    // Hapus jawaban sementara setelah berhasil disimpan.
    unset($_SESSION['jawaban_ujian'][$id_ujian]);
    unset($_SESSION['siap_diproses'][$id_ujian]);

    // 14. Arahkan siswa ke halaman hasil.
    header('Location: hasil_siswa.php?id=' . $id_ujian);
    exit;

} catch (Throwable $e) {

    if ($transaksiDimulai) {
        mysqli_rollback($conn);
    }

    error_log(
        'Gagal menyelesaikan ujian siswa '
        . $id_siswa
        . ', ujian '
        . $id_ujian
        . ': '
        . $e->getMessage()
    );

    http_response_code(500);

    exit(
        'Gagal menyelesaikan ujian. '
        . 'Silakan coba kembali atau hubungi pengelola ujian.'
    );
}
?>
