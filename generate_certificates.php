<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'pdf_generation_errors.log');
error_reporting(E_ALL);

require_once('fpdf.php');

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

// No conversion needed: use Y positions (mm) and font sizes (pt) directly
$name_y_mm = $name_y;
$details_y_mm = $details_y;
$name_size_pt = $name_size;
$details_size_pt = $details_size;

// Ensure the background image has a .png extension for FPDF
$bg_tmp_with_ext = tempnam(sys_get_temp_dir(), 'bg_') . '.png';
copy($bg_path, $bg_tmp_with_ext);

$records = parse_csv($csv_path);
error_log('Parsed CSV records: ' . print_r($records, true));
if (!$records) {
    http_response_code(400);
    echo 'No valid records in CSV.';
    exit;
}

// PDF setup
class CertificatePDF extends FPDF {
    public $bg;
    function setBg($bg) { $this->bg = $bg; }
    function Header() {
        if ($this->bg) {
            $this->Image($this->bg, 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
        }
    }
}
$pdf = new CertificatePDF('L', 'mm', array(279.4, 215.9)); // Letter landscape
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(0, 0, 0);
$pdf->setBg($bg_tmp_with_ext);

foreach ($records as $rec) {
    $pdf->AddPage();
    $w = $pdf->GetPageWidth();
    // Name
    $pdf->SetFont('Helvetica', 'B', $name_size_pt);
    $pdf->SetTextColor(170, 31, 46);
    $name = $rec['fullName'];
    $name_w = $pdf->GetStringWidth($name);
    $name_top = $name_y_mm - ($name_size_pt * 0.3528);
    $pdf->SetXY(($w-$name_w)/2, $name_top);
    $pdf->Cell($name_w, 10, $name, 0, 1, 'C');
    // Details
    $details = array_filter($rec['details']);
    if ($details) {
        $pdf->SetFont('Helvetica', 'B', $details_size_pt);
        $pdf->SetTextColor(28, 53, 94);
        $bullet = '|';
        $space = 6.35; // mm
        // Measure total width
        $totalWidth = 0;
        foreach ($details as $i => $d) {
            if ($i > 0) $totalWidth += $space + $pdf->GetStringWidth($bullet) + $space;
            $totalWidth += $pdf->GetStringWidth($d);
        }
        $x = ($w - $totalWidth) / 2;
        $y = $details_y_mm - ($details_size_pt * 0.3528);
        $pdf->SetXY($x, $y);
        foreach ($details as $i => $d) {
            if ($i > 0) {
                $pdf->Cell($space, 10, '', 0, 0, 'L');
                $pdf->SetTextColor(170, 31, 46); // Red bullet
                $pdf->Cell($pdf->GetStringWidth($bullet), 10, $bullet, 0, 0, 'L');
                $pdf->SetTextColor(28, 53, 94); // Restore details text color
                $pdf->Cell($space, 10, '', 0, 0, 'L');
            }
            $pdf->Cell($pdf->GetStringWidth($d), 10, $d, 0, 0, 'L');
        }
        $pdf->Ln(10);
    }
}

$pdf_content = $pdf->Output('S');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificates.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;
@unlink($bg_tmp_with_ext);
exit;
?> 