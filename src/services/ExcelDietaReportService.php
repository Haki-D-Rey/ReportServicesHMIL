<?php

namespace App\services;

use App\Controllers\ApiController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelDietaReportService
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
        $sheet->setCellValue('C2', 'TIPO SERVICIOS');
        // Fusionar celdas para SEDE GENERAL
        $sheet->mergeCells('C2:H2');
        // Establecer valor para SEDE GENERAL
        $sheet->setCellValue('C2', 'REPORTE DIETA TODAS LAS ÁREAS DE SERVICIOS');
        // Fusionar celdas para SEDE OFICIALES 
        $sheet->mergeCells('J2:J3');
        $sheet->setCellValue('J2', 'TOTAL');

        // Establecer encabezados
        $sheet->setCellValue('D3', 'DESAYUNO')
            ->setCellValue('E3', 'ALMUERZO')
            ->setCellValue('F3', 'CENA')
            ->setCellValue('G3', 'MERIENDA DE DESAYUNO')
            ->setCellValue('H3', 'MERIENDA DE ALMUERZO')
            ->setCellValue('I3', 'MERIENDA DE CENA');
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
                $mapeoIndexado[$servicio["servicio"]][$letter["sede_id"]] = $letter["letter"];
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
            $servicio = $item["servicio"];
            $tipo_servicio = $item["tipo_servicio"];

            // Verificar si la fecha ya está en el arreglo reestructurado
            if (!isset($reestructurado[$fecha])) {
                $reestructurado[$fecha] = [];
            }

            // Verificar si el tipo de servicio ya está en el arreglo reestructurado para esa fecha
            if (!isset($reestructurado[$fecha][$tipo_servicio])) {
                $reestructurado[$fecha][$tipo_servicio] = [];
            }

            // Verificar si el servicio esperado para esta sede está definido
            if (isset($serviciosEsperados[$servicio]) && in_array($sede_id, $serviciosEsperados[$servicio])) {
                // Si es así, agregar el item con letter_excel correspondiente si está definido
                $letter_excel = isset($mapeoServicios[$servicio][$sede_id]) ? $mapeoServicios[$servicio][$sede_id] : "";
                $reestructurado[$fecha][$tipo_servicio][] = [
                    "sede_id" => $sede_id,
                    "servicio" => $servicio,
                    "cantidad" => $item["cantidad"],
                    "letter_excel" => $letter_excel
                ];
            }
        }

        $tiposServiciosEsperados = [
            "Hospitalización General",
            "Hospitalización Privada"
        ];

        $reestructurado = $this->resetValue($reestructurado, $serviciosEsperados, $mapeoServicios, $tiposServiciosEsperados);
        ksort($reestructurado);
        return $reestructurado;
    }

    public function resetValue(
        array $reestructurado,
        array $serviciosEsperados,
        array $mapeoServicios,
        array $tiposServiciosEsperados
    ): array {
        // Iterar sobre cada combinación de servicio, sede, y tipo de servicio esperado
        foreach ($serviciosEsperados as $servicio => $sedes) {
            foreach ($sedes as $sede_id) {
                foreach ($tiposServiciosEsperados as $tipo_servicio_esperado) {
                    foreach ($reestructurado as $fecha => &$tipos_servicio) {

                        // Inicializar el tipo de servicio si no existe
                        if (!isset($tipos_servicio[$tipo_servicio_esperado])) {
                            $tipos_servicio[$tipo_servicio_esperado] = [];
                        }

                        // Verificar si ya existe el servicio en la sede para el tipo_servicio_esperado
                        $existe = array_filter($tipos_servicio[$tipo_servicio_esperado], function ($item) use ($sede_id, $servicio) {
                            return $item['sede_id'] === $sede_id && $item['servicio'] === $servicio;
                        });

                        // Si no existe, agregar con valores por defecto
                        if (empty($existe)) {
                            $letter_excel = $mapeoServicios[$servicio][$sede_id] ?? "";
                            $tipos_servicio[$tipo_servicio_esperado][] = [
                                "sede_id" => $sede_id,
                                "servicio" => $servicio,
                                "cantidad" => "0.00", // Valor por defecto
                                "letter_excel" => $letter_excel
                            ];
                        }
                    }
                }
            }
        }

        return $reestructurado;
    }



    public function formData(array $data, array $style, int $row, Worksheet $sheet): void
    {

        $sheet->getStyle("B2:J2")->applyFromArray($style);
        $sheet->getStyle("B3:J3")->applyFromArray($style);

        foreach ($data as $fecha => $datos) {
            // Obtener los datos para la fecha actual
            $datos_para_fecha = $datos;

            // Inicializar un contador para las filas internas
            $fila_interna = $row;
            $cantidad_tipo_servicios = count($datos_para_fecha);

            foreach ($datos_para_fecha as $tipo_servicio => $item_tipo_servicios) {

                $sheet->setCellValue('C' . $fila_interna, mb_strtoupper($tipo_servicio, 'UTF-8'));
                $datos_para_tipo_servicios = $item_tipo_servicios;
                // Iterar sobre los datos para la fecha actual
                foreach ($datos_para_tipo_servicios as $dato) {
                    // Obtener los valores de los datos
                    $sede_id = $dato["sede_id"];
                    $valor = $dato["cantidad"];

                    // Determinar las columnas según el valor de "sede_id"
                    $columna = $dato["letter_excel"][0] ?? [];

                    switch ($columna) {
                        case 'D':
                        case 'E':
                        case 'F':
                        case 'G':
                        case 'H':
                        case 'I':
                            // Si es sede 2, coloca los datos en las columnas H a K
                            if ($sede_id === '0') {
                                $sheet->setCellValue($columna . $fila_interna, $valor);
                            }
                            break;
                    }
                }
                // Calcular totales de las dos sedes
                // $sheet->setCellValue('I' .$row, "=SUMA(C${row}:H${row})");
                $sheet->setCellValue('J' . $fila_interna, "=(D${fila_interna}+E${fila_interna}+F${fila_interna}+G${fila_interna}+H${fila_interna}+I${fila_interna})");

                // Aplicar estilo a toda la fila
                $sheet->getStyle("B${fila_interna}:J${fila_interna}")->applyFromArray($style);

                $fila_interna++;
            }
            $rango_inicio = $fila_interna - $cantidad_tipo_servicios;
            $rango_final = $fila_interna - 1;
            $sheet->mergeCells("B{$rango_inicio}:B{$rango_final}");
            $sheet->setCellValue('B' . $rango_inicio, $fecha);
            $row = $fila_interna;
        }

        // Configurar anchos de columna
        foreach (range('B', 'J') as $column) {
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
