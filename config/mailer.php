<?php


// Cargamos el autoload de Composer — necesario para usar PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

// Importamos las clases de PHPMailer
// PHPMailer — clase principal para enviar correos
// SMTP — clase para la conexión SMTP
// Exception — clase para manejar errores de PHPMailer
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
    $mail->Host     = 'smtp.resend.com';
    $mail->SMTPAuth = true;

    // Usuario SMTP de Resend — siempre es "resend"
    // Password — tu API key de Resend
    $mail->Username   = 'resend';
    $mail->Password   = 're_JwoJvTvE_HguJVCnkEMzhnw3iuHRKtHzv';

    // SMTPS usa SSL en el puerto 465 — recomendado por Resend
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Remitente — onboarding@resend.dev es el email de pruebas de Resend
    // En producción usarías tu propio dominio verificado en Resend
    $mail->setFrom('onboarding@resend.dev', 'Tareas App');

    return $mail;
}


// --- EMAIL DE VERIFICACIÓN ---
// Genera y envía el email con el enlace de verificación
// $email — dirección del destinatario
// $token — token único generado en el controller con bin2hex(random_bytes(32))
function enviarEmailVerificacion($email, $token) {
    try {
        // Obtenemos la instancia de PHPMailer ya configurada
        $mail = crearMailer();

        // Construimos el enlace de verificación con el token en la URL
        // Cuando el usuario haga clic el controller leerá el token con filter_input
        $enlace = "http://localhost/ejericiosPHP/tareas_app/index.php?controller=auth&action=verificar&token=" . $token;

        // Añadimos el destinatario — puede llamarse varias veces para múltiples destinatarios
        $mail->addAddress($email);

        // Activamos HTML en el cuerpo del email
        // Sin esto el HTML se mostraría como texto plano
        $mail->isHTML(true);

        $mail->Subject = 'Verifica tu cuenta en Tareas App';

        // Cuerpo HTML — lo que ve el usuario si su cliente soporta HTML
        $mail->Body = "
            <h1>Bienvenido a Tareas App</h1>
            <p>Haz clic en el enlace para verificar tu cuenta:</p>
            <a href='{$enlace}'>Verificar cuenta</a>
            <p>Si no creaste esta cuenta ignora este mensaje.</p>
        ";

        // Cuerpo en texto plano — fallback para clientes que no soportan HTML
        // Siempre es buena práctica incluirlo
        $mail->AltBody = "Verifica tu cuenta entrando en este enlace: {$enlace}";

        // Enviamos el correo — si algo falla lanza una Exception
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Logueamos el error real para el desarrollador
        // No relanzamos la excepción — el controller decide si el email
        // fallido es un error crítico o no
        error_log($e->getMessage());
        return false;
    }
}


// --- EMAIL DE RESET DE CONTRASEÑA ---
// Se llama desde AuthController->olvidoPassword() tras generar el token
// $email — dirección del destinatario
// $token — token único generado con bin2hex(random_bytes(32))
function enviarEmailReset($email, $token) {
    try {
        $mail = crearMailer();

        // Enlace con el token en la URL — expira en 1 hora
        // Al hacer clic el controller leerá el token con filter_input
        $enlace = "http://localhost/ejericiosPHP/tareas_app/index.php?controller=auth&action=resetPassword&token=" . $token;

        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Cambio de contraseña — Tareas App';

        // Cuerpo HTML — lo que ve el usuario si su cliente soporta HTML
        $mail->Body = "
            <h1>Cambio de contraseña</h1>
            <p>Haz clic en el enlace para cambiar tu contraseña:</p>
            <a href='{$enlace}'>Cambiar contraseña</a>
            <p>El enlace expira en 1 hora.</p>
            <p>Si no pediste este cambio ignora este mensaje.</p>
        ";

        // Versión en texto plano — fallback para clientes que no soportan HTML
        $mail->AltBody = "Cambia tu contraseña entrando en este enlace: {$enlace}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}




