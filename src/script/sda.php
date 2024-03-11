<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $this->mailer->isSMTP();
    $this->mailer->Host = 'mail.hospitalmilitar.com.ni';
    $this->mailer->SMTPAuth = true;
    $this->mailer->Username = 'luis.tapia@hospitalmilitar.com.ni';
    $this->mailer->Password = '8GTu*DP~!nXU';
    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $this->mailer->Port = 587;

    // Configuración del correo
    $mail->setFrom('cesar.cuadra@hospitalmilitar.com.ni', 'Tu Nombre');
    $mail->addAddress('maleisho@gmail.com', 'Nombre del destinatario');
    $mail->Subject = 'Asunto del correo';
    $mail->Body = 'pruena de cron job';

    // Otros ajustes y configuraciones si es necesario

    // Envía el correo
    $mail->send();
    echo 'Correo programado enviado con éxito!';
} catch (Exception $e) {
    echo 'Error al enviar el correo programado: ', $mail->ErrorInfo;
}
