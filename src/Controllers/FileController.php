<?php

namespace App\Controllers;

use App\Models\DB;
use ExcelReportService;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class FileController
{
    protected $storageDirectory;

    public function __construct()
    {
        $this->storageDirectory = __DIR__ . '/../../storage/';
    }

    public function uploadFile()
    {
        // Verificar si se recibió un archivo
        if (isset($_FILES['file'])) {
            $uploadedFile = $_FILES['file'];

            // Nombre del archivo
            $fileName = basename($uploadedFile['name']);

            // Ruta completa del archivo en el directorio de almacenamiento
            $filePath = $this->storageDirectory . $fileName;

            // Mover el archivo al directorio de almacenamiento
            if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                // Respuesta exitosa
                echo json_encode(['message' => 'Archivo subido con éxito.', 'file' => $filePath]);
            } else {
                // Error al mover el archivo
                echo json_encode(['error' => 'Error al subir el archivo.']);
            }
        } else {
            // No se recibió ningún archivo
            echo json_encode(['error' => 'No se recibió ningún archivo.']);
        }
    }

    public function filterData(&$str)
    {
        // Escape special characters
        $str = addslashes($str);
        // Enclose fields containing spaces, commas, or tabs in double quotes
        if (preg_match("/ |\t|,|\"/", $str)) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        }
    }


    // public function downloadExcel(Request $request, Response $response)
    // {
    //     try {
    //         // Database connection
    //         $db = new DB();
    //         $conn = $db->connect();
    //         $query = $conn->query("SELECT aux_apenom AS id, aux_apepat AS first_name, aux_apemat AS email FROM dmona.aux_clientes");
    //         $db = null;

    //         // CSV file name for download
    //         $fileName = "members-data_" . date('Y-m-d') . ".csv";

    //         // Column names
    //         $fields = array('ID', 'FIRST NAME', 'EMAIL');

    //         // Output CSV headers
    //         header("Content-Type: text/csv");
    //         header("Content-Disposition: attachment; filename=\"$fileName\"");

    //         // Create a file pointer connected to the output stream
    //         $output = fopen('php://output', 'w');


    //         // Create a temporary file to store the CSV data
    //         $tempFile = tempnam(sys_get_temp_dir(), 'csv');
    //         $output = fopen($tempFile, 'w');

    //         // Output the column headings
    //         fputcsv($output, $fields);

    //         // Fetch records from the database
    //         while ($row_data = $query->fetch(PDO::FETCH_ASSOC)) {
    //             fputcsv($output, $row_data);
    //         }

    //         // Close the file pointer
    //         fclose($output);
            
    //         // Email configuration
    //         $mail = new PHPMailer(true);

    //         // Enable SMTP debugging
    //         $mail->isSMTP();
    //         $mail->Host = 'mail.hospitalmilitar.com.ni';
    //         $mail->SMTPAuth = true;
    //         $mail->Username = 'luis.tapia@hospitalmilitar.com.ni';
    //         $mail->Password = '8GTu*DP~!nXU';
    //         $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    //         $mail->Port = 587;

    //         // Sender and recipient
    //         $mail->setFrom('cesar.cuadra@hospitalmilitar.com.ni', 'Your Name');
    //         $mail->addAddress('maleisho@gmail.com', 'Recipient Name');

    //         // Attach the CSV file
    //         $mail->addAttachment($tempFile, $fileName);

    //         // Email content
    //         $mail->isHTML(true);
    //         $mail->Subject = 'CSV File Attachment';
    //         $mail->Body    = 'Please find the attached CSV file.';

    //         // Send the email
    //         $mail->send();

    //         // Clean up the temporary file
    //         unlink($tempFile);

    //         // Your existing code for JSON response
    //         $response->getBody()->write(json_encode(["status" => "success"]));
    //         exit;

    //         // $response->getBody()->write(json_encode(["hola" => "cesar"]));
    //         // return $response
    //         //     ->withHeader('content-type', 'application/json')
    //         //     ->withStatus(200);
    //     } catch (PDOException $e) {
    //         $error = ["message" => $e->getMessage()];
    //         $response->getBody()->write(json_encode($error));
    //         return $response
    //             ->withHeader('content-type', 'application/json')
    //             ->withStatus(500);
    //     }
    // }
}
