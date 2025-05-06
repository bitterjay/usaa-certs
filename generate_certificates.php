<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'pdf_generation_errors.log');
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

// Helper: Parse CSV file
function parse_csv($file) {
    $rows = [];
    if (($handle = fopen($file, 'r')) !== false) {
        fgetcsv($handle); // skip header
        while (($row = fgetcsv($handle)) !== false) {
            if (!empty($row[0]) || !empty($row[1])) {
                $rows[] = [
                    'fullName' => strtoupper(trim($row[0] . ' ' . $row[1])),
                    'details' => array_map(function($v) { return strtoupper(trim($v)); }, array_slice($row, 2, 3))
                ];
            }
        }
        fclose($handle);
    }
    return $rows;
}

// Get POSTed files and settings
if (!isset($_FILES['background'], $_FILES['csv'])) {
    http_response_code(400);
    echo 'Missing files.';
    exit;
}
$bg_path = $_FILES['background']['tmp_name'];
$csv_path = $_FILES['csv']['tmp_name'];
$name_y = isset($_POST['name_y']) ? floatval($_POST['name_y']) : 111.8;
$details_y = isset($_POST['details_y']) ? floatval($_POST['details_y']) : 130.1;
$name_size = isset($_POST['name_size']) ? intval($_POST['name_size']) : 36;
$details_size = isset($_POST['details_size']) ? intval($_POST['details_size']) : 18;

$records = parse_csv($csv_path);
error_log('Parsed CSV records: ' . print_r($records, true));
if (!$records) {
    http_response_code(400);
    echo 'No valid records in CSV.';
    exit;
}

// Prepare background image as base64
$bg_data = base64_encode(file_get_contents($bg_path));
$bg_mime = mime_content_type($bg_path);
$bg_url = "data:$bg_mime;base64,$bg_data";

// mPDF setup
$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$mpdf = new \Mpdf\Mpdf([
    'format' => [279.4, 215.9], // Letter landscape in mm
    'orientation' => 'L',
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
    'margin_header' => 0,
    'margin_footer' => 0,
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
    'fontdata' => [
        'poppins' => [
            'B' => 'Poppins-Bold.ttf',
        ],
    ],
    'default_font' => 'poppins',
]);

foreach ($records as $rec) {
    $mpdf->AddPage();
    $details = array_filter($rec['details']);
    $details_html = '';
    if ($details) {
        $details_html = '';
        foreach ($details as $i => $d) {
            if ($i > 0) {
                $details_html .= '<span style="color:#aa1f2e;font-weight:bold;margin:0 6.35mm;">|</span>';
            }
            $details_html .= '<span style="color:#1c355e;font-weight:bold;">' . htmlspecialchars($d) . '</span>';
        }
    }
    $html = '<html><head><style>
    @font-face {
        font-family: "Poppins";
        src: url("fonts/Poppins-Bold.ttf") format("truetype");
        font-weight: bold;
    }
    body { margin: 0; padding: 0; }
    .bg { position: absolute; left: 0; top: 0; width: 279.4mm; height: 215.9mm; z-index: 0; }
    .name {
        position: absolute;
        left: 50%;
        top: ' . $name_y . 'mm;
        transform: translateX(-50%);
        color: #aa1f2e;
        font-size: ' . $name_size . 'pt;
        font-family: "Poppins", Arial, sans-serif;
        font-weight: bold;
        white-space: nowrap;
        z-index: 1;
    }
    .details {
        position: absolute;
        left: 50%;
        top: ' . $details_y . 'mm;
        transform: translateX(-50%);
        font-size: ' . $details_size . 'pt;
        font-family: "Poppins", Arial, sans-serif;
        font-weight: bold;
        white-space: nowrap;
        z-index: 1;
    }
    </style></head><body>
    <img src="' . $bg_url . '" class="bg" />
    <div class="name">' . htmlspecialchars($rec['fullName']) . '</div>
    <div class="details">' . $details_html . '</div>
</body></html>';
    $mpdf->WriteHTML($html);
}

$pdf_content = $mpdf->Output('', 'S');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificates.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;
exit;
?> 