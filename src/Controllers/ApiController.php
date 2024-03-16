<?php

namespace App\Controllers;

use App\Models\DB;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;
use Slim\Psr7\Stream;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ApiController
{

    public function index()
    {
        phpinfo();
    }

    public function getAll(Request $request, Response $response)
    {

        // Obtener los datos del cuerpo de la solicitud (FormData)
        $bodyParams = $request->getParsedBody();

        // Obtener los parámetros de fecha del cuerpo de la solicitud
        $fecha_inicio = $bodyParams['fecha_inicio'] ?? null;
        $fecha_fin = $bodyParams['fecha_fin'] ?? null;

        // Obtener el valor del parámetro tipo_busquedad
        $queryParams = $request->getQueryParams();
        $tipo_busquedad = $queryParams['tipo_busquedad'] ?? 1;
        $condicional = "SELECT
                            v.sede_id,
                            v.serv_id,
                            DATE(v.vc_emision_feho) AS fecha,
                            SUM(v.vc_total) AS total_del_dia
                        FROM dmona.ventas_cab v
                        WHERE " . ($tipo_busquedad === 1 ? "DATE(v.vc_emision_feho) >= $fecha_inicio AND DATE(v.vc_emision_feho) <= $fecha_fin" : "DATE(v.vc_emision_feho) = $fecha_inicio") . "
                        GROUP BY v.sede_id, v.serv_id, DATE(v.vc_emision_feho)";
        $sql = "SELECT
                    sede_id,
                    serv_id,
                    fecha,
                    SUM(total_del_dia) AS total_por_servicio_y_sede
                FROM (
                    $condicional
                ) AS subconsulta
                GROUP BY sede_id, serv_id, fecha;";

        try {
            $db = new DB();
            $conn = $db->connect();
            $stmt = $conn->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            $response->getBody()->write(json_encode($customers));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(200);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(500);
        }
    }


    public function getExcel(Request $request, Response $response)
    {
        try {
            // Crear una instancia de PhpSpreadsheet
            $spreadsheet = new Spreadsheet();

            // Establecer propiedades del documento
            $spreadsheet->getProperties()->setCreator("Tu nombre")
                ->setLastModifiedBy("Tu nombre")
                ->setTitle("Reporte de ventas")
                ->setSubject("Reporte de ventas")
                ->setDescription("Este archivo contiene el reporte de ventas.")
                ->setKeywords("ventas reporte excel")
                ->setCategory("Reporte");

            // Obtener la hoja activa
            $sheet = $spreadsheet->getActiveSheet();

            //Fusionar Celda Fecha
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

            // Establecer encabezados
            $sheet->setCellValue('C3', 'ALMUERZO')
                ->setCellValue('D3', 'CENA')
                ->setCellValue('E3', 'DESAYUNO')
                ->setCellValue('F3', 'REFRACCION')
                ->setCellValue('G2', 'TOTAL GENERAL')
                ->setCellValue('H3', 'ALMUERZO')
                ->setCellValue('I3', 'CENA')
                ->setCellValue('J3', 'DESAYUNO')
                ->setCellValue('K3', 'REFRACCION')
                ->setCellValue('L2', 'TOTAL OFICIALES')
                ->setCellValue('M3', 'TOTAL');

                $data = [
                    [
                        "sede_id" => "1",
                        "serv_id" => "ALMUERZO",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "211.00"
                    ],
                    [
                        "sede_id" => "1",
                        "serv_id" => "CENA",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "30.00"
                    ],
                    [
                        "sede_id" => "1",
                        "serv_id" => "REFRACCION",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "3.00"
                    ],
                    [
                        "sede_id" => "2",
                        "serv_id" => "ALMUERZO",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "687.00"
                    ],
                    [
                        "sede_id" => "2",
                        "serv_id" => "CENA",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "207.00"
                    ],
                    [
                        "sede_id" => "2",
                        "serv_id" => "DESAYUNO",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "140.00"
                    ],
                    [
                        "sede_id" => "2",
                        "serv_id" => "REFRACCION",
                        "fecha" => "2024-02-05",
                        "total_por_servicio_y_sede" => "54.00"
                    ]
                ];                

            $columnas_encabezados = range('B', 'M');
            // Configurar el estilo de la tabla
            $styleArray = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];

            $sheet->getStyle("B2:M2")->applyFromArray($styleArray);
            $sheet->getStyle("B3:M3")->applyFromArray($styleArray);

            $sede_columnas = [
                '1' => ['C', 'D', 'E', 'F'],
                '2' => ['H', 'I', 'J', 'K']
            ];

            $row = 4;
            foreach ($data as $row_data) {

                // Obtener el valor del dato actual
                $valor = $row_data['total_por_servicio_y_sede'];

                // Determinar las columnas según el valor de "sede_id"
                $columnas = $sede_columnas[$row_data['sede_id']] ?? [];
                foreach ($columnas_encabezados as $columna) {
                    // Obtener el valor del dato actual
                    $valor = $row_data['total_por_servicio_y_sede'];
                    $fecha = $row_data['fecha'];

                    // Condiciona la colocación de los datos según el valor de "sede_id"
                    if ($row_data['sede_id'] === '1') {
                        // Si es sede 1, coloca los datos en las columnas C a F
                        switch ($columna) {
                            case 'B':
                                $sheet->setCellValue($columna . $row, $fecha);
                                break;
                            case 'C':
                            case 'D':
                            case 'E':
                            case 'F':
                                $sheet->setCellValue($columna . $row, $valor);
                                break;
                            default:
                                // Para otras columnas, dejar vacío
                                $sheet->setCellValue($columna . $row, '');
                        }
                    } elseif ($row_data['sede_id'] === '2') {
                        // Si es sede 2, coloca los datos en las columnas H a K
                        switch ($columna) {
                            case 'B':
                                $sheet->setCellValue($columna . $row, $fecha);
                                break;
                            case 'H':
                            case 'I':
                            case 'J':
                            case 'K':
                                $sheet->setCellValue($columna . $row, $valor);
                                break;
                            default:
                                // Para otras columnas, dejar vacío
                                $sheet->setCellValue($columna . $row, '');
                        }
                    }

                    // Calcular totales
                    if ($columna === 'G') {
                        $sheet->setCellValue('G' . $row, "=SUM(C${row}:F${row})");
                    }
                    if ($columna === 'L') {
                        $sheet->setCellValue('L' . $row, "=SUM(H${row}:K${row})");
                    }
                    $sheet->getStyle("B${row}:M${row}")->applyFromArray($styleArray);
                }
                $row++; // Avanzar a la siguiente fila
            }


            // Agregar datos de ejemplo
            $row = 4; // Comenzar desde la fila 4
            // foreach ($data as $row_data) {
            //     foreach ($columnas_encabezados as $columna) {
            //         $sheet->setCellValue($columna . $row, array_shift($row_data)); // Establecer el valor de la celda correspondiente
            //         // Calcular totales
            //         if ($columna === 'G') {
            //             $sheet->setCellValue('G' . $row, "=SUM(C${row}:F${row})");
            //         }
            //         if ($columna === 'L') {
            //             $sheet->setCellValue('L' . $row, "=SUM(H${row}:K${row})");
            //         }
            //         $sheet->getStyle("B${row}:M${row}")->applyFromArray($styleArray);
            //     }
            //     $row++; // Avanzar a la siguiente fila
            // }

            // Configurar anchos de columna
            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Guardar el archivo
            $writer = new Xlsx($spreadsheet);
            $writer->save('ventas_reporte.xlsx');

            // Guardar el archivo en el servidor
            $filename = 'ventas_reporte.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($filename);

            // Configurar la respuesta para descargar el archivo
            $fileSize = filesize($filename);

            $response = $response->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->withHeader('Content-Disposition', 'attachment;filename="' . basename($filename) . '"')
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public')
                ->withHeader('Content-Length', $fileSize);

            // Leer el archivo y enviarlo como respuesta
            $file = fopen($filename, 'rb');
            $stream = new Stream($file);
            $response = $response->withBody($stream);

            // Eliminar el archivo después de enviarlo
            unlink($filename);

            return $response;
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('content-type', 'application/json')
                ->withStatus(500);
        }
    }
}
