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
    'format' => 'Letter', // US Letter size (8.5x11")
    'orientation' => 'L', // Landscape
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
    'margin_header' => 0,
    'margin_footer' => 0,
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
    'fontdata' => [
        'poppins' => [
            'R' => 'Poppins-Regular.ttf',
            'B' => 'Poppins-Bold.ttf',
            'I' => 'Poppins-Italic.ttf',
            'BI' => 'Poppins-BoldItalic.ttf',
        ],
    ],
    'default_font' => 'poppins',
]);

try {
    foreach ($records as $rec) {
        $mpdf->AddPage();
        $details = array_filter($rec['details']);
        $details_html = '';
        if ($details) {
            $details_html = '';
            foreach ($details as $i => $d) {
                if ($i > 0) {
                    $details_html .= '<span class="pipe">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;</span>';
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
        @page {
            margin: 0;
            padding: 0;
            size: 279.4mm 215.9mm landscape;
        }
        body { 
            margin: 0; 
            padding: 0;
            width: 279.4mm;
            height: 215.9mm;
            position: relative;
        }
        .bg { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 279.4mm; 
            height: 215.9mm; 
            z-index: 0; 
            object-fit: cover;
            display: block;
        }
        .name {
            position: absolute;
            left: 0;
            right: 0;
            top: ' . $name_y . 'mm;
            margin: 0 auto;
            color: #aa1f2e;
            font-size: ' . ($name_size * 1.35) . 'pt;
            font-family: "Poppins", Arial, sans-serif;
            font-weight: bold;
            white-space: nowrap;
            z-index: 1;
            text-align: center;
            width: 100%;
            line-height: 1;
        }
        .details {
            position: absolute;
            left: 0;
            right: 0;
            top: ' . $details_y . 'mm;
            margin: 0 auto;
            font-size: ' . ($details_size * 1.35) . 'pt;
            font-family: "Poppins", Arial, sans-serif;
            font-weight: bold;
            white-space: nowrap;
            z-index: 1;
            text-align: center;
            width: 100%;
            line-height: 1;
        }
        body, .name, .details {
            font-family: "Poppins", Arial, sans-serif !important;
        }
        .pipe {
            color: #aa1f2e;
            font-weight: bold;
            padding: 0 2mm;
            font-size: inherit;
            display: inline-block;
            vertical-align: middle;
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
} catch (\Mpdf\MpdfException $e) {
    error_log('PDF generation error: ' . $e->getMessage());
    http_response_code(500);
    echo 'An error occurred while generating the PDF: ' . htmlspecialchars($e->getMessage());
    exit;
} catch (\Throwable $e) {
    error_log('General error: ' . $e->getMessage());
    http_response_code(500);
    echo 'A server error occurred: ' . htmlspecialchars($e->getMessage());
    exit;
}
?> 