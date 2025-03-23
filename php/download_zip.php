<?php
// $zipFileName = "Retur_Files.zip";
$fileName = isset($_GET['file']) ? $_GET['file'] : null;
$outputPath = __DIR__ . "/../output/" . $fileName;

// Check if the file exists before downloading
if (!file_exists($outputPath)) {
    die("File not found." .$outputPath);
}

// Send the file as a download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Length: ' . filesize($outputPath));
readfile($outputPath);
exit;
?>
