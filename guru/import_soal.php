
<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// ======================================
// 1. FUNGSI BANTU
// ======================================

function kembaliKeSoal($idUjian, $pesan, $jenis = 'danger')
{
    $_SESSION['pesan_import_soal'] = $pesan;
    $_SESSION['jenis_pesan_import_soal'] = $jenis;

    header(
        'Location: soal_guru.php?id_ujian=' . (int) $idUjian
    );
    exit;
}

// ======================================
// 2. PERIKSA LOGIN GURU
// ======================================

if (!isset($_SESSION['id_guru'])) {
    header('Location: ../login.php?jenis=guru');
    exit;
}

$idGuru = (int) $_SESSION['id_guru'];

// ======================================
// 3. PERIKSA METODE REQUEST
// ======================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: soal_guru.php');
    exit;
}

$idUjian = (int) ($_POST['id_ujian'] ?? 0);

// ======================================
// 4. PERIKSA CSRF TOKEN
// ======================================

$tokenDikirim = $_POST['csrf_token'] ?? '';
$tokenSesi = $_SESSION['csrf_soal_guru'] ?? '';

if (
    !is_string($tokenDikirim) ||
    !is_string($tokenSesi) ||
    $tokenSesi === '' ||
    !hash_equals($tokenSesi, $tokenDikirim)
) {
    kembaliKeSoal(
        $idUjian,
        'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.'
    );
}

// ======================================
// 5. PASTIKAN UJIAN MILIK GURU
// ======================================

$stmtUjian = mysqli_prepare(
    $conn,
    "SELECT id_ujian
     FROM ujian
     WHERE id_ujian = ? AND id_guru = ?
     LIMIT 1"
);

if (!$stmtUjian) {
    kembaliKeSoal($idUjian, 'Gagal memeriksa data ujian.');
}

mysqli_stmt_bind_param($stmtUjian, 'ii', $idUjian, $idGuru);
mysqli_stmt_execute($stmtUjian);

$hasilUjian = mysqli_stmt_get_result($stmtUjian);
$ujianValid = mysqli_num_rows($hasilUjian) > 0;

mysqli_stmt_close($stmtUjian);

if ($idUjian <= 0 || !$ujianValid) {
    kembaliKeSoal(
        0,
        'Ujian tidak ditemukan atau bukan milik akun guru ini.'
    );
}

// ======================================
// 6. PERIKSA FILE EXCEL
// ======================================

if (
    !isset($_FILES['file_excel']) ||
    $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK
) {
    kembaliKeSoal(
        $idUjian,
        'File Excel gagal diunggah. Pilih file kembali dan coba lagi.'
    );
}

$file = $_FILES['file_excel'];

// Maksimal 5 MB
if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
    kembaliKeSoal(
        $idUjian,
        'Ukuran file harus lebih dari 0 dan maksimal 5 MB.'
    );
}

$ekstensi = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);

if ($ekstensi !== 'xlsx') {
    kembaliKeSoal(
        $idUjian,
        'Format file harus .xlsx. Gunakan template Excel yang disediakan.'
    );
}

// ======================================
// 7. BACA EXCEL DAN VALIDASI HEADER
// ======================================

try {
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);

    $spreadsheet = $reader->load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();

    $barisTerakhir = $sheet->getHighestDataRow();

    if ($barisTerakhir < 2) {
        $spreadsheet->disconnectWorksheets();

        kembaliKeSoal(
            $idUjian,
            'Excel belum berisi soal. Isi minimal satu soal.'
        );
    }

    $headerWajib = [
        'tipe_soal',
        'teks_soal',
        'opsi_a',
        'opsi_b',
        'opsi_c',
        'opsi_d',
        'jawaban_benar'
    ];

    $headerExcel = [];

    foreach (range('A', 'G') as $kolom) {
        $headerExcel[] = strtolower(
            trim((string) $sheet->getCell($kolom . '1')->getValue())
        );
    }

    if ($headerExcel !== $headerWajib) {
        $spreadsheet->disconnectWorksheets();

        kembaliKeSoal(
            $idUjian,
            'Header Excel tidak sesuai. Gunakan template yang disediakan tanpa mengubah nama atau urutan kolom.'
        );
    }

    // ======================================
    // 8. VALIDASI SEMUA BARIS SOAL
    // ======================================

    $daftarSoalImport = [];
    $daftarError = [];

    for ($baris = 2; $baris <= $barisTerakhir; $baris++) {
        $data = [];

        foreach (range('A', 'G') as $kolom) {
            $data[] = trim(
                (string) $sheet->getCell($kolom . $baris)->getValue()
            );
        }

        [
            $tipeSoal,
            $teksSoal,
            $opsiA,
            $opsiB,
            $opsiC,
            $opsiD,
            $jawabanBenar
        ] = $data;

        // Lewati baris yang sepenuhnya kosong
        if (count(array_filter($data, fn($v) => $v !== '')) === 0) {
            continue;
        }

        $errorBaris = [];

        if (!in_array($tipeSoal, ['pilihan_ganda', 'esai'], true)) {
            $errorBaris[] = 'tipe_soal harus pilihan_ganda atau esai';
        }

        if ($teksSoal === '') {
            $errorBaris[] = 'teks_soal wajib diisi';
        }

        if ($tipeSoal === 'pilihan_ganda') {
            if (
                $opsiA === '' ||
                $opsiB === '' ||
                $opsiC === '' ||
                $opsiD === ''
            ) {
                $errorBaris[] = 'opsi A, B, C, dan D wajib diisi';
            }

            $jawabanBenar = strtoupper($jawabanBenar);

            if (!in_array($jawabanBenar, ['A', 'B', 'C', 'D'], true)) {
                $errorBaris[] = 'jawaban_benar harus A, B, C, atau D';
            }
        } elseif ($tipeSoal === 'esai') {
            // Esai tidak memakai pilihan jawaban
            $opsiA = '';
            $opsiB = '';
            $opsiC = '';
            $opsiD = '';
            $jawabanBenar = '';
        }

        if ($errorBaris) {
            $daftarError[] =
                'Baris ' . $baris . ': ' . implode('; ', $errorBaris);
        } else {
            $daftarSoalImport[] = [
                'tipe_soal' => $tipeSoal,
                'teks_soal' => $teksSoal,
                'opsi_a' => $opsiA,
                'opsi_b' => $opsiB,
                'opsi_c' => $opsiC,
                'opsi_d' => $opsiD,
                'jawaban_benar' => $jawabanBenar
            ];
        }

        // Batasi jumlah error agar pesan tetap terbaca
        if (count($daftarError) >= 20) {
            $daftarError[] = 'Pemeriksaan dihentikan setelah 20 kesalahan.';
            break;
        }
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    if (count($daftarError) > 0) {
        kembaliKeSoal(
            $idUjian,
            'Import dibatalkan. ' . implode(' | ', $daftarError)
        );
    }

    if (count($daftarSoalImport) === 0) {
        kembaliKeSoal(
            $idUjian,
            'Tidak ada soal yang bisa diimpor.'
        );
    }

    // ======================================
    // 9. SIMPAN SEMUA SOAL DALAM TRANSAKSI
    // ======================================

    mysqli_begin_transaction($conn);

    try {
        $stmtInsert = mysqli_prepare(
            $conn,
            "INSERT INTO soal (
                id_ujian,
                id_guru,
                teks_soal,
                tipe_soal,
                opsi_a,
                opsi_b,
                opsi_c,
                opsi_d,
                jawaban_benar
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmtInsert) {
            throw new Exception('Gagal menyiapkan query penyimpanan soal.');
        }

        foreach ($daftarSoalImport as $soal) {
            $tipe = $soal['tipe_soal'];
            $teks = $soal['teks_soal'];
            $a = $soal['opsi_a'];
            $b = $soal['opsi_b'];
            $c = $soal['opsi_c'];
            $d = $soal['opsi_d'];
            $kunci = $soal['jawaban_benar'];

            mysqli_stmt_bind_param(
                $stmtInsert,
                'iisssssss',
                $idUjian,
                $idGuru,
                $teks,
                $tipe,
                $a,
                $b,
                $c,
                $d,
                $kunci
            );

            if (!mysqli_stmt_execute($stmtInsert)) {
                throw new Exception('Gagal menyimpan salah satu soal.');
            }
        }

        mysqli_stmt_close($stmtInsert);
        mysqli_commit($conn);

    } catch (Throwable $e) {
        mysqli_rollback($conn);

        error_log('Import soal ExamHub: ' . $e->getMessage());

        kembaliKeSoal(
            $idUjian,
            'Import gagal disimpan. Tidak ada soal dari proses ini yang ditambahkan.'
        );
    }

    kembaliKeSoal(
        $idUjian,
        count($daftarSoalImport) . ' soal berhasil diimpor dari Excel!',
        'success'
    );

} catch (Throwable $e) {
    error_log('Baca Excel ExamHub: ' . $e->getMessage());

    kembaliKeSoal(
        $idUjian,
        'File Excel tidak bisa dibaca. Pastikan file berasal dari template resmi dan tidak rusak.'
    );
}

