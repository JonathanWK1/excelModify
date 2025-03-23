<?php
require __DIR__ . '/../vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_FILES["file1"]) || !isset($_FILES["file2"])) {
        die("Both files are required.");
    }

    // Upload files
    $file1 = $_FILES["file1"]["tmp_name"];
    $file2 = $_FILES["file2"]["tmp_name"];
    $transaction_type = $_POST["transaction_type"] ?? ''; // Get selected value safely
    $startRow = 14;
    $outputDir = __DIR__ . '/../output/comparer/';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true); // Creates the folder with full permissions (recursive)
    }
    if ($transaction_type == "FAKTUR")
    {
        $initial = "PU";
    }
    else if ($transaction_type == "RETUR")
    {
        $initial = "RB";
    }
    else
    {
        die("invalid request");
    }

    $columnNomorPU = "C";
    $columnJumlah = "T";

    $columnTrue = "X";
    $columnPUNumber2 = "Y";
    $columnPUTotal = "Z";

    $columnTotal = "AA";
    $columnSelisih = "AB";

    $columnTerakhir = "AB";


    $reader = new Xlsx();
    $reader->setReadDataOnly(true);
     // This significantly reduces memory usage
    $spreadsheet1 = $reader->load($file1);
    $spreadsheet2 = $reader->load($file2);

    $sheet1 = $spreadsheet1->getActiveSheet();
    $sheet2 = $spreadsheet2->getActiveSheet();

    $data1 = [];
    $firstFound = [];

    // Read data1 and store in array if column A starts with 'PU'
    foreach ($sheet1->getRowIterator() as $row) {
        $cellA = trim($sheet1->getCell('A' . $row->getRowIndex())->getValue() ?? '');
        $cellB = trim($sheet1->getCell('B' . $row->getRowIndex())->getValue() ?? '');
        if (strpos($cellA, $initial) === 0) {
            $data1[$cellA] = $cellB;
        }
    }

    // Read data2 and update only the first occurrence
    foreach ($sheet2->getRowIterator($startRow) as $row) {
        $rowIndex = $row->getRowIndex();
        $cellC = trim($sheet2->getCell($columnNomorPU . $rowIndex)->getValue() ?? '');
        if (empty(trim($cellC))) {
            continue; // Skip empty rows properly
        }
        
        if (strpos($cellC, $initial) === 0) {
            if (isset($data1[$cellC])) {

                if (!isset($firstFound[$cellC]))
                {
                    $sheet2->setCellValue($columnTrue . $rowIndex, "true");
                    $sheet2->setCellValue($columnPUNumber2 . $rowIndex, $cellC);
                    $sheet2->setCellValue($columnPUTotal . $rowIndex, $data1[$cellC]);
                    $firstFound[$cellC] = $rowIndex; // Mark as updated
    
                    
                    $cellT = intval(trim($sheet2->getCell($columnJumlah . $rowIndex)->getValue() ?? ''));
                    $amount = $cellT;
                    $sheet2->setCellValue($columnTotal . $rowIndex, $amount);
                    $sheet2->setCellValue($columnSelisih . $rowIndex, intval($data1[$cellC]) - $amount);
                }
                else
                {
                    $firstRowIndex = $firstFound[$cellC];
                    $cellT = intval(trim($sheet2->getCell($columnJumlah . $rowIndex)->getValue() ?? ''));
        
                    $cellAA =intval(trim($sheet2->getCell($columnTotal . $firstRowIndex)->getValue() ?? ''));
                    $cellAB =intval(trim($sheet2->getCell($columnSelisih . $firstRowIndex)->getValue() ?? ''));
                    $sheet2->setCellValue($columnTrue . $rowIndex, "");
                    $sheet2->setCellValue($columnPUNumber2 . $rowIndex, "");
                    $sheet2->setCellValue($columnPUTotal . $rowIndex, "");
                    
                    $amount = $cellAA + $cellT;
                    $sheet2->setCellValue($columnTotal . $firstRowIndex, $amount);
                    $sheet2->setCellValue($columnSelisih . $firstRowIndex, intval($data1[$cellC]) - $amount);
                }
            }
            else {
                $sheet2->setCellValue($columnTrue . $rowIndex, "");
                $sheet2->setCellValue($columnPUNumber2 . $rowIndex, "");
                $sheet2->setCellValue($columnPUTotal . $rowIndex, "");

                $sheet2->getStyle('A' . $rowIndex . ':'. $columnTerakhir . $rowIndex)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6']
                    ]
                ]);
            }
        } else {
            $sheet2->setCellValue($columnTrue . $rowIndex, "");
            $sheet2->setCellValue($columnPUNumber2 . $rowIndex, "");
            $sheet2->setCellValue($columnPUTotal . $rowIndex, "");

            // Highlight row blue if no match is found
            $sheet2->getStyle('A' . $rowIndex . ':' . $columnTerakhir . $rowIndex)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FF6666'] // Light blue
                ]
            ]);
        }

    }

    // Find the last row in Excel 2
    $lastRow = $sheet2->getHighestRow() + 1;

    // Append unmatched PU values at the bottom
    foreach ($data1 as $puKey => $puValue) {
        if (!isset($firstFound[$puKey])) 
        {
            $sheet2->setCellValue($columnPUNumber2 . $lastRow, $puKey);
            $sheet2->setCellValue($columnPUTotal . $lastRow, $puValue);
            
            $sheet2->getStyle('A' . $lastRow . ':' . $columnTerakhir . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFF00'] // Yellow highlight for unmatched PU
                ]
            ]);
            $lastRow++;
        }
    }

    // Save modified file
    $outputFile = $outputDir.'modified_data2.xlsx';
    $writer = IOFactory::createWriter($spreadsheet2, 'Xlsx');
    $writer->save($outputFile);
    $spreadsheet2->disconnectWorksheets();
    unset($spreadsheet2);
    // Return JSON response with download link

    header('Content-Type: application/json');
    echo json_encode(['download' => basename($outputFile)]);
}