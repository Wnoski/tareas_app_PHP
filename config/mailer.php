<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function crearMailer() {
    $mail = new PHPMailer(true); // true activa las excepciones

    $mail->isSMTP();
    $mail->Host       = 'el_host';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'tu_username_o_del_gestor_de_correos';
    $mail->Password   = 'tu_api_key';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL en puerto 465
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    
    $mail->setFrom('correo_remitente', 'Tareas App');

    return $mail;
}
