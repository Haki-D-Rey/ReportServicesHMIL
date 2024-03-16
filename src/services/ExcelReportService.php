<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelReportService
{
    public function generateUserReport($users)
    {
        // Crear una instancia de la hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Añadir encabezados
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->setCellValue('C1', 'Correo');

        // Añadir datos al reporte
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user['id']);
            $sheet->setCellValue('B' . $row, $user['name']);
            $sheet->setCellValue('C' . $row, $user['email']);
            $row++;
        }

        // Guardar el reporte en un archivo
        $filename = 'user_report.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        return $filename;
    }
}
