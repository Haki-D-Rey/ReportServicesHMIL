<?php

namespace App\Controllers;

use App\Models\DB;
use App\services\ExcelSiserviReportService;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;
use Slim\Psr7\Stream;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

            // Get the raw HTTP request body
            // $body = file_get_contents('php://input');

            // // For example, you can decode JSON if the request body is JSON
            // $dataBody = json_decode($body, true);

            // // Obtener los parámetros de fecha del cuerpo de la solicitud
            // $fecha_inicio = $bodyParams['fecha_inicio'] ?? null;
            // $fecha_fin = $bodyParams['fecha_fin'] ?? null;

            // // Obtener el valor del parámetro tipo_busquedad
            // $queryParams = $request->getQueryParams();
            // $tipo_busquedad = $queryParams['tipo_busquedad'] ?? 1;

            // Obtener la fecha actual y el nombre del mes en inglés
            $month = date('F');
            $year = date('Y');
            // Traducir el nombre del mes al español
            $months = [
                'January' => 'Enero',
                'February' => 'Febrero',
                'March' => 'Marzo',
                'April' => 'Abril',
                'May' => 'Mayo',
                'June' => 'Junio',
                'July' => 'Julio',
                'August' => 'Agosto',
                'September' => 'Septiembre',
                'October' => 'Octubre',
                'November' => 'Noviembre',
                'December' => 'Diciembre'
            ];

            // Obtener el nombre del mes en español
            $current_month = $months[$month];
            // Crear una instancia de PhpSpreadsheet
            $spreadsheet = new Spreadsheet();
            $excelServicesSiservi = new ExcelSiserviReportService();

            $excelServicesSiservi->setDocumentProperties($spreadsheet);

            // Crear una nueva hoja de cálculo
            $sheetSI = $spreadsheet->getActiveSheet();
            $sheetSI->setTitle("REPORTE SISERVI - $current_month $year");
            $excelServicesSiservi->setHeaders($sheetSI);

            $data = $excelServicesSiservi->getData();

            // Definir el arreglo de mapeo de serv_id a letter_excel
            $mapeoServicios = [
                [
                    "serv_id" => "ALMUERZO",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["C"]],
                        ["sede_id" => 2, "letter" => ["H"]]
                    ]
                ],
                [
                    "serv_id" => "CENA",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["D"]],
                        ["sede_id" => 2, "letter" => ["I"]]
                    ]
                ],
                [
                    "serv_id" => "REFRACCION",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["F"]],
                        ["sede_id" => 2, "letter" => ["K"]]
                    ]
                ],
                [
                    "serv_id" => "DESAYUNO",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["E"]],
                        ["sede_id" => 2, "letter" => ["J"]]
                    ]
                ]
                // Puedes agregar más elementos según sea necesario
            ];

            $mapeoIndexado = $excelServicesSiservi->mapServices($mapeoServicios);

            // Definir un arreglo con todos los serv_id esperados y sus respectivas sedes
            $serviciosEsperados = [
                "ALMUERZO" => ["1", "2"],
                "CENA" => ["1", "2"],
                "REFRACCION" => ["1", "2"],
                "DESAYUNO" => ["1", "2"],
            ];

            $data = $excelServicesSiservi->restructureData($data, $serviciosEsperados, $mapeoIndexado);

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

            $excelServicesSiservi->formData($data, $styleArray, 4, $sheetSI);

            // Crear una nueva hoja de cálculo
            $newSheet = $spreadsheet->createSheet();
            $newSheet->setTitle("REPORTE DIETA $current_month $year");

            // Agregar datos a la nueva hoja de cálculo
            $newSheet->setCellValue('A1', 'Valor 1');
            $newSheet->setCellValue('B1', 'Valor 2');
            $newSheet->setCellValue('C1', 'Valor 3');

            // También puedes agregar datos iterando sobre un array
            $data = [
                ['Dato 1', 'Dato 2', 'Dato 3'],
                ['Dato 4', 'Dato 5', 'Dato 6'],
                // Agrega más filas de datos según sea necesario
            ];

            // Inicializar el contador de fila para los datos
            $row = 2;

            // Iterar sobre los datos y agregarlos a la hoja de cálculo
            foreach ($data as $rowData) {
                $col = 'A';
                foreach ($rowData as $value) {
                    $newSheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Guardar el archivo excel en el servidor
            $arrayFile = $excelServicesSiservi->saveFile($spreadsheet, "Reporte Servicios Alimentacios HMIL $year");

            $filename = $arrayFile["filename"];

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
