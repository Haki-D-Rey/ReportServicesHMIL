<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController
{
    private $mailer;


    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
    }

    public function sendEmail(Request $request, Response $response, $args)
    {
        // Obtener datos del cuerpo de la solicitud (puedes ajustar esto según tus necesidades)
        $requestData = $request->getParsedBody();

        // // Ejemplo de uso
        $result = $this->configEmail(
            $requestData['fromEmail'],
            $requestData['fromName'],
            $requestData['toEmail'],
            $requestData['toName'],
            $requestData['subject'],
            $requestData['body']
        );

          $result = $this->configEmail(
            'cesar.cuadra@hospitalmilitar.com.ni',
            'Cesar Cuadra',
            'maleisho@gmail.com',
            'haki',
            'prueba',
            'hola como estas'
        );

        if ($result === true) {
            $response->getBody()->write('El correo se ha enviado correctamente.');
        } else {
            $response->getBody()->write($result);
        }

        return $response;
    }

    public function configEmail($fromEmail, $fromName, $toEmail, $toName, $subject, $body)
    {
        try {
            // Configuración del servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host = 'mail.hospitalmilitar.com.ni';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'luis.tapia@hospitalmilitar.com.ni';
            $this->mailer->Password = '8GTu*DP~!nXU';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;

            // Configuración del remitente y destinatario
            $this->mailer->setFrom($fromEmail, $fromName);
            $this->mailer->addAddress($toEmail, $toName);

            // Contenido del correo
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            // Enviar correo
            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            return 'Error al enviar el correo: ' . $this->mailer->ErrorInfo;
        }
    }
}
