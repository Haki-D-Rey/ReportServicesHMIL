<?php

namespace App\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController
{
    private $mailer;
    private $fromEmail = 'gti@hospitalmilitar.com.ni';
    private $fromName = 'Gerencia de Tecnología Información';
    private $destinatary = [
        [
            "name" => "Gerencia de Tecnologia",
            "email" => "gti@hospitalmilitar.com.ni"
        ],
    ];
    private $subject = '';
    private $body = '';
    private $mailConfig = [
        'Host' => 'smtp.elasticemail.com',
        'SMTPAuth' => true,
        'Username' => 'gti@hospitalmilitar.com.ni',
        'Password' => '8DA58248225E3D6BD9D5B688400D2AC7B18F',
        'SMTPSecure' => 'ssl',  // Cambiado a SSL según la documentación de Elastic Email
        'Port' => 465  // Puerto específico de Elastic Email
    ];


    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
    }

    public function sendEmail(array $parametros): PHPMailer
    {
        $this->configEmail(
            isset($parametros['fromEmail']) && !empty($parametros['fromEmail']) ? $parametros['fromEmail'] : $this->fromEmail,
            isset($parametros['fromName']) && !empty($parametros['fromName']) ? $parametros['fromName'] : $this->fromName,
            isset($parametros['destinatary']) && !empty($parametros['destinatary']) ? $parametros['destinatary'] : $this->destinatary,
            isset($parametros['subject']) && !empty($parametros['subject']) ? $parametros['subject'] : $this->subject,
            isset($parametros['body']) && !empty($parametros['body']) ? $parametros['body'] : $this->body
        );

        return $this->mailer;
    }

    public function configEmail($fromEmail, $fromName, $destinatary, $subject, $body): PHPMailer
    {

        try {
            // Configuración del servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->mailConfig['Host'];
            $this->mailer->SMTPAuth = $this->mailConfig['SMTPAuth'];
            $this->mailer->Username = $this->mailConfig['Username'];
            $this->mailer->Password = $this->mailConfig['Password'];
            $this->mailer->SMTPSecure = $this->mailConfig['SMTPSecure'];
            $this->mailer->Port = $this->mailConfig['Port'];
            $this->mailer->CharSet = 'UTF-8';

            // Configuración del remitente y destinatario
            $this->mailer->setFrom($fromEmail, $fromName);
            // Suponiendo que $toEmails es un array que contiene los correos electrónicos a los que deseas enviar el correo
            foreach ($destinatary as $item) {
                $nombre = $item['name'];
                $email = $item['email'];
                // Aquí puedes usar el método addAddress() para agregar el destinatario al objeto $this->mailer
                $this->mailer->addAddress($email, $nombre);
            }


            // Contenido del correo
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            return  $this->mailer;
        } catch (Exception $e) {
            return 'Error al enviar el correo: ' . $this->mailer->ErrorInfo;
        }
    }
}
