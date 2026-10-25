
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// 1. Pastikan guru sudah login.
if (!isset($_SESSION['id_guru'])) {
    header('Location: login_guru.php');
    exit;
}

$id_guru = (int) $_SESSION['id_guru'];

// 2. Ambil daftar ujian dan siswa dari semua kelas yang ditugaskan.
$query = "
    SELECT
        u.id_ujian,
        u.nama_ujian,
        u.tanggal_ujian,
        s.id_siswa,
        s.nama_siswa,
        us.waktu_selesai,

        COUNT(DISTINCT so.id_soal) AS total_soal,

        COUNT(DISTINCT CASE
            WHEN so.tipe_soal = 'pilihan_ganda'
            THEN so.id_soal
        END) AS total_pg,

        COUNT(DISTINCT CASE
            WHEN so.tipe_soal = 'esai'
            THEN so.id_soal
        END) AS total_esai,

        COUNT(DISTINCT CASE
            WHEN so.tipe_soal = 'pilihan_ganda'
             AND UPPER(TRIM(j.jawaban_terpilih))
                 = UPPER(TRIM(so.jawaban_benar))
            THEN so.id_soal
        END) AS benar_pg,

        COUNT(DISTINCT CASE
            WHEN so.tipe_soal = 'esai'
             AND j.nilai_esai IS NOT NULL
            THEN so.id_soal
        END) AS esai_dinilai,

        COALESCE(SUM(
            CASE
                WHEN so.tipe_soal = 'pilihan_ganda'
                 AND UPPER(TRIM(j.jawaban_terpilih))
                     = UPPER(TRIM(so.jawaban_benar))
                THEN 100

                WHEN so.tipe_soal = 'esai'
                THEN COALESCE(j.nilai_esai, 0)

                ELSE 0
            END
        ), 0) AS total_poin

    FROM ujian u

    INNER JOIN ujian_kelas uk
        ON uk.id_ujian = u.id_ujian

    INNER JOIN siswa s
        ON s.id_kelas = uk.id_kelas

    LEFT JOIN soal so
        ON so.id_ujian = u.id_ujian

    LEFT JOIN jawaban_siswa j
        ON j.id_siswa = s.id_siswa
        AND j.id_ujian = u.id_ujian
        AND j.id_soal = so.id_soal

    LEFT JOIN ujian_selesai us
        ON us.id_siswa = s.id_siswa
        AND us.id_ujian = u.id_ujian

    WHERE u.id_guru = ?

    GROUP BY
        u.id_ujian,
        u.nama_ujian,
        u.tanggal_ujian,
        s.id_siswa,
        s.nama_siswa,
        us.waktu_selesai

    ORDER BY
        u.tanggal_ujian DESC,
        u.nama_ujian ASC,
        s.nama_siswa ASC
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    error_log('Gagal menyiapkan query nilai guru: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil nilai siswa.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_guru);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// 3. Amankan teks yang ditampilkan.
function h($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Nilai Siswa - ExamHub</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/cbt-theme.css?v=20261024-2">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand mb-0">
            ExamHub - Nilai Siswa
        </span>

        <a href="dashboard_guru.php" class="btn btn-light btn-sm">
            Kembali ke Dashboard
        </a>
    </div>
</nav>

<div class="container py-4">

    <h3 class="mb-2">Daftar Nilai Siswa</h3>

    <p class="text-muted mb-4">
        Lihat hasil ujian siswa dari seluruh kelas yang ditugaskan,
        periksa jawaban, dan selesaikan penilaian esai.
    </p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>Nama Ujian</th>
                            <th>Nama Siswa</th>
                            <th>PG Benar</th>
                            <th>Total Soal</th>
                            <th>Nilai Akhir</th>
                            <th>Tanggal &amp; Jam Selesai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($row = mysqli_fetch_assoc($result)): ?>

                            <?php
                            $total = (int) $row['total_soal'];
                            $benar = (int) $row['benar_pg'];
                            $total_esai = (int) $row['total_esai'];
                            $esai_dinilai = (int) $row['esai_dinilai'];

                            $sudah_selesai = !empty(
                                $row['waktu_selesai']
                            );

                            // Nilai akhir tampil setelah seluruh esai dinilai.
                            $penilaian_lengkap =
                                $sudah_selesai &&
                                $total > 0 &&
                                $esai_dinilai === $total_esai;

                            if ($penilaian_lengkap) {
                                $nilai = round(
                                    (float) $row['total_poin'] / $total,
                                    2
                                );
                            } else {
                                $nilai = null;
                            }

                            if (!$sudah_selesai) {
                                if ($total === 0) {
                                    $status = 'Belum ada soal';
                                    $badge = 'secondary';
                                } else {
                                    $status = 'Belum mengerjakan';
                                    $badge = 'warning';
                                }
                            } elseif ($total === 0) {
                                $status = 'Belum ada soal';
                                $badge = 'secondary';
                            } elseif (!$penilaian_lengkap) {
                                $status = 'Menunggu penilaian esai';
                                $badge = 'warning';
                            } else {
                                $status = 'Selesai dinilai';
                                $badge = 'success';
                            }
                            ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= h($row['nama_ujian']) ?>
                                </td>

                                <td>
                                    <?= h($row['nama_siswa']) ?>
                                </td>

                                <td>
                                    <?= $benar ?> dari
                                    <?= (int) $row['total_pg'] ?>
                                </td>

                                <td><?= $total ?></td>

                                <td class="fw-bold">
                                    <?php if ($nilai !== null): ?>
                                        <?= number_format(
                                            $nilai,
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($sudah_selesai): ?>
                                        <?= date(
                                            'd-m-Y H:i:s',
                                            strtotime($row['waktu_selesai'])
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            Belum selesai
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-<?= $badge ?>">
                                        <?= h($status) ?>
                                    </span>

                                    <?php if (
                                        $sudah_selesai &&
                                        $total_esai > 0
                                    ): ?>
                                        <div class="small text-muted mt-1">
                                            Esai dinilai:
                                            <?= $esai_dinilai ?>/<?= $total_esai ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($sudah_selesai): ?>
                                        <a
                                            href="detail_nilai_guru.php?id_ujian=<?= (int) $row['id_ujian'] ?>&amp;id_siswa=<?= (int) $row['id_siswa'] ?>"
                                            class="btn btn-primary btn-sm"
                                        >
                                            Detail Jawaban
                                        </a>
                                    <?php else: ?>
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            disabled
                                        >
                                            Belum tersedia
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="text-center py-4">
                                Belum ada ujian atau siswa
                                di kelas yang ditugaskan untuk ujianmu.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php
mysqli_stmt_close($stmt);
?>

</body>
</html>

