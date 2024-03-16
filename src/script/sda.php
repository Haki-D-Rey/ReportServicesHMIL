<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require (dirname(__DIR__).'/../vendor/autoload.php');

try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'mail.hospitalmilitar.com.ni';
    $mail->SMTPAuth = true;
    $mail->Username = 'luis.tapia@hospitalmilitar.com.ni';
    $mail->Password = '8GTu*DP~!nXU';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Configuración del correo
    $mail->setFrom('cesar.cuadra@hospitalmilitar.com.ni', 'Tu Nombre');
    $mail->addAddress('maleisho@gmail.com', 'Nombre del destinatario');
    $mail->Subject = 'Asunto del correo';
    $mail->Body = 'prueba de cron job';

    // Otros ajustes y configuraciones si es necesario

    // Envía el correo
    $mail->send();
    echo 'Correo programado enviado con éxito!';
} catch (Exception $e) {
    echo 'Error al enviar el correo programado: ', $mail->ErrorInfo;
}
