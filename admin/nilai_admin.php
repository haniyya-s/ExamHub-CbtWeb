
<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/../koneksi.php';

// Pastikan Admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Ambil nilai seluruh siswa dari semua ujian
$query = "
    SELECT
        u.id_ujian,
        u.nama_ujian,
        u.tanggal_ujian,
        k.nama_kelas,
        g.nama_guru,
        s.id_siswa,
        s.nama_siswa,
        us.waktu_selesai,

        COUNT(DISTINCT so.id_soal) AS total_soal,

        COUNT(DISTINCT CASE
            WHEN UPPER(TRIM(j.jawaban_terpilih))
                 = UPPER(TRIM(so.jawaban_benar))
            THEN so.id_soal
        END) AS benar,

        COUNT(DISTINCT j.id_soal) AS dijawab

    FROM ujian u

    LEFT JOIN kelas k
        ON k.id_kelas = u.id_kelas

    LEFT JOIN guru g
        ON g.id_guru = u.id_guru

    INNER JOIN siswa s
        ON s.id_kelas = u.id_kelas

    LEFT JOIN soal so
        ON so.id_ujian = u.id_ujian

    LEFT JOIN jawaban_siswa j
        ON j.id_siswa = s.id_siswa
        AND j.id_ujian = u.id_ujian
        AND j.id_soal = so.id_soal

    LEFT JOIN ujian_selesai us
        ON us.id_siswa = s.id_siswa
        AND us.id_ujian = u.id_ujian

    GROUP BY
        u.id_ujian,
        u.nama_ujian,
        u.tanggal_ujian,
        k.nama_kelas,
        g.nama_guru,
        s.id_siswa,
        s.nama_siswa,
        us.waktu_selesai

    ORDER BY
        u.tanggal_ujian DESC,
        u.nama_ujian ASC,
        s.nama_siswa ASC
";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    exit('Terjadi kesalahan saat mengambil data nilai.');
}

// Mengamankan teks yang ditampilkan ke halaman
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

    <title>Nilai Siswa - ExamHub Admin</title>

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
            ExamHub Admin - Nilai Siswa
        </span>

        <a href="dashboard_admin.php" class="btn btn-light btn-sm">
            Kembali ke Dashboard
        </a>
    </div>
</nav>

<div class="container py-4">

    <h3 class="mb-2">Daftar Nilai Siswa</h3>

    <p class="text-muted mb-4">
        Admin dapat melihat hasil ujian dari seluruh kelas
        dan guru yang terdaftar.
    </p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-primary">
                        <tr>
                            <th>No.</th>
                            <th>Nama Ujian</th>
                            <th>Kelas</th>
                            <th>Guru</th>
                            <th>Nama Siswa</th>
                            <th>Jawaban Benar</th>
                            <th>Total Soal</th>
                            <th>Nilai</th>
                            <th>Tanggal &amp; Jam Selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <?php
                            $total = (int) $row['total_soal'];
                            $benar = (int) $row['benar'];
                            $dijawab = (int) $row['dijawab'];

                            $sudahSelesai =
                                !empty($row['waktu_selesai']);

                            // Nilai akhir hanya ditampilkan
                            // jika ujian sudah diselesaikan.
                            if ($total > 0 && $sudahSelesai) {
                                $nilai = round(
                                    ($benar / $total) * 100,
                                    2
                                );

                                $tampilanNilai = number_format(
                                    $nilai,
                                    2,
                                    ',',
                                    '.'
                                );
                            } else {
                                $tampilanNilai = '-';
                            }

                            // Tentukan status pengerjaan
                            if ($total === 0) {
                                $status = 'Belum ada soal';
                                $badge = 'secondary';
                            } elseif ($sudahSelesai) {
                                $status = 'Sudah mengerjakan';
                                $badge = 'success';
                            } elseif ($dijawab > 0) {
                                $status = 'Belum selesai';
                                $badge = 'info';
                            } else {
                                $status = 'Belum mengerjakan';
                                $badge = 'warning';
                            }
                            ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= h($row['nama_ujian']) ?>
                                </td>

                                <td>
                                    <?= h($row['nama_kelas'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= h($row['nama_guru'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= h($row['nama_siswa']) ?>
                                </td>

                                <td><?= $benar ?></td>

                                <td><?= $total ?></td>

                                <td class="fw-bold">
                                    <?= h($tampilanNilai) ?>
                                </td>

                                <td>
                                    <?php if ($sudahSelesai): ?>
                                        <?= date(
                                            'd-m-Y H:i:s',
                                            strtotime(
                                                $row['waktu_selesai']
                                            )
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
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="text-center py-4">
                                Belum ada data ujian dan siswa
                                yang dapat ditampilkan.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

</body>
</html>

<?php
$result->free();
?>

