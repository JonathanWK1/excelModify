<?php
require __DIR__ . '/../vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_FILES["file1"])) {
        die("Both files are required.");
    }

    // Upload files
    $file = $_FILES["file1"]["tmp_name"];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $outputDir = __DIR__ . '/../output/separator/';
    $dataByMonthYear = []; // Store grouped data
    
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true); // Creates the folder with full permissions (recursive)
    }
    
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex = $row->getRowIndex();
        
        $type = trim($sheet->getCell('C' . $rowIndex)->getValue() ?? '');
        $idValue = trim($sheet->getCell('B' . $rowIndex)->getValue() ?? '');
        $dataE = trim($sheet->getCell('E' . $rowIndex)->getValue() ?? '');
    
        // Only process rows where C column == "Faktur"
        if ($type !== 'Retur') {
            continue;
        }
    
        // Only process rows where C column == "Faktur"
        if ($type !== 'Retur' || empty($idValue)) {
            continue;
        }
    
        // Extract Year and Month from Column B (ID)
        if (preg_match('/^RB([A-Z0-9]{2})(\d{2})(\d{2})\d+$/', $idValue, $matches)) {
            $year = $matches[2];  // YYYY
            $monthNumber = $matches[3]; // MM        
            //$monthName = DateTime::createFromFormat('!m', $monthNumber)->format('F'); // Convert "07" to "July"
            $monthName = 1;
        } else {
            continue; // Skip if ID format is invalid
        }
        
        // Remove parentheses from E column
        $cleanedE = preg_replace('/[\(\)]/', '', $dataE);
    
        // Store data
        $dataByMonthYear[$year][$monthName][] = [$idValue, $cleanedE];
    }
    
    $zip = new ZipArchive();
    $zipFileName = $outputDir . "Retur_Files.zip"; // ZIP file path
    
    // Delete old ZIP if exists
    if (file_exists($zipFileName)) {
        unlink($zipFileName);
    }

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Generate separate Excel files for each month & year
        foreach ($dataByMonthYear as $year => $months) {
            foreach ($months as $monthName => $rows) {
        
                // **SORT by Column B (String)**
                usort($rows, function ($a, $b) {
                    return strcmp($a[0], $b[0]); // Sort by second column (B)
                });
        
                $newSpreadsheet = new Spreadsheet();
                $newSheet = $newSpreadsheet->getActiveSheet();
        
                // Insert data
                $rowIndex = 1;
                foreach ($rows as $rowData) {
                    $newSheet->fromArray($rowData, null, 'A' . $rowIndex);
                    $rowIndex++;
                }
        
                // Save the new file
                $fileName = "Retur_{$monthName}_{$year}.xlsx";
                $writer = IOFactory::createWriter($newSpreadsheet, 'Xlsx');
                $writer->save($outputDir.$fileName);
                // **Add the Excel file to the ZIP archive**
                $zip->addFile($outputDir . $fileName, $fileName);
            }
        }
    }
    
    $zip->close();
    // Return JSON response with download link

    header('Content-Type: application/json');
    echo json_encode(['download' => basename($zipFileName)]);
}
?>
