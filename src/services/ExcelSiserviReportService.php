<?php

namespace App\services;

use App\Controllers\ApiController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelSiserviReportService
{
    public function setDocumentProperties(Spreadsheet $spreadsheet)
    {
        // Configurar propiedades del documento
        return $spreadsheet->getProperties()->setCreator("Tu nombre")
            ->setLastModifiedBy("Tu nombre")
            ->setTitle("Reporte de ventas")
            ->setSubject("Reporte de ventas")
            ->setDescription("Este archivo contiene el reporte de ventas.")
            ->setKeywords("ventas reporte excel")
            ->setCategory("Reporte");
    }

    public function setHeaders(Worksheet $sheet)
    {
        // Configurar encabezados
        // Fusionar Celda Fecha
        $sheet->mergeCells('B2:B3');
        $sheet->setCellValue('B2', 'FECHA');

        // Fusionar celdas para SEDE GENERAL
        $sheet->mergeCells('C2:F2');
        // Establecer valor para SEDE GENERAL
        $sheet->setCellValue('C2', 'SEDE GENERAL');
        $sheet->mergeCells('G2:G3');
        $sheet->mergeCells('L2:L3');
        // Fusionar celdas para SEDE OFICIALES
        $sheet->mergeCells('H2:K2');
        // Establecer valor para SEDE OFICIALES
        $sheet->setCellValue('H2', 'SEDE OFICIALES');

        // Fusionar celdas para SEDE OFICIALES
        $sheet->mergeCells('M2:M3');
        // Establecer valor para SEDE OFICIALES
        $sheet->setCellValue('M2', 'TOTAL');

        // Establecer encabezados
        $sheet->setCellValue('C3', 'DESAYUNO')
            ->setCellValue('D3', 'ALMUERZO')
            ->setCellValue('E3', 'CENA')
            ->setCellValue('F3', 'REFACCION')
            ->setCellValue('G2', 'TOTAL GENERAL')
            ->setCellValue('H3', 'DESAYUNO')
            ->setCellValue('I3', 'ALMUERZO')
            ->setCellValue('J3', 'CENA')
            ->setCellValue('K3', 'REFACCION')
            ->setCellValue('L2', 'TOTAL OFICIALES');
        return $sheet;
    }


    public function getData(array $parametros): array
    {
        $Api = new ApiController();
        $data = $Api->getSellSiServi($parametros);
        return $data;
    }

    public function mapServices(array $mapeoServicios): array
    {
        // Convertir el arreglo de mapeo a un formato más eficiente para búsqueda
        $mapeoIndexado = [];
        foreach ($mapeoServicios as $servicio) {
            foreach ($servicio["letter_excel"] as $letter) {
                $mapeoIndexado[$servicio["serv_id"]][$letter["sede_id"]] = $letter["letter"];
            }
        }

        return $mapeoIndexado;
    }

    public function restructureData(array $data, array $serviciosEsperados, array $mapeoServicios): array
    {
        // Inicializar el arreglo reestructurado con elementos vacíos para cada combinación de sede y servicio
        $reestructurado = [];
        foreach ($data as $item) {
            $fecha = $item["fecha"];
            $sede_id = $item["sede_id"];
            $serv_id = $item["serv_id"];

            // Verificar si la fecha ya está en el arreglo reestructurado
            if (!isset($reestructurado[$fecha])) {
                $reestructurado[$fecha] = [];
            }

            // Verificar si el servicio esperado para esta sede está definido
            if (isset($serviciosEsperados[$serv_id]) && in_array($sede_id, $serviciosEsperados[$serv_id])) {
                // Si es así, agregar el item con letter_excel correspondiente si está definido
                $letter_excel = isset($mapeoServicios[$serv_id][$sede_id]) ? $mapeoServicios[$serv_id][$sede_id] : "";
                $reestructurado[$fecha][] = [
                    "sede_id" => $sede_id,
                    "serv_id" => $serv_id,
                    "total_por_servicio_y_sede" => $item["total_por_servicio_y_sede"],
                    "letter_excel" => $letter_excel
                ];
            }
        }

        $reestructurado = $this->resetValue($reestructurado, $serviciosEsperados, $mapeoServicios);
        return $reestructurado;
    }

    public function resetValue(array $reestructurado, array $serviciosEsperados, array $mapeoServicios): array
    {
        // Añadir los servicios que no están presentes en $data pero se esperan
        foreach ($serviciosEsperados as $serv_id => $sedes) {
            foreach ($sedes as $sede_id) {
                foreach ($reestructurado as $fecha => $items) {
                    $encontrado = false;
                    foreach ($items as $item) {
                        if ($item['sede_id'] === $sede_id && $item['serv_id'] === $serv_id) {
                            $encontrado = true;
                            break;
                        }
                    }
                    if (!$encontrado) {
                        $letter_excel = isset($mapeoServicios[$serv_id][$sede_id]) ? $mapeoServicios[$serv_id][$sede_id] : "";
                        $reestructurado[$fecha][] = [
                            "sede_id" => $sede_id,
                            "serv_id" => $serv_id,
                            "total_por_servicio_y_sede" => "0.00", // Valor por defecto
                            "letter_excel" => $letter_excel
                        ];
                    }
                }
            }
        }
        return $reestructurado;
    }

    public function formData(array $data, array $style, int $row, Worksheet $sheet): void
    {

        $sheet->getStyle("B2:M2")->applyFromArray($style);
        $sheet->getStyle("B3:M3")->applyFromArray($style);

        $row = 4;
        foreach ($data as $fecha => $datos) {
            // Insertar la fecha en la columna B
            $sheet->setCellValue('B' . $row, $fecha);

            // Obtener los datos para la fecha actual
            $datos_para_fecha = $datos;

            // Inicializar un contador para las filas internas
            $fila_interna = $row;

            // Iterar sobre los datos para la fecha actual
            foreach ($datos_para_fecha as $dato) {
                // Obtener los valores de los datos
                $sede_id = $dato["sede_id"];
                $valor = $dato["total_por_servicio_y_sede"];

                // Determinar las columnas según el valor de "sede_id"
                $columna = $dato["letter_excel"][0] ?? [];

                switch ($columna) {
                    case 'C':
                    case 'D':
                    case 'E':
                    case 'F':
                        // Si es sede 1, coloca los datos en las columnas C a F
                        if ($sede_id === '2') {
                            $sheet->setCellValue($columna . $fila_interna, $valor);
                        }
                        break;
                    case 'H':
                    case 'I':
                    case 'J':
                    case 'K':
                        // Si es sede 2, coloca los datos en las columnas H a K
                        if ($sede_id === '1') {
                            $sheet->setCellValue($columna . $fila_interna, $valor);
                        }
                        break;
                }
            }

            // Calcular totales para la fila actual
            $sheet->setCellValue('G' . $row, "=SUM(C${row}:F${fila_interna})");
            $sheet->setCellValue('L' . $row, "=SUM(H${row}:K${fila_interna})");

            // Calcular totales de las dos sedes
            $sheet->setCellValue('M' . $row, "=G${row}+L${fila_interna}");

            // Aplicar estilo a toda la fila
            $sheet->getStyle("B${row}:M${fila_interna}")->applyFromArray($style);

            // Avanzar a la siguiente fila
            $row = $fila_interna + 1;
        }

        // Configurar anchos de columna
        foreach (range('B', 'M') as $column) {
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
