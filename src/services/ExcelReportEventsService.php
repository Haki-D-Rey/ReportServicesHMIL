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

    public function setHeadersInstitutions(Worksheet $sheet)
    {
        // Configurar encabezados
        $sheet->mergeCells('B2:B3');
        $sheet->setCellValue('B2', 'Fecha');

        $sheet->mergeCells('C2:C3');
        $sheet->setCellValue('C2', 'Tipo Institución');

        $sheet->mergeCells('D2:D3');
        $sheet->setCellValue('D2', 'Plan Inscripción');

        $sheet->mergeCells('E2:E3');
        $sheet->setCellValue('E2', 'Cantidad');

        return $sheet;
    }

    public function getData(array $parametros): array
    {
        $Api = new ApiController();
        $datos = $Api->getPlanInscripcionEvents($parametros);
        $datosInstitutions = $Api->getPlanInscripcionEventsInstitutions($parametros);
        $resultadosInstituciones = $this->validateStructuredInstitutionsData($datosInstitutions);

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

        return [
            "Datos" => $resultados,
            "Datos_Instituciones" => $resultadosInstituciones
        ];
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
        $sheet->getStyle('B2:B' . $rowColumn)->getFont()->setBold(true);
        $sheet->getStyle('B' . $rowColumn . ':D' . $rowColumn)->getFont()->setBold(true);
        $sheet->getStyle('B2:D' . $rowColumn)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:D' . $rowColumn)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
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

    public function formDataInstituciones(array $data, Worksheet $sheet, int $fila): void
    {
        $row = $fila;
        $numPlanes = count($data);
        $rowColumn = $row + $numPlanes;
        foreach ($data as $index => $item) {
            $currentRow = $row + $index;
            // Fusionar celdas para la fecha
            $sheet->mergeCells('B' . $row . ':B' . ($rowColumn - 1));
            $sheet->setCellValue('B' . $row, date('Y-m-d'));

            $sheet->setCellValue('C' . $currentRow, $item->nombre_institucion);
            $sheet->setCellValue('D' . $currentRow, $item->plan);
            $sheet->setCellValue('E' . $currentRow, $item->total_cantidad);
        }

        //total
        // Calcular totales para la fila actual
        $sheet->setCellValue('E' . $rowColumn, "=SUM(E${fila}:E${rowColumn})");

        $sheet->mergeCells('B' . $rowColumn . ':D' . $rowColumn);
        $sheet->setCellValue('B' . $rowColumn, 'TOTAL');

        // Aplicar estilos a las celdas
        $sheet->getStyle('B2:E2')->getFont()->setBold(true);
        $sheet->getStyle('B2:B' . $rowColumn)->getFont()->setBold(true);
        $sheet->getStyle('B' . $rowColumn . ':E' . $rowColumn)->getFont()->setBold(true);
        $sheet->getStyle('B2:E' . $rowColumn)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:E' . $rowColumn)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B2:B9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B9')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        // Opcional: Ajustar el ancho de las columnas para mejor visualización
        foreach (range('B', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Aplicar bordes a las celdas
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('B2:E' . $highestRow)->applyFromArray([
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

    public function validateStructuredInstitutionsData(array $datosInstitutions): array
    {
        $Api = new ApiController();
        $resultados = [];
        $validarInstituciones = [
            '/\bSERMESA\b/i',
            '/\bHCRH\b/i',
            '/\bHOSPITAL CARLOS ROBERTOS\b/i',
            '/\bMINSA\b/i',
            '/\bINSS\b/i',
            '/\bHOSPITAL SALUD INTEGRAL\b/i',
            '/\bHOSPITAL VIVIAN PELLAS\b/i',
            '/\bHOSPITAL SERMESA\b/i',
            '/\bUAM\b/i',
            '/\bFACMED\b/i',
        ];

        // Recorremos el arreglo original
        foreach ($datosInstitutions as $item) {
            $idTipoInstitucion = $item->id_tipo_institucion_oficial;
            $nombreInstitucion = $item->nombre_institucion;
            $plan = $item->plan;
            $cantidad = $item->cantidad;
            $idTipoPlanesInscripcion = $item->id_tipo_planes_inscripcion;
            // Verificamos si el nombre de la institución contiene "HOSPITAL MILITAR"
            if (strpos($nombreInstitucion, 'HOSPITAL MILITAR') !== false) {
                $nombreInstitucion = 'HOSPITAL MILITAR ESCUELA "DR. ALEJANDRO DAVÍLA BOLAÑOS"';
            } else {
                $encontrado = false;
                foreach ($validarInstituciones as $regex) {
                    if (preg_match($regex, $nombreInstitucion)) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado && $idTipoInstitucion == 6) {
                    $nombreInstitucion = 'USUARIOS PARTICULARES';
                }
            }

            // Creamos una clave única para identificar el grupo por id_tipo_institucion_oficial, nombre_institucion y id_tipo_planes_inscripcion
            $clave = $idTipoInstitucion . '_' . $nombreInstitucion . '_' . $idTipoPlanesInscripcion;

            // Si la clave aún no existe en el arreglo de resultados, la inicializamos
            if (!isset($resultados[$clave])) {
                $remakeInstitutionName = $idTipoInstitucion === 6 ? $nombreInstitucion : $Api->getNameInstitutionOficial($idTipoInstitucion);
                $resultados[$clave] = (object) [
                    "id_tipo_institucion_oficial" => $idTipoInstitucion,
                    "nombre_institucion" => $remakeInstitutionName,
                    "id_tipo_planes_inscripcion" => $idTipoPlanesInscripcion,
                    "plan" => $plan,
                    "total_cantidad" => 0 // Inicializamos la cantidad total en cero
                ];
            }

            // Sumamos la cantidad al objeto correspondiente
            $resultados[$clave]->total_cantidad += $cantidad;
        }

        $resultados = array_values($resultados);

        // Creamos un arreglo auxiliar para almacenar los nombres de institución y sus respectivos índices
        $nombreIndex = [];
        foreach ($resultados as $index => $item) {
            $nombreInstitucion = $item->nombre_institucion;
            $nombreIndex[$index] = $nombreInstitucion;
        }

        // Ordenamos los índices basándonos en los nombres de institución
        array_multisort($nombreIndex, SORT_ASC, $resultados);

        return $resultados;
    }

}
