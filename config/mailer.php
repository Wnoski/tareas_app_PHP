<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function crearMailer() {
    $mail = new PHPMailer(true); // true activa las excepciones

    $mail->isSMTP();
    $mail->Host       = 'smtp.resend.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'resend';
    $mail->Password   = 're_cwm9wE6p_7BqZ62ptyBFj5vJ2ya7BKuCY';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL en puerto 465
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Remitente — debe ser un dominio verificado en Resend
    // Para pruebas Resend permite onboarding@resend.dev
    $mail->setFrom('onboarding@resend.dev', 'Tareas App');

    return $mail;
}