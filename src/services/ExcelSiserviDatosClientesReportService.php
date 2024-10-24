<?php

namespace App\services;

use App\Controllers\ApiController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\TYPE_NUMERIC;

class ExcelSiserviDatosClientesReportService
{
    public function setDocumentProperties(Spreadsheet $spreadsheet)
    {
        // Configurar propiedades del documento
        return $spreadsheet->getProperties()->setCreator("GTI")
            ->setLastModifiedBy("GTI")
            ->setTitle("Reporte de ventas")
            ->setSubject("Reporte de ventas")
            ->setDescription("Este archivo contiene el reporte de ventas.")
            ->setKeywords("ventas reporte excel")
            ->setCategory("Reporte");
    }

    public function setHeaders(Worksheet $sheet)
    {
        // Fusión de celdas para "N"
        $sheet->mergeCells('B2:B3');
        $sheet->setCellValue('B2', 'N');

        // Fusión de celdas para "NOMBRE COMPLETO"
        $sheet->mergeCells('C2:C3');
        $sheet->setCellValue('C2', 'NOMBRE COMPLETO');

        // Fusión de celdas para "CARNET"
        $sheet->mergeCells('D2:D3');
        $sheet->setCellValue('D2', 'CARNET');

        // Fusión de celdas para "SEDE"
        $sheet->mergeCells('E2:E3');
        $sheet->setCellValue('E2', 'COMEDOR');

        // Fusión de celdas para "DEPARTAMENTO"
        $sheet->mergeCells('F2:F3');
        $sheet->setCellValue('F2', 'DEPARTAMENTO');

        // Fusión de celdas para "SERVICIO"
        $sheet->mergeCells('G2:G3');
        $sheet->setCellValue('G2', 'SERVICIO');

        // Fusión de celdas para "CANTIDAD"
        $sheet->mergeCells('H2:H3');
        $sheet->setCellValue('H2', 'CANTIDAD');

        // Fusión de celdas para "FECHA CORTE"
        $sheet->mergeCells('I2:I3');
        $sheet->setCellValue('I2', 'FECHA CORTE');

        // Aplicar estilos opcionales a los encabezados
        $headerStyleArray = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        // Aplicar estilos a los encabezados en las filas 2 y 3
        $sheet->getStyle('B2:I3')->applyFromArray($headerStyleArray);

        return $sheet;
    }


    public function getData(array $parametros): array
    {
        $Api = new ApiController();
        $data = $Api->getSellSiServiClientService($parametros);
        return $data;
    }

    public function formData(array $data, array $style, int $row, Worksheet $sheet): void
    {
        // Aplicar estilos a las filas 2 y 3 (encabezados)
        $sheet->getStyle("B2:I3")->applyFromArray($style);

        // Inicializar fila donde comenzarán los datos
        $row = 4;

        // Iterar sobre cada objeto stdClass en el array de datos
        foreach ($data as $item) {
            // Extraer datos del stdClass
            $contador = $item->contador ?? ''; // Columna A (N)
            $nombrecompleto = $item->nombrecompleto ?? ''; // Columna B (NOMBRE COMPLETO)
            $carnet = $item->carnet ?? ''; // Columna C (CARNET)
            $sede = $item->sede ?? ''; // Columna D (SEDE)
            $departamento = $item->departamento ?? ''; // Columna E (DEPARTAMENTO)
            $servicio = $item->servicio ?? ''; // Columna F (SERVICIO)
            $cantidad = $item->cantidad ?? ''; // Columna G (CANTIDAD)
            $fechacorte = $item->fechacorte ?? ''; // Columna H (FECHA CORTE)

            // Asignar valores a las celdas según los encabezados
            $sheet->setCellValue('B' . $row, $contador); // N
            $sheet->setCellValue('C' . $row, $nombrecompleto); // NOMBRE COMPLETO
            $sheet->setCellValueExplicit('D' . $row, $item->carnet, DataType::TYPE_NUMERIC);
            $sheet->setCellValue('E' . $row, $sede); // SEDE
            $sheet->setCellValue('F' . $row, $departamento); // DEPARTAMENTO
            $sheet->setCellValue('G' . $row, $servicio); // SERVICIO
            $sheet->setCellValue('H' . $row, $cantidad); // CANTIDAD
            $sheet->setCellValue('I' . $row, $fechacorte); // FECHA CORTE

            // Aplicar estilo a la fila actual
            $sheet->getStyle("B${row}:I${row}")->applyFromArray($style);

            // Avanzar a la siguiente fila
            $row++;
        }

         // Configurar anchos de columna
         foreach (range('B', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
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