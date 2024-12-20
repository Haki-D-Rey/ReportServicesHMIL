<?php

namespace App\Controllers;

use App\Models\DB;
use App\services\ExcelDietaReportService;
use App\services\ExcelReportEventsService;
use App\services\ExcelSiserviReportService;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;
use Slim\Psr7\Stream;
use App\Helpers\Utils;
use App\services\ExcelSiserviDatosClientesReportService;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ApiController
{
    protected PHPMailer $mailServer;
    protected $databases = [
        'SISERVI' => [
            'driver' => 'pgsql',
            'host' => '10.0.30.147',
            'user' => 'postgres',
            'port' => '5432',
            'pass' => '&ecurity23',
            'dbname' => 'siservi_catering_local'
        ],
        'DIETA' => [
            'driver' => 'sqlsrv',
            'host' => 'Dieta',
            'user' => 'sa',
            'port' => '1433',
            'pass' => 'PA$$W0RD',
            'dbname' => 'Dieta'
        ],
        'EVENTOS' => [
            'driver' => 'mysql',
            'host' => 'c98055.sgvps.net',
            'user' => 'udeq5kxktab81',
            'port' => '3306',
            'pass' => 'clmjsfgcrt5m',
            'dbname' => 'db2gdg4nfxpgyk'
        ],
        'RRHH_DEV' => [
            'driver' => 'sqlsrv',
            'host' => 'RRHH_DEV',
            'port' => '1433',
            'user' => 'sa',
            'pass' => '2r>6C8A>tKcq',
            'dbname' => 'RRHH_DEV'
        ],
        'IVSM_DEV' => [
            'driver' => 'mysql',
            'host' => '10.0.30.185',
            'user' => 'developer',
            'pass' => 'T41g<l6rnF7J',
            'dbname' => 'IVSM_DEV'
        ],
        'IVSM_PROD' => [
            'driver' => 'mysql',
            'host' => '10.0.30.146',
            'port' => '3308',
            'user' => 'developer',
            'pass' => 'T41g<l6rnF7J',
            'dbname' => 'ivsm'
        ],
        'RRHH_PROD' => [
            'driver' => 'sqlsrv',
            'host' => 'RRHH',
            'user' => 'sa',
            'pass' => 'P@$$W0RD',
            'dbname' => 'Dieta'
        ],
    ];

    public function index()
    {
        $this->getConnection();
        phpinfo();
    }

    public function getConnection()
    {
        $multiDB = new DB($this->databases);

        try {

            var_dump($multiDB->connections);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
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

        $arryaParams = [
            "fecha_inicio" => '2024-01-19',
            "fecha_fin" => '2024-01-19',
            "tipo_busquedad" => 1
        ];

        try {

            $customers = $this->getSellSiServi($arryaParams);

            $lotes = array_chunk($customers, 10);

            // JSON final
            $json_final = '';

            // Iterar sobre cada lote y serializarlo por separado
            foreach ($lotes as $lote) {
                // Array de arrays para el lote actual
                $arrayArrays = array();

                // Convertir cada objeto stdClass a un array asociativo y agregarlo al array de arrays
                foreach ($lote as $objeto) {
                    $array = (array) $objeto;
                    $arrayArrays[] = $array;
                }

                // Serializar el array de arrays
                $json = json_encode($arrayArrays);

                // Agregar una coma si ya hay datos en el JSON final
                if ($json_final !== '') {
                    $json_final .= ',';
                }

                // Concatenar el JSON del lote actual al JSON final
                $json_final .= $json;
            }

            // Envolver el JSON final entre corchetes para crear un JSON válido de múltiples objetos
            $json_final = '[' . $json_final . ']';

            $response->getBody()->write($json_final);
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


    public function getExcelReporteServiciosAlimentacion(Request $request, Response $response)
    {
        try {

            //Get the raw HTTP request body
            $body = file_get_contents('php://input');

            // For example, you can decode JSON if the request body is JSON
            $dataBody = json_decode($body, true);

            // Obtener la fecha actual
            $hoy = date('Y-m-d');

            // Obtener los parámetros de fecha del cuerpo de la solicitud
            $fecha_inicio = isset($dataBody['fecha_inicio']) && !empty($dataBody['fecha_inicio']) ? $dataBody['fecha_inicio'] : date('Y-m-d', strtotime($hoy . ' - 1 day'));
            $fecha_fin = isset($dataBody['fecha_fin']) && !empty($dataBody['fecha_fin']) ? $dataBody['fecha_fin'] : date('Y-m-d', strtotime($hoy . ' - 1 day'));

            // Obtener parametros de correo
            $parametrosCorreo = [
                'fromEmail' => $dataBody['fromEmail'] ?? null,
                'fromName' => $dataBody['fromName'] ?? null,
                'destinatary' => $dataBody['destinatary'] ?? null,
                'subject' => $dataBody['subject'] ?? null,
                'body' => $dataBody['body'] ?? null
            ];

            // Obtener el valor del parámetro tipo_busquedad
            $queryParams = $request->getQueryParams();
            $tipo_busquedad = $queryParams['tipo_busquedad'] ?? 1;

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

            $arryaParams = [
                "fecha_inicio" => $fecha_inicio,
                "fecha_fin" => $fecha_fin,
                "tipo_busquedad" => $tipo_busquedad
            ];

            // Obtener el nombre del mes en español
            $current_month = $months[$month];
            // Crear una instancia de PhpSpreadsheet
            $spreadsheet = new Spreadsheet();
            $excelServicesDieta = new ExcelDietaReportService();
            $excelServicesSiservi = new ExcelSiserviReportService();

            $excelServicesSiservi->setDocumentProperties($spreadsheet);

            // Crear una nueva hoja de cálculo
            $sheetSI = $spreadsheet->getActiveSheet();
            $sheetSI->setTitle("SISERVI - $current_month $year");
            $excelServicesSiservi->setHeaders($sheetSI);

            $data = $this->getSellSiServi($arryaParams);
            $data = $this->restructuredArray($data);
            // Definir el arreglo de mapeo de serv_id a letter_excel
            $mapeoServicios = [
                [
                    "serv_id" => "ALMUERZO",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["I"]],
                        ["sede_id" => 2, "letter" => ["D"]]
                    ]
                ],
                [
                    "serv_id" => "CENA",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["J"]],
                        ["sede_id" => 2, "letter" => ["E"]]
                    ]
                ],
                [
                    "serv_id" => "REFRACCION",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["K"]],
                        ["sede_id" => 2, "letter" => ["F"]]
                    ]
                ],
                [
                    "serv_id" => "DESAYUNO",
                    "letter_excel" => [
                        ["sede_id" => 1, "letter" => ["H"]],
                        ["sede_id" => 2, "letter" => ["C"]]
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
            $sheetDieta = $spreadsheet->createSheet();
            $sheetDieta->setTitle("DIETA $current_month $year");

            $excelServicesDieta->setDocumentProperties($spreadsheet);

            $excelServicesDieta->setHeaders($sheetDieta);

            $data = $this->getSellDietaReport($arryaParams); //$excelServicesDieta->getData();
            $data = $this->restructuredArray($data);
            // Definir el arreglo de mapeo de serv_id a letter_excel
            $mapeoServicios = [
                [
                    "servicio" => "DESAYUNO",
                    "letter_excel" => [
                        ["sede_id" => 0, "letter" => ["D"]],
                    ]
                ],
                [
                    "servicio" => "ALMUERZO",
                    "letter_excel" => [
                        ["sede_id" => 0, "", "letter" => ["E"]],
                    ]
                ],
                [
                    "servicio" => "CENA",
                    "letter_excel" => [
                        ["sede_id" => 0, "letter" => ["F"]],
                    ]
                ],
                [
                    "servicio" => "MERIENDA PARA DESAYUNO",
                    "letter_excel" => [
                        ["sede_id" => 0, "letter" => ["G"]],
                    ]
                ],
                [
                    "servicio" => "MERIENDA PARA ALMUERZO",
                    "letter_excel" => [
                        ["sede_id" => 0, "letter" => ["H"]],
                    ]
                ],
                [
                    "servicio" => "MERIENDA PARA CENA",
                    "letter_excel" => [
                        ["sede_id" => 0, "letter" => ["I"]],
                    ]
                ],
                // Puedes agregar más elementos según sea necesario
            ];

            $mapeoIndexado = $excelServicesDieta->mapServices($mapeoServicios);


            // Definir un arreglo con todos los serv_id esperados y sus respectivas sedes
            $serviciosEsperados = [
                "ALMUERZO" => ["0"],
                "CENA" => ["0"],
                "DESAYUNO" => ["0"],
                "MERIENDA PARA ALMUERZO" => ["0"],
                "MERIENDA PARA CENA" => ["0"],
                "MERIENDA PARA DESAYUNO" => ["0"],
            ];

            $data = $excelServicesDieta->restructureData($data, $serviciosEsperados, $mapeoIndexado);

            $excelServicesDieta->formData($data, $styleArray, 4, $sheetDieta);

            // Guardar el archivo excel en el servidor
            $arrayFile = $excelServicesDieta->saveFile($spreadsheet, "Reporte Servicios Alimentacios HMIL $year");

            $filename = $arrayFile["filename"];
            $mailer = new EmailController();

            // Configurar el correo electrónico
            $this->mailServer = $mailer->sendEmail($parametrosCorreo);
            // Adjuntar el archivo Excel
            $this->mailServer->addAttachment($filename, basename($filename));
            // Enviar el correo electrónico
            $this->mailServer->send();

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


    public function getExcelReportInscripcionesEvent(Request $request, Response $response)
    {
        try {

            //Get the raw HTTP request body
            $body = file_get_contents('php://input');

            // For example, you can decode JSON if the request body is JSON
            $dataBody = json_decode($body, true);

            // Obtener la fecha actual
            $hoy = date('Y-m-d');

            // Obtener los parámetros de fecha del cuerpo de la solicitud
            $fecha = isset($dataBody['fecha']) && !empty($dataBody['fecha']) ? $dataBody['fecha'] : date('Y-m-d', strtotime($hoy . '0 day'));
            // Obtener parametros de correo
            $parametrosCorreo = [
                'fromEmail' => $dataBody['fromEmail'] ?? null,
                'fromName' => $dataBody['fromName'] ?? null,
                'destinatary' => $dataBody['destinatary'] ?? null,
                'subject' => $dataBody['subject'] ?? "Reporte Diario de Personas Inscritas XXI Congreso y Precongreso Cientifíco Médico",
                'body' => $dataBody['body'] ?? "Se detalla La cantidad de personas inscritas para el evento XXI Congreso y Precongreso Cientifíco Médico, para llevar un control del conteo diario con corte a las 15hrs. Muchas Gracias"
            ];

            // Obtener el valor del parámetro tipo_busquedad
            $queryParams = $request->getQueryParams();
            $tipo_busquedad = $queryParams['tipo_busquedad'] ?? 1;

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

            $arryaParams = [
                "fecha" => $fecha,
                "tipo_busquedad" => $tipo_busquedad
            ];

            $current_month = $months[$month];
            // Crear una instancia de PhpSpreadsheet
            $spreadsheet = new Spreadsheet();
            $excelServicesRepoertEvents = new ExcelReportEventsService();

            // Crear una nueva hoja de cálculo
            $sheetReportEvents = $spreadsheet->getActiveSheet();
            $sheetReportEvents->setTitle("REPORTE $current_month $year");

            $excelServicesRepoertEvents->setDocumentProperties($spreadsheet);

            $excelServicesRepoertEvents->setHeaders($sheetReportEvents);

            $data = $excelServicesRepoertEvents->getData($arryaParams);

            $datos = $data["Datos"];
            $datos_instituciones = $data["Datos_Instituciones"];
            $excelServicesRepoertEvents->formData($datos, $sheetReportEvents, 4);


            // Crear una nueva hoja de cálculo
            $sheetReportEventsInstitutions = $spreadsheet->createSheet();
            $sheetReportEventsInstitutions->setTitle("REPORTE INSTITUCIONES");

            $excelServicesRepoertEvents->setHeadersInstitutions($sheetReportEventsInstitutions);

            $excelServicesRepoertEvents->formDataInstituciones($datos_instituciones, $sheetReportEventsInstitutions, 4);

            $arrayFile = $excelServicesRepoertEvents->saveFile($spreadsheet, "Reporte Inscripciones Eventos - $current_month $year");

            $filename = $arrayFile["filename"];
            $mailer = new EmailController();

            // Configurar el correo electrónico
            $this->mailServer = $mailer->sendEmail($parametrosCorreo);
            // Adjuntar el archivo Excel
            $this->mailServer->addAttachment($filename, basename($filename));
            // Enviar el correo electrónico
            $this->mailServer->send();

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

    public function getExcelReporteServiciosAlimentacionDatosClientes(Request $request, Response $response)
    {
        try {
            //Get the raw HTTP request body
            $body = file_get_contents('php://input');
            // For example, you can decode JSON if the request body is JSON
            $dataBody = json_decode($body, true);
            // Obtener la fecha actual
            $hoy = date('Y-m-d');
            // Obtener los parámetros de fecha del cuerpo de la solicitud
            $fecha_inicio = isset($dataBody['fecha_inicio']) && !empty($dataBody['fecha_inicio']) ? $dataBody['fecha_inicio'] : date('Y-m-d', strtotime($hoy . ' 0 day'));
            $fecha_fin = isset($dataBody['fecha_fin']) && !empty($dataBody['fecha_fin']) ? $dataBody['fecha_fin'] : date('Y-m-d', strtotime($hoy . ' 0 day'));
            $lista_servicios =  isset($dataBody['lista_servicios']) && !empty($dataBody['lista_servicios']) ? $dataBody['lista_servicios'] : ["ALMUERZO"];
            // Obtener parametros de correo
            $parametrosCorreo = [
                'fromEmail' => $dataBody['fromEmail'] ?? null,
                'fromName' => $dataBody['fromName'] ?? null,
                'destinatary' => $dataBody['destinatary'] ?? null,
                'subject' => $dataBody['subject'] ?? null,
                'body' => $dataBody['body'] ?? null
            ];
            // Obtener el valor del parámetro tipo_busquedad
            $queryParams = $request->getQueryParams();
            $tipo_busquedad = $queryParams['tipo_busquedad'] ?? 1;
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

            $arryaParams = [
                "fecha_inicio" => $fecha_inicio,
                "fecha_fin" => $fecha_fin,
                "tipo_busquedad" => $tipo_busquedad,
                "lista_servicios" => $lista_servicios
            ];
            // Obtener el nombre del mes en español
            $current_month = $months[$month];
            // Crear una instancia de PhpSpreadsheet
            $spreadsheet = new Spreadsheet();
            $excelServicesDieta = new ExcelDietaReportService();
            $excelServicesSiservi = new ExcelSiserviDatosClientesReportService();
            $excelServicesSiservi->setDocumentProperties($spreadsheet);
            // Crear una nueva hoja de cálculo
            $sheetSI = $spreadsheet->getActiveSheet();
            $sheetSI->setTitle("REPORTE SISERVI - $current_month $year");
            $excelServicesSiservi->setHeaders($sheetSI);
            $data = $excelServicesSiservi->getData($arryaParams);
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
            // Guardar el archivo excel en el servidor
            $arrayFile = $excelServicesSiservi->saveFile($spreadsheet, "Reporte Servicios Alimentacios HMIL $year");
            $filename = $arrayFile["filename"];
            $mailer = new EmailController();
            // Configurar el correo electrónico
            $this->mailServer = $mailer->sendEmail($parametrosCorreo);
            // Adjuntar el archivo Excel
            $this->mailServer->addAttachment($filename, basename($filename));
            // Enviar el correo electrónico
            $this->mailServer->send();
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

    public function getBigDataInsertMarks(Request $request, Response $response)
    {
        $json_final = [];

        try {
            // Inicia el contador para todo el proceso
            $startTime = microtime(true);
            $json_final['overall_start_time'] = $startTime;

            $page = (int) $request->getQueryParams()['page'] ?? 1;
            $pageSize = (int) $request->getQueryParams()['pageSize'] ?? 10;

            //Get the raw HTTP request body
            $body = file_get_contents('php://input');
            $dataBody = json_decode($body, true);

            // Obtener parametros de correo
            $parametrosCorreo = [
                'fromEmail' => $dataBody['fromEmail'] ?? null,
                'fromName' => $dataBody['fromName'] ?? null,
                'destinatary' => $dataBody['destinatary'] ?? null,
                'subject' => $dataBody['subject'] ?? null,
                'body' => ""
            ];


            $dataStartTime = microtime(true);
            $DatosMarcas = $this->getMarksClockHivision('', '');
            $dataEndTime = microtime(true);
            $json_final['data_fetch_time'] = round($dataEndTime - $dataStartTime, 4);

            // Transformación de datos
            $transformStartTime = microtime(true);
            $Template = [
                "DeviceSerial" => "DeviceSerial",
                "DeviceName" => "DeviceName",
                "Data" => [
                    "PersonID" => "PersonID",
                    "AuthDateTime" => "AuthDateTime",
                    "AuthDate" => "AuthDate",
                    "Authtime" => "Authtime",
                    "Direction" => "Direction"
                ]
            ];


            $devices = [
                ["DeviceSerial" => "DS-K1T8003MF20220523V010400ENK89383023", "DeviceName" => "EDIFICIO D"],
                ["DeviceSerial" => "DS-K1T8003MF20220523V010400ENK89382766", "DeviceName" => "POLICLINICA TIPITAPA"],
                ["DeviceSerial" => "DS-K1T320MFX20221110V030500ENL34922859", "DeviceName" => "UAAE FISIOTERAPIA"],
                ["DeviceSerial" => "DS-K1T320MFX20221110V030500ENL34922754", "DeviceName" => "UAAE LOGOPEDIA"],
                ["DeviceSerial" => "DS-K1T320MFX20221110V030500ENL34922814", "DeviceName" => "UAAE REUMATOLOGIA"],
                ["DeviceSerial" => "DS-K1T671MF20230330V030230ENAD1514630", "DeviceName" => "EDIFICIO 1C"],
                ["DeviceSerial" => "DS-K1T8003EF20210407V010330ENF77487337", "DeviceName" => "EDIFICIO A"],
                ["DeviceSerial" => "DS-K1T671MF20210406V030230ENG12677159", "DeviceName" => "EDIFICIO 1B"]
            ];

            $deviceMap = [];
            foreach ($devices as $device) {
                $deviceMap[$device['DeviceName']] = $device['DeviceSerial'];
            }

            $utils = new Utils();
            $mutatedArray = $utils->transformArray($DatosMarcas, $Template, true, 'DeviceSerial');
            $transformEndTime = microtime(true);
            $json_final['transform_time'] = round($transformEndTime - $transformStartTime, 4);

            foreach ($mutatedArray as &$itemData) {
                $itemData['Data'] = $this->filterDataByDirection1($itemData['Data'], ['DS-K1T8003EF20210407V010330ENF77487337']);
            }
            unset($itemData);

            foreach ($mutatedArray as &$item) {
                if (empty($item['DeviceSerial'])) {
                    if (isset($deviceMap[$item['DeviceName']])) {
                        $item['DeviceSerial'] = $deviceMap[$item['DeviceName']];
                    }
                }
            }
            unset($item);
            $deviceSerials = array_column($mutatedArray, 'DeviceSerial');

            $db = new DB($this->databases);
            $connD = $db->getConnection('RRHH_PROD');

            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $today = date('Y-m-d');

            $validationStartTime = microtime(true);
            $sqlRRHH = "SELECT ID_EMPLEADO, HORA_MARCA, TIPO_MARCA, IDRELOJ
                        FROM RRHH.dbo.[MAESTRO DE MARCAS]
                        WHERE FECHA_MARCA BETWEEN :yesterday AND :today";

            $stmtRRHH = $connD->prepare($sqlRRHH);
            $stmtRRHH->execute([
                ':yesterday' => $yesterday,
                ':today'     => $today
            ]);

            $existingRecords = $stmtRRHH->fetchAll(PDO::FETCH_ASSOC);
            $existingMap = [];
            foreach ($existingRecords as $record) {
                $key = $record['ID_EMPLEADO'] . '_' . $record['HORA_MARCA'] . '_' . $record['TIPO_MARCA'] . '_' . $record['IDRELOJ'];
                $existingMap[$key] = true;
            }

            $validationEndTime = microtime(true);
            $json_final['validation_time'] = round($validationEndTime - $validationStartTime, 4);

            $filterStartTime = microtime(true);

            $relojIds = $this->getRelojIdsBySerials($deviceSerials);

            $chunks = array_chunk($mutatedArray, 1000);
            $filteredData = [];

            $filteredChunks = array_map(function ($chunks) use ($existingMap, $relojIds) {
                $tempFiltered = [];
                foreach ($chunks as $deviceData) {
                    $DeviceSerial = $deviceData['DeviceSerial'];
                    $idReloj = $relojIds[$DeviceSerial] ?? 0;

                    foreach ($deviceData['Data'] as $registro) {
                        // $CodigoEmpleado = '00000' . $registro['PersonID'];
                        $CodigoEmpleado = str_pad($registro['PersonID'], 10, '0', STR_PAD_LEFT);
                        $key = $CodigoEmpleado . '_' . $registro['AuthDateTime'] . '_' . $registro['Direction'] . '_' . $idReloj;
                        if (!isset($existingMap[$key])) {
                            $tempFiltered[] = [
                                'IDEMPRESA' => 1,
                                'IDRELOJ' => $idReloj,
                                'ID_EMPLEADO' => $CodigoEmpleado,
                                'HORA_MARCA' => $registro['AuthDateTime'],
                                'FECHA_MARCA' => $registro['AuthDate'],
                                'FECHA_CARGA' => date('Y-m-d H:i:s'),
                                'TIPO_MARCA' => $registro['Direction']
                            ];
                        }
                    }
                }
                return $tempFiltered;
            }, $chunks);

            foreach ($filteredChunks as $chunk) {
                $filteredData = array_merge($filteredData, $chunk);
            }

            $filterEndTime = microtime(true);
            $json_final['filter_time'] = round($filterEndTime - $filterStartTime, 4);

            $insertStartTime = microtime(true);

            if (!empty($filteredData)) {

                $chunkedData = array_chunk($filteredData, 1000);
                $connD->beginTransaction();

                try {
                    $rowsAffected = 0;
                    foreach ($chunkedData as $chunk) {

                        $sqlInsert = "INSERT INTO RRHH.dbo.[MAESTRO DE MARCAS] 
                        (IDEMPRESA, IDRELOJ, ID_EMPLEADO, HORA_MARCA, FECHA_MARCA, FECHA_CARGA, ARCHIVO, NUMERO, PROGRAMADO, OBSERVACION, TIPO_MARCA) 
                        VALUES ";

                        $insertValues = [];
                        foreach ($chunk as $data) {
                            $insertValues[] = "({$data['IDEMPRESA']}, {$data['IDRELOJ']}, '{$data['ID_EMPLEADO']}', '{$data['HORA_MARCA']}', 
                            '{$data['FECHA_MARCA']}', '{$data['FECHA_CARGA']}', '.', '.', 0, '.', '{$data['TIPO_MARCA']}')";
                        }
                        $sqlInsert .= implode(', ', $insertValues);
                        $rowsAffected = $connD->exec($sqlInsert);
                    }

                    $connD->commit();

                    $json_final['message'] = "$rowsAffected registros insertados correctamente.";
                    $json_final['total_insert'] = $rowsAffected;
                } catch (Exception $e) {
                    $connD->rollBack();
                    throw new Exception("Error al insertar registros: " . $e->getMessage());
                }
            } else {
                $json_final['message'] = "No hay registros nuevos para insertar.";
            }

            $insertEndTime = microtime(true);
            $json_final['insert_time'] = round($insertEndTime - $insertStartTime, 4);

            $endTime = microtime(true);
            $json_final['total_time_seconds'] = round($endTime - $startTime, 4);
        } catch (Exception $e) {
            $json_final['error'] = [
                'message' => "Ocurrió un error durante el proceso: " . $e->getMessage(),
                'code' => $e->getCode()
            ];
            return $response->withHeader('content-type', 'application/json')
                ->withStatus(500)
                ->getBody()->write(json_encode($json_final));
        }

        $mailer = new EmailController();
        $parametrosCorreo['body'] = "Se ingresaron del proceso de migración " . $json_final['message'] . " registros insertados correctamente, con un tiempo de inserción de " . $json_final['total_time_seconds'] . " segundos.";
        $this->mailServer = $mailer->sendEmail($parametrosCorreo);
        $this->mailServer->send();

        $response->getBody()->write(json_encode($json_final));
        return $response
            ->withHeader('content-type', 'application/json')
            ->withStatus(200);
    }

    public function getSellSiServi(array $arrayParams): array
    {
        $tipo_busquedad = $arrayParams['tipo_busquedad'];
        $fecha_inicio = $arrayParams['fecha_inicio'];
        $fecha_fin = $arrayParams['fecha_fin'];

        $condicional =
            "SELECT
                v.sede_id,
                v.serv_id,
                DATE(v.vc_emision_feho) AS fecha,
                SUM(v.vc_total) AS total_del_dia
        FROM dmona.ventas_cab v
        WHERE v.vc_anulado <> 1 AND " . ($tipo_busquedad == 1 ? "DATE(v.vc_emision_feho) >= '$fecha_inicio' AND DATE(v.vc_emision_feho) <= '$fecha_fin'" : "DATE(v.vc_emision_feho) = '$fecha_inicio'") . "
        GROUP BY v.sede_id, v.serv_id, DATE(v.vc_emision_feho)";

        $sql =
            "SELECT
                sede_id,
                serv_id,
                fecha,
                SUM(total_del_dia) AS total_por_servicio_y_sede
        FROM (
                $condicional
             ) AS subconsulta
        GROUP BY sede_id, serv_id, fecha;";

        try {
            $db = new DB($this->databases);
            $conn = $db->getConnection('SISERVI');
            $stmt = $conn->query($sql);
            $siserviReport = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            return $siserviReport;
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }


    public function getSellDietaReport(array $arrayParams): array
    {
        $tipo_busquedad = $arrayParams['tipo_busquedad'];
        $fecha_inicio = date("d/m/Y", strtotime($arrayParams['fecha_inicio']));
        $fecha_fin = date("d/m/Y", strtotime($arrayParams['fecha_fin']));

        try {
            $db = new DB($this->databases);
            $connD = $db->getConnection('DIETA');
            $sql =
                "SELECT 
                    CONVERT(VARCHAR, p.fecha, 120) AS fecha,
                    t.nombre as servicio,
                    CASE 
                        WHEN as2.idAreaServicio IN (12,21,46)  THEN 'Hospitalización Privada'
                        ELSE 'Hospitalización General'
                    END AS tipo_servicio,
                    '0' AS sede_id,
                    COUNT(1) AS cantidad
            FROM 
                Pedidos p
            INNER JOIN 
                Ordenes o ON p.idPedido = o.idPedido
            INNER JOIN 
                AreasServicios as2 ON as2.idAreaServicio = p.idAreaServicio
            INNER JOIN 
                Tiempos t ON o.idTiempo = t.idTiempo
            WHERE " . ($tipo_busquedad == 1 ?  "p.fecha BETWEEN CONVERT(DATE, '$fecha_inicio', 103) AND CONVERT(DATE, '$fecha_fin', 103)" : "p.fecha = CONVERT(DATE, '$fecha_fin', 103)") . "
            GROUP BY 
                CONVERT(VARCHAR, p.fecha, 120),
                t.nombre,
                CASE 
                    WHEN as2.idAreaServicio IN (12,21,46)  THEN 'Hospitalización Privada'
                    ELSE 'Hospitalización General'
                END
            ORDER BY 
                CONVERT(VARCHAR, p.fecha, 120) DESC,
                t.nombre ASC;";

            $stmt2 = $connD->query($sql);
            $dietaReport = $stmt2->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            return $dietaReport;
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }


    public function getPlanInscripcionEvents(array $arrayParams): array
    {
        $tipo_busquedad = $arrayParams['tipo_busquedad'];
        $fecha = date("Y-m-d", strtotime($arrayParams['fecha']));

        try {
            $db = new DB($this->databases);
            $connD = $db->getConnection('EVENTOS');

            $query = 'SELECT
                :fecha AS fecha,
                tpins.descripcion as plan,
                count(1) as cantidad
            FROM
                wp_eiparticipante tb
                INNER JOIN wp_tipo_planes_inscripcion tpins 
            ON tb.id_tipo_planes_inscripcion = tpins.id
            WHERE
                tb.estaInscrito = 1 
                AND tb.evento IN (
                    "XXI PRECONGRESO CIENTÍFICO MÉDICO",
                    "XXI CONGRESO CIENTÍFICO MÉDICO",
                    "XXI PRECONGRESO y CONGRESO CIENTÍFICO MÉDICO"
                ) 
                AND tb.id_participante >= 1030 AND tb.id_participante <= 2018
                AND DATE(tb.fecha) <= :fecha
            GROUP BY
                tb.id_tipo_planes_inscripcion;';

            $stmt = $connD->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();
            $reportEvents = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            return $reportEvents;
        } catch (PDOException $e) {
            error_log($e->getMessage());  // Log the error message
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }

    public function getPlanInscripcionEventsInstitutions(array $arrayParams): array
    {
        $fecha = date("Y-m-d", strtotime($arrayParams['fecha']));

        try {
            $db = new DB($this->databases);
            $connD = $db->getConnection('EVENTOS');

            $query = 'SELECT
            tb.id_tipo_institucion_oficial,
            tb.nombre_institucion,
            tb.id_tipo_planes_inscripcion,
            tpins.descripcion as plan,
            count(1) as cantidad
        FROM
            wp_eiparticipante tb
            INNER JOIN wp_tipo_planes_inscripcion tpins 
        ON tb.id_tipo_planes_inscripcion = tpins.id
        WHERE
            tb.estaInscrito = 1 
            AND tb.evento IN (
                "XXI PRECONGRESO CIENTÍFICO MÉDICO",
                "XXI CONGRESO CIENTÍFICO MÉDICO",
                "XXI PRECONGRESO y CONGRESO CIENTÍFICO MÉDICO"
            ) 
            AND tb.id_participante >= 1030 AND tb.id_participante <= 2018
            AND DATE(tb.fecha) <= :fecha
        GROUP BY
            tb.id_tipo_planes_inscripcion, tb.nombre_institucion;';

            $stmt = $connD->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();
            $reportEventsInstitutions = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            return $reportEventsInstitutions;
        } catch (PDOException $e) {
            error_log($e->getMessage());  // Log the error message
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }

    public function getNameInstitutionOficial($id)
    {
        try {
            $db = new DB($this->databases); // Suponiendo que DB es tu clase para manejar la conexión a la base de datos
            $connD = $db->getConnection('EVENTOS'); // Suponiendo que 'EVENTOS' es el nombre de tu conexión

            $query = 'SELECT descripcion FROM wp_tipo_institucion_oficial WHERE estado = 1 AND id = :id';

            $stmt = $connD->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Asumiendo que $id es un entero, usar PDO::PARAM_INT para evitar inyecciones SQL
            $stmt->execute();
            $reportEventsInstitutions = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Cerramos la conexión
            $db = null;

            return $reportEventsInstitutions[0]->descripcion;
        } catch (PDOException $e) {
            error_log($e->getMessage());  // Registrar el mensaje de error en el log
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }

    public function getRelojIdsBySerials(array $deviceSerials): array
    {
        $relojIds = [];
        if (empty($deviceSerials)) {
            return $relojIds; // Retornar un array vacío si no hay seriales
        }

        try {
            $db = new DB($this->databases);
            $connD = $db->getConnection('RRHH_PROD');

            // Crear una lista de placeholders para la consulta
            $placeholders = rtrim(str_repeat('?, ', count($deviceSerials)), ', ');

            // Prepara la consulta para obtener los IDs de reloj
            $query = "SELECT DeviceSerial, IDRELOJ 
                      FROM RRHH.[dbo].[CAT DE RELOJ] 
                      WHERE DeviceSerial IN ($placeholders)";
            $stmt = $connD->prepare($query);
            $stmt->execute($deviceSerials);

            // Obtiene todos los resultados
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mapea los resultados al array de retorno
            foreach ($results as $row) {
                $relojIds[$row['DeviceSerial']] = $row['IDRELOJ'];
            }

            // Cerramos la conexión
            $db = null;
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }

        return $relojIds;
    }

    public function getHorarios(): array
    {
        try {
            $db = new DB($this->databases); // Suponiendo que DB es tu clase para manejar la conexión a la base de datos
            $connD = $db->getConnection('RRHH_DEV'); // Suponiendo que 'EVENTOS' es el nombre de tu conexión
            $query = 'SELECT 
                            ID_T_HORARIO AS ID,
                            DESCRIPCION,
                            CHRT,
                            C_DIAS_T,
                            C_DIAS_L,
                            CONVERT(VARCHAR(32), [HORA_E], 108) AS HoraEntrada,
                            CONVERT(VARCHAR(32), [HORA_S], 108) AS HoraSalida 
                      FROM dbo.[CAT DE TIPO DE HORARIO]';
            // $query = 'SELECT descripcion FROM wp_tipo_institucion_oficial WHERE estado = 1 AND id = :id';

            $stmt = $connD->prepare($query);
            $stmt->execute();
            $horarios = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Cerramos la conexión
            $db = null;

            return $horarios;
        } catch (PDOException $e) {
            error_log($e->getMessage());  // Registrar el mensaje de error en el log
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }

    public function getMarksClockHivision($fecha_inicio, $fecha_fin): array
    {
        try {
            $db = new DB($this->databases);
            $connD = $db->getConnection('IVSM_PROD');

            // $fecha_inicio = $fecha_inicio ? $fecha_inicio : date('Y-m-d', strtotime('2024-09-28'));
            $fecha_inicio = $fecha_inicio ? $fecha_inicio : date('Y-m-d', strtotime('-1 day'));
            $fecha_fin = $fecha_fin ? $fecha_fin : date('Y-m-d');

            $query = 'SELECT *
                      FROM attlog a 
                      WHERE a.Direction IN ("ENTRADA", "SALIDA")
                        AND a.AuthDate BETWEEN :ayer AND :hoy
                      ORDER BY DeviceSerial ASC, a.AuthDate DESC, Authtime ASC';

            $stmt = $connD->prepare($query);

            $stmt->bindValue(':ayer', $fecha_inicio);
            $stmt->bindValue(':hoy', $fecha_fin);

            $stmt->execute();
            $horarios = $stmt->fetchAll(PDO::FETCH_OBJ);

            $db = null;

            return $horarios;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }


    public function restructuredArray($array): array
    {
        foreach ($array as $objeto) {
            $arreglo = (array) $objeto;
            $arreglo_arreglos[] = $arreglo;
        }
        return $arreglo_arreglos;
    }

    private static function filterDataByDirection1(array $data, array $deviceSerials): array
    {
        // Agruparemos los datos por PersonID y AuthDate
        $groupedData = [];

        foreach ($data as $item) {
            $personID = $item['PersonID'];
            $authDate = $item['AuthDate'];
            $direction = $item['Direction'];

            // Solo agrupar datos válidos
            if ($direction === 'ENTRADA' || $direction === 'SALIDA') {
                // Inicializamos un grupo si no existe
                if (!isset($groupedData[$personID][$authDate])) {
                    $groupedData[$personID][$authDate] = [
                        'ENTRADA' => [],
                        'SALIDA' => [],
                    ];
                }

                // Añadimos el item al grupo correspondiente
                $groupedData[$personID][$authDate][$direction][] = $item;

                // Para el caso específico de 'ENTRADA', limitamos a 2 entradas
                if ($direction === 'ENTRADA') {
                    // Si hay más de 2 entradas, mantenemos solo la primera y la última
                    if (count($groupedData[$personID][$authDate]['ENTRADA']) > 2) {
                        usort($groupedData[$personID][$authDate]['ENTRADA'], function ($a, $b) {
                            return strcmp($a['Authtime'], $b['Authtime']);
                        });

                        // Guardar la primera y la última
                        $firstEntry = $groupedData[$personID][$authDate]['ENTRADA'][0];
                        $lastEntry = end($groupedData[$personID][$authDate]['ENTRADA']);

                        // Reemplazamos el array de entradas por solo la primera y última
                        $groupedData[$personID][$authDate]['ENTRADA'] = [$firstEntry, $lastEntry];
                    }
                }
            }
        }

        $filteredData = [];
        $previousEntry = null;
        foreach ($groupedData as $personID => $dates) {
            foreach ($dates as $authDate => $directions) {
                if (isset($directions['ENTRADA']) && count($directions['ENTRADA']) <> 0) {

                    usort($directions['ENTRADA'], function ($a, $b) {
                        return strcmp($a['Authtime'], $b['Authtime']);
                    });

                    if (count($directions['ENTRADA']) > 1 && count($directions['SALIDA']) > 1) {
                        $filteredData[] = $directions['ENTRADA'][0];
                    } elseif (count($directions['ENTRADA']) > 1 && count($directions['SALIDA']) === 0) {

                        $filteredData[] = $directions['ENTRADA'][0];

                        $firstEntryTime = strtotime($directions['ENTRADA'][0]['Authtime']);
                        $secondEntryTime = strtotime($directions['ENTRADA'][1]['Authtime']);

                        $timeDifference = $secondEntryTime - $firstEntryTime;

                        if ($timeDifference > 7200) {
                            $directions['ENTRADA'][1]['Direction'] = 'SALIDA';
                            $filteredData[] = $directions['ENTRADA'][1];
                        }
                    } else {
                        $filteredData[] = $directions['ENTRADA'][0];
                    }

                    if ($previousEntry !== null) {
                        $currentEntryTime = strtotime($directions['ENTRADA'][0]['Authtime']);
                        $previousEntryTime = strtotime($previousEntry['Authtime']);


                        $timeDifference = ($currentEntryTime - $previousEntryTime) / 3600;

                        if ($timeDifference >= 10) {
                            $previousEntry['Direction'] = 'SALIDA';
                            $filteredData[] = $previousEntry;
                        }
                    }

                    $previousEntry = end($directions['ENTRADA']);
                }

                // Filtrar las salidas
                if (isset($directions['SALIDA']) && count($directions['SALIDA']) <> 0) {
                    usort($directions['SALIDA'], function ($a, $b) {
                        return strcmp($b['Authtime'], $a['Authtime']);
                    });

                    $filteredData[] = $directions['SALIDA'][0];
                }
            }
        }
        return array_filter($filteredData, function ($item) {
            return !empty($item['Direction']) && ($item['Direction'] === 'ENTRADA' || $item['Direction'] === 'SALIDA');
        });
    }

    private static function filterDataByCriteria(array $devices, string $deviceSerial, string $direction, string $personID): array
    {
        $filteredDevices = [];

        foreach ($devices as $device) {
            // Verificar si el DeviceSerial coincide
            if ($device['DeviceSerial'] === $deviceSerial) {
                // Filtrar los datos internos por Direction y PersonID
                $filteredData = array_filter($device['Data'], function ($item) use ($direction, $personID) {
                    return $item['Direction'] === $direction && $item['PersonID'] == $personID;
                });

                // Si hay datos que coinciden con los criterios, agregar al resultado
                if (!empty($filteredData)) {
                    // Asignar los datos filtrados al nuevo array
                    $device['Data'] = array_values($filteredData); // Reindexar
                    $filteredDevices[] = $device; // Agregar el dispositivo con los datos filtrados
                }
            }
        }

        return $filteredDevices;
    }

    public function getSellSiServiClientService(array $arrayParams): array
    {
        $tipo_busquedad = $arrayParams['tipo_busquedad'];
        $fecha_inicio = $arrayParams['fecha_inicio'];
        $fecha_fin = $arrayParams['fecha_fin'];
        $listaServicioAlimentacion = implode("','", $arrayParams['lista_servicios']);
        $query = "SELECT ROW_NUMBER() OVER (ORDER BY cli.sede_id ASC, v.vc_emision_feho::time ASC) AS Contador,
                        concat(cli.clie_nom, ' ', cli.clie_pat, ' ', cli.clie_mat) AS NombreCompleto,
                        CAST(cli.clie_docnum AS VARCHAR(64)) as Carnet,
                        s.sede_descri AS Sede,
                        COALESCE(ca.cat_descri, 'SIN DEFINIR') AS Departamento,
                        v.serv_id AS Servicio,
                        v.vc_total AS Cantidad,
                        v.vc_emision_feho::date AS FechaCorte
                  FROM dmona.clientes cli
                  INNER JOIN dmona.ventas_cab v ON v.clie_docnum = cli.clie_docnum
                  INNER JOIN dmona.sede s ON s.sede_id = cli.sede_id
                  LEFT JOIN dmona.categoria ca ON ca.cat_id = cli.cat_id
                  WHERE " . ($tipo_busquedad == 1 ? "v.vc_emision_feho::date BETWEEN '$fecha_inicio' AND '$fecha_fin'" : "v.vc_emision_feho::date = '$fecha_inicio'") .
            " AND v.vc_anulado = 0 
                  AND v.serv_id IN ('$listaServicioAlimentacion') 
                  ORDER BY cli.sede_id ASC, v.vc_emision_feho::time ASC;";
        try {
            $db = new DB($this->databases);
            $conn = $db->getConnection('SISERVI');
            $stmt = $conn->query($query);
            $siserviReport = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            return $siserviReport;
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            return $error;
        }
    }
}
