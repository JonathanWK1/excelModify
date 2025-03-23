<?php
// $zipFileName = "Retur_Files.zip";
$zipFileName = isset($_GET['file']) ? basename($_GET['file']) : null;
$outputPath = __DIR__ . "/../output/separator/" . $zipFileName;

// Check if the file exists before downloading
if (!file_exists($outputPath)) {
    die("File not found." .$outputPath);
}

// Send the file as a download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Length: ' . filesize($outputPath));
readfile($outputPath);
exit;
?>
