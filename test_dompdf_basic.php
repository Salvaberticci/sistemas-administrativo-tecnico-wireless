<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml('<h1>Test PDF</h1><p>If you see this, Dompdf is working.</p>');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Save to file instead of streaming to avoid header issues in CLI
file_put_contents('test_output.pdf', $dompdf->output());
echo "PDF generated successfully as test_output.pdf\n";
?>