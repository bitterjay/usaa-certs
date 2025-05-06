<?php
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
$name_y = isset($_POST['name_y']) ? intval($_POST['name_y']) : 400;
$details_y = isset($_POST['details_y']) ? intval($_POST['details_y']) : 500;
$name_size = isset($_POST['name_size']) ? intval($_POST['name_size']) : 36;
$details_size = isset($_POST['details_size']) ? intval($_POST['details_size']) : 18;

$records = parse_csv($csv_path);
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
$pdf->AddFont('Poppins', 'B', 'poppins.php');
$pdf->setBg($bg_path);

foreach ($records as $rec) {
    $pdf->AddPage();
    $w = $pdf->GetPageWidth();
    // Name
    $pdf->SetFont('Poppins', 'B', $name_size);
    $pdf->SetTextColor(170, 31, 46);
    $name = $rec['fullName'];
    $name_w = $pdf->GetStringWidth($name);
    $pdf->SetXY(($w-$name_w)/2, $name_y);
    $pdf->Cell($name_w, 10, $name, 0, 1, 'C');
    // Details
    $details = array_filter($rec['details']);
    if ($details) {
        $pdf->SetFont('Poppins', 'B', $details_size);
        $pdf->SetTextColor(28, 53, 94);
        $details_str = implode('      |      ', $details);
        $details_w = $pdf->GetStringWidth($details_str);
        $pdf->SetXY(($w-$details_w)/2, $details_y);
        $pdf->Cell($details_w, 10, $details_str, 0, 1, 'C');
    }
}

$pdf_content = $pdf->Output('S');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificates.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;
exit;
?> 