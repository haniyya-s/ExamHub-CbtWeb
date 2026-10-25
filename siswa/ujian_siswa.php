<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Pastikan siswa sudah login.
if (
    !isset($_SESSION['id_siswa']) ||
    !isset($_SESSION['id_kelas']) ||
    !isset($_SESSION['nama'])
) {
    header('Location: login_siswa.php');
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_kelas = (int) $_SESSION['id_kelas'];

// 2. Validasi ID ujian.
$id_ujian = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_ujian || $id_ujian < 1) {
    http_response_code(400);
    exit('ID ujian tidak valid.');
}

// 3. Pastikan ujian ditujukan kepada kelas siswa.
$query = "
    SELECT u.id_ujian, u.nama_ujian,
           u.durasi_waktu, u.tanggal_ujian
    FROM ujian u
    INNER JOIN ujian_kelas uk ON u.id_ujian = uk.id_ujian
    WHERE u.id_ujian = ? AND uk.id_kelas = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $id_ujian, $id_kelas);
mysqli_stmt_execute($stmt);
$ujian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ujian) {
    http_response_code(404);
    exit('Ujian tidak ditemukan untuk kelas kamu.');
}

// 4. Periksa apakah ujian sudah selesai.
$stmtSelesai = mysqli_prepare(
    $conn,
    "SELECT id FROM ujian_selesai
     WHERE id_siswa = ? AND id_ujian = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmtSelesai, 'ii', $id_siswa, $id_ujian);
mysqli_stmt_execute($stmtSelesai);
$sudahSelesai = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmtSelesai)
);
mysqli_stmt_close($stmtSelesai);

if ($sudahSelesai) {
    exit('Kamu sudah menyelesaikan ujian ini.');
}

// 5. Periksa jadwal ujian.
$mulai = new DateTime($ujian['tanggal_ujian']);
$selesai = clone $mulai;
$selesai->modify('+' . (int) $ujian['durasi_waktu'] . ' minutes');

$sekarang = new DateTime();

if ($sekarang < $mulai) {
    exit('Ujian belum dimulai.');
}

if ($sekarang >= $selesai) {
    exit('Waktu ujian sudah berakhir.');
}

// 6. Ambil soal ujian.
$query = "
    SELECT id_soal, teks_soal, tipe_soal,
           opsi_a, opsi_b, opsi_c, opsi_d
    FROM soal
    WHERE id_ujian = ?
    ORDER BY id_soal ASC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_ujian);
mysqli_stmt_execute($stmt);

$daftarSoal = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);

mysqli_stmt_close($stmt);

if (empty($daftarSoal)) {
    exit('Belum ada soal untuk ujian ini.');
}

$jumlahSoal = count($daftarSoal);

// 7. Siapkan jawaban sementara.
if (
    !isset($_SESSION['jawaban_ujian'][$id_ujian]) ||
    !is_array($_SESSION['jawaban_ujian'][$id_ujian])
) {
    $_SESSION['jawaban_ujian'][$id_ujian] = [];
}

