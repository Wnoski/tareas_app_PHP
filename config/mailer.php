<?php

// Cargamos el autoload de Composer — necesario para usar PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


// --- MAILER BASE ---
// Devuelve una instancia de PHPMailer ya configurada con Resend
// Reutilizable para cualquier tipo de email — evita repetir configuración
function crearMailer() {

    // true — activa el modo de excepciones en PHPMailer
    // Sin esto los errores se guardan silenciosamente y son difíciles de detectar
    $mail = new PHPMailer(true);

    // Usamos SMTP en vez de la función mail() de PHP
    // SMTP es más fiable y permite usar servicios externos como Resend
    $mail->isSMTP();
    $mail->Host     = '';
    $mail->SMTPAuth = true;

    // Usuario SMTP 
    // Password — tu API key 
    $mail->Username   = '';
    $mail->Password   = '';

    // SMTPS usa SSL en el puerto 465 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Remitente — 
    $mail->setFrom('', 'Tareas App');

    return $mail;
}


// --- EMAIL DE VERIFICACIÓN ---
// Se llama desde AuthController->registro() o api/registro.php tras crear el usuario
// $email  — dirección del destinatario
// $token  — token único generado con bin2hex(random_bytes(32))
// $source — 'web' para la app PHP, 'api' para la API REST
function enviarEmailVerificacion($email, $token, $source = 'web') {
    try {
        $mail = crearMailer();

        // Generamos el enlace según el origen de la petición
        // web — apunta al controller de la app PHP
        // api — apunta al endpoint de la API REST
        $enlace = match ($source) {
            'api'   => "http://localhost/ejericiosPHP/tareas_app/api/verificar.php?token=" . $token,
            'web'   => "http://localhost/ejericiosPHP/tareas_app/index.php?controller=auth&action=verificar&token=" . $token,
            default => "",
        };

        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu cuenta en Tareas App';
        $mail->Body    = "
            <h1>Bienvenido a Tareas App</h1>
            <p>Haz clic en el enlace para verificar tu cuenta:</p>
            <a href='{$enlace}'>Verificar cuenta</a>
            <p>Si no creaste esta cuenta ignora este mensaje.</p>
        ";
        $mail->AltBody = "Verifica tu cuenta entrando en este enlace: {$enlace}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}


// --- EMAIL DE RESET DE CONTRASEÑA ---
// Se llama desde AuthController->olvidoPassword() o api/olvido.php tras generar el token
// $email  — dirección del destinatario
// $token  — token único generado con bin2hex(random_bytes(32))
// $source — 'web' para la app PHP, 'api' para la API REST
function enviarEmailReset($email, $token, $source = 'web') {
    try {
        $mail = crearMailer();

        // Generamos el enlace según el origen de la petición
        // El enlace expira en 1 hora — definido al guardar el token en la BD
        $enlace = match ($source) {
            'api'   => "http://localhost/ejericiosPHP/tareas_app/api/reset.php?token=" . $token,
            'web'   => "http://localhost/ejericiosPHP/tareas_app/index.php?controller=auth&action=resetPassword&token=" . $token,
            default => "",
        };

        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Cambio de contraseña — Tareas App';
        $mail->Body    = "
            <h1>Cambio de contraseña</h1>
            <p>Haz clic en el enlace para cambiar tu contraseña:</p>
            <a href='{$enlace}'>Cambiar contraseña</a>
            <p>El enlace expira en 1 hora.</p>
            <p>Si no pediste este cambio ignora este mensaje.</p>
        ";
        $mail->AltBody = "Cambia tu contraseña entrando en este enlace: {$enlace}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}