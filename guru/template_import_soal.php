
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Template Soal');

$data = [
    [
        'tipe_soal',
        'teks_soal',
        'opsi_a',
        'opsi_b',
        'opsi_c',
        'opsi_d',
        'jawaban_benar'
    ],
    [
        'pilihan_ganda',
        'Berapakah hasil 2 + 3?',
        '4',
        '5',
        '6',
        '7',
        'B'
    ],
    [
        'esai',
        'Jelaskan proses fotosintesis!',
        '',
        '',
        '',
        '',
        ''
    ]
];

$sheet->fromArray($data, null, 'A1');

// Format header
$sheet->getStyle('A1:G1')->getFont()->setBold(true);

// Atur lebar kolom
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(40);

foreach (range('C', 'G') as $kolom) {
    $sheet->getColumnDimension($kolom)->setWidth(18);
}

// Bungkus teks agar mudah dibaca
$sheet->getStyle('A1:G3')
    ->getAlignment()
    ->setWrapText(true);

// Bersihkan semua output buffer sebelum mengirim file Excel
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template_import_soal.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
