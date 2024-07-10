<?php

namespace App\services;

use App\Controllers\ApiController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use stdClass;

class ExcelReportEventsService
{

    public function setDocumentProperties(Spreadsheet $spreadsheet)
    {
        // Configurar propiedades del documento
        return $spreadsheet->getProperties()->setCreator("Tu nombre")
            ->setLastModifiedBy("Tu nombre")
            ->setTitle("Reporte de Inscripciones XXI Congreso y Precongreso Cientifíco Médico")
            ->setSubject("Reporte de inscripciones")
            ->setDescription("Este archivo contiene el reporte de inscripciones.")
            ->setKeywords("inscripciones reporte excel")
            ->setCategory("Reporte");
    }

    public function setHeaders(Worksheet $sheet)
    {
        // Configurar encabezados
        $sheet->mergeCells('B2:B3');
        $sheet->setCellValue('B2', 'Fecha');

        $sheet->mergeCells('C2:C3');
        $sheet->setCellValue('C2', 'Plan Inscripción');

        $sheet->mergeCells('D2:D3');
        $sheet->setCellValue('D2', 'Cantidad');

        return $sheet;
    }

    public function getData(array $parametros): array
    {
        $Api = new ApiController();
        $datos = $Api->getPlanInscripcionEvents($parametros);
        $resultados = [];
        foreach ($datos as $data) {
            // var_dump($data);

            // Crear el objeto de datos
            $objectData = new stdClass();
            $objectData->cantidad = $data->cantidad;
            $objectData->plan = $data->plan;

            // Verificar si la fecha ya existe en el array resultados
            if (!isset($resultados[$data->fecha])) {
                // Si no existe, crear un array vacío para esa fecha
                $resultados[$data->fecha] = [];
            }

            // Añadir el objeto de datos al array correspondiente a la fecha
            array_push($resultados[$data->fecha], $objectData);
        }

        return $resultados;
    }

    public function formData(array $data, Worksheet $sheet, int $fila): void
    {
        $row = $fila;
        $rowColumn = 0;
        foreach ($data as $fecha => $planes) {
            $numPlanes = count($planes);
            // Fusionar celdas para la fecha
            $sheet->mergeCells('B' . $row . ':B' . ($row + $numPlanes - 1));
            $sheet->setCellValue('B' . $row, $fecha);

            foreach ($planes as $index => $plan) {
                $currentRow = $row + $index;
                $sheet->setCellValue('C' . $currentRow, $plan->plan);
                $sheet->setCellValue('D' . $currentRow, $plan->cantidad);
            }

            // Incrementar $row para la próxima fecha
            $row += $numPlanes;
            $rowColumn = $row;
        }

        //total
        // Calcular totales para la fila actual
        $sheet->setCellValue('D' . $rowColumn, "=SUM(D${fila}:D${rowColumn})");

        $sheet->mergeCells('B' . $rowColumn . ':C' . $rowColumn);
        $sheet->setCellValue('B' . $rowColumn, 'TOTAL');

        // Aplicar estilos a las celdas
        $sheet->getStyle('B2:D2')->getFont()->setBold(true);
        $sheet->getStyle('B2:B'.$rowColumn)->getFont()->setBold(true);
        $sheet->getStyle('B'.$rowColumn.':D'.$rowColumn)->getFont()->setBold(true);
        $sheet->getStyle('B2:D'.$rowColumn)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:D'.$rowColumn)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B2:B9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B9')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        // Opcional: Ajustar el ancho de las columnas para mejor visualización
        foreach (range('B', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Aplicar bordes a las celdas
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('B2:D' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
    }

    public function saveFile(Spreadsheet $spreadsheet, string $reportName): array
    {
         // Guardar el archivo
         $writer = new Xlsx($spreadsheet);
         $writer->save("$reportName.xlsx");
 
         // Guardar el archivo en el servidor
         $filename = "$reportName.xlsx";
         $writer = new Xlsx($spreadsheet);
         $writer->save($filename);
         return [
             "writer" => $writer,
             "filename" => $filename
         ];
    }
}