// 8. Proses jawaban dan navigasi.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF.
    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !isset($_SESSION['csrf_ujian_siswa']) ||
        !hash_equals($_SESSION['csrf_ujian_siswa'], $csrf)
    ) {
        http_response_code(403);
        exit('Sesi formulir tidak valid. Muat ulang halaman.');
    }

    $id_soal_post = filter_input(
        INPUT_POST, 'id_soal', FILTER_VALIDATE_INT
    );

    $nomorPost = filter_input(
        INPUT_POST, 'nomor', FILTER_VALIDATE_INT
    );

    $aksi = $_POST['aksi'] ?? 'berikutnya';

    // Pastikan soal milik ujian ini.
    $soalPost = null;

    foreach ($daftarSoal as $item) {
        if ((int) $item['id_soal'] === (int) $id_soal_post) {
            $soalPost = $item;
            break;
        }
    }

    if (!$soalPost) {
        http_response_code(400);
        exit('ID soal tidak valid.');
    }

    $idSoalPost = (int) $soalPost['id_soal'];
    $tipeSoalPost = $soalPost['tipe_soal'];

    // Simpan jawaban.
    if (
        isset($_POST['jawaban']) &&
        is_string($_POST['jawaban'])
    ) {
        $jawabanPost = $_POST['jawaban'];

        if ($tipeSoalPost === 'pilihan_ganda') {
            $pilihan = strtoupper(trim($jawabanPost));

            if (in_array($pilihan, ['A', 'B', 'C', 'D'], true)) {
                $_SESSION['jawaban_ujian'][$id_ujian][$idSoalPost]
                    = $pilihan;
            }
        } elseif ($tipeSoalPost === 'esai') {
            $_SESSION['jawaban_ujian'][$id_ujian][$idSoalPost]
                = $jawabanPost;
        }
    }

    $nomorPost = max(1, min($jumlahSoal, $nomorPost ?: 1));

    // 9. Proses penyelesaian ujian.
    if ($aksi === 'selesai') {

        $waktuSekarang = new DateTime();

        if ($waktuSekarang < $mulai || $waktuSekarang >= $selesai) {
            exit('Waktu ujian sudah berakhir atau belum dimulai.');
        }

        // Semua soal wajib dijawab.
        foreach ($daftarSoal as $item) {
            $idCek = (int) $item['id_soal'];
            $tipeCek = $item['tipe_soal'];

            $jawabanCek =
                $_SESSION['jawaban_ujian'][$id_ujian][$idCek] ?? null;

            if ($tipeCek === 'pilihan_ganda') {
                $sudahDijawab = is_string($jawabanCek)
                    && in_array(
                        $jawabanCek,
                        ['A', 'B', 'C', 'D'],
                        true
                    );
            } elseif ($tipeCek === 'esai') {
                $sudahDijawab = is_string($jawabanCek)
                    && trim($jawabanCek) !== '';
            } else {
                $sudahDijawab = false;
            }

            if (!$sudahDijawab) {
                $_SESSION['flash_ujian'] = [
                    'icon' => 'warning',
                    'title' => 'Masih ada soal kosong!',
                    'text' => 'Jawab semua soal terlebih dahulu sebelum mengirim ujian.'
                ];

                header(
                    'Location: ujian_siswa.php?id=' . $id_ujian
                    . '&nomor=' . $nomorPost
                );
                exit;
            }
        }

        $_SESSION['siap_diproses'][$id_ujian] = true;

        header(
            'Location: proses_selesai_siswa.php?id=' . $id_ujian
        );
        exit;
    }

    // Navigasi soal.
    if ($aksi === 'sebelumnya') {
        $nomorPost--;
    } else {
        $nomorPost++;
    }

    $nomorPost = max(1, min($jumlahSoal, $nomorPost));

    header(
        'Location: ujian_siswa.php?id=' . $id_ujian
        . '&nomor=' . $nomorPost
    );
    exit;
}

// 10. Token CSRF.
if (empty($_SESSION['csrf_ujian_siswa'])) {
    $_SESSION['csrf_ujian_siswa'] = bin2hex(random_bytes(32));
}

// 11. Tentukan soal yang ditampilkan.
$nomor = filter_input(INPUT_GET, 'nomor', FILTER_VALIDATE_INT);
$nomor = max(1, min($jumlahSoal, $nomor ?: 1));

$soal = $daftarSoal[$nomor - 1];
$id_soal = (int) $soal['id_soal'];
$tipeSoal = $soal['tipe_soal'];

$jawabanSaatIni =
    $_SESSION['jawaban_ujian'][$id_ujian][$id_soal] ?? '';

$namaUjian = htmlspecialchars(
    $ujian['nama_ujian'],
    ENT_QUOTES,
    'UTF-8'
);

$teksSoal = nl2br(htmlspecialchars(
    $soal['teks_soal'],
    ENT_QUOTES,
    'UTF-8'
));

$flashUjian = $_SESSION['flash_ujian'] ?? null;
unset($_SESSION['flash_ujian']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $namaUjian ?> - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="container py-4 py-md-5">

    <section class="page-hero mb-4">
        <span class="eyebrow">🧠 Focus mode</span>

        <h1 class="h3 mt-3 mb-2"><?= $namaUjian ?></h1>

        <p class="hero-copy mb-0">
            Kamu sedang mengerjakan soal <?= $nomor ?>
            dari <?= $jumlahSoal ?>.
            Baca soal dengan teliti dan isi jawabanmu.
        </p>
    </section>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <div>
                <p class="text-uppercase fw-bold text-muted small mb-1">
                    Progress jawaban
                </p>

                <h2 class="h5 mb-0">
                    Soal <?= $nomor ?> dari <?= $jumlahSoal ?>
                </h2>
            </div>

            <span class="badge bg-light text-dark border">
                <?= (int) round(($nomor / $jumlahSoal) * 100) ?>%
            </span>
        </div>

        <div class="card-body">

            <form method="POST" id="formUjian">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_ujian_siswa'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input type="hidden" name="id_soal" value="<?= $id_soal ?>">
                <input type="hidden" name="nomor" value="<?= $nomor ?>">

                <div class="mb-4">
                    <span class="badge bg-secondary mb-3">
                        <?= $tipeSoal === 'esai' ? 'Soal Esai' : 'Pilihan Ganda' ?>
                    </span>

                    <h5 class="mb-0"><?= $teksSoal ?></h5>
                </div>

                <?php if ($tipeSoal === 'pilihan_ganda'): ?>

                    <?php foreach (['A', 'B', 'C', 'D'] as $huruf): ?>
                        <?php $kolom = 'opsi_' . strtolower($huruf); ?>

                        <label class="border rounded p-3 mb-2 d-block">
                            <input
                                type="radio"
                                name="jawaban"
                                value="<?= $huruf ?>"
                                <?= $jawabanSaatIni === $huruf ? 'checked' : '' ?>
                            >

                            <strong><?= $huruf ?>.</strong>

                            <?= htmlspecialchars(
                                $soal[$kolom] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </label>
                    <?php endforeach; ?>

                <?php elseif ($tipeSoal === 'esai'): ?>

                    <label for="jawabanEsai" class="form-label fw-semibold">
                        Tulis jawabanmu
                    </label>

                    <textarea
                        name="jawaban"
                        id="jawabanEsai"
                        class="form-control"
                        rows="6"
                        placeholder="Ketik jawaban esai di sini..."
                    ><?= htmlspecialchars(
                        is_string($jawabanSaatIni) ? $jawabanSaatIni : '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                    <div class="form-text">
                        Jawaban tersimpan sementara saat kamu berpindah soal.
                    </div>

                <?php else: ?>

                    <div class="alert alert-danger">
                        Jenis soal tidak dikenali. Silakan hubungi guru.
                    </div>

                <?php endif; ?>

                <div class="d-flex justify-content-between mt-4">

                    <?php if ($nomor > 1): ?>
                        <button
                            type="submit"
                            name="aksi"
                            value="sebelumnya"
                            class="btn btn-outline-secondary"
                            formnovalidate
                        >
                            Sebelumnya
                        </button>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <?php if ($nomor < $jumlahSoal): ?>

                        <button
                            type="submit"
                            name="aksi"
                            value="berikutnya"
                            id="tombolBerikutnya"
                            class="btn btn-primary"
                            formnovalidate
                            disabled
                        >
                            Berikutnya
                        </button>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="aksi"
                            value="selesai"
                            id="tombolSelesai"
                            class="btn btn-success"
                            formnovalidate
                            disabled
                        >
                            Selesaikan Ujian
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>
    </div>
</div>

<script>
const formUjian = document.getElementById('formUjian');
const pilihanJawaban = document.querySelectorAll('input[name="jawaban"]');
const jawabanEsai = document.getElementById('jawabanEsai');

const tombolBerikutnya = document.getElementById('tombolBerikutnya');
const tombolSelesai = document.getElementById('tombolSelesai');

function periksaJawaban() {
    let sudahMenjawab = false;

    if (jawabanEsai) {
        sudahMenjawab = jawabanEsai.value.trim() !== '';
    } else if (pilihanJawaban.length > 0) {
        sudahMenjawab = Array.from(pilihanJawaban).some(
            pilihan => pilihan.checked
        );
    }

    if (tombolBerikutnya) {
        tombolBerikutnya.disabled = !sudahMenjawab;
    }

    if (tombolSelesai) {
        tombolSelesai.disabled = !sudahMenjawab;
    }
}

pilihanJawaban.forEach(pilihan => {
    pilihan.addEventListener('change', periksaJawaban);
});

if (jawabanEsai) {
    jawabanEsai.addEventListener('input', periksaJawaban);
}

periksaJawaban();

// SweetAlert2: konfirmasi saat siswa mengirim ujian.
if (tombolSelesai) {
    tombolSelesai.addEventListener('click', function(event) {
        event.preventDefault();

        Swal.fire({
            title: 'Kirim jawaban ujian?',
            text: 'Pastikan semua jawaban sudah diperiksa. Setelah ujian dikirim, kamu tidak dapat mengubah jawaban lagi.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, kirim ujian!',
            cancelButtonText: 'Periksa kembali',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Isi aksi secara eksplisit agar PHP menerima "selesai".
                const aksi = document.createElement('input');
                aksi.type = 'hidden';
                aksi.name = 'aksi';
                aksi.value = 'selesai';
                formUjian.appendChild(aksi);

                formUjian.requestSubmit();
            }
        });
    });
}

// SweetAlert untuk pesan validasi setelah kembali ke halaman ujian.
<?php if ($flashUjian): ?>
Swal.fire({
    icon: <?= json_encode($flashUjian['icon']) ?>,
    title: <?= json_encode($flashUjian['title']) ?>,
    text: <?= json_encode($flashUjian['text']) ?>,
    confirmButtonText: 'Mengerti',
    confirmButtonColor: '#3085d6'
});
<?php endif; ?>
</script>

</body>
</html>
