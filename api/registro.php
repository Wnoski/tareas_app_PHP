<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/usuarioModel.php';
require_once __DIR__ . '/../config/mailer.php';

// --- HEADERS ---
require_once __DIR__ . '/../config/headers.php';
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// --- POST — crear usuario ---
// Body JSON: { "email": "usuario@email.com", "password": "contraseña" }
try {
    $body     = json_decode(file_get_contents('php://input'), true);
    $email    = $body['email']    ?? '';
    $password = $body['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email y contraseña son obligatorios.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email no válido.']);
    exit;
}

    $userModel = new Usuario();

    // Verificamos si el email ya existe antes de intentar insertarlo
    if ($userModel->findByEmail($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ese email ya está registrado.']);
        exit;
    }

    // Hasheamos antes de pasar al model — el model recibe el hash, nunca el texto plano
    $newUser = $userModel->crearUsuario($email, password_hash($password, PASSWORD_DEFAULT));

    if ($newUser) {
        // Generamos token de verificación y lo guardamos en la BD
        $token = bin2hex(random_bytes(32));
        $userModel->guardarToken($newUser, $token);

        // Mandamos email con enlace que apunta al endpoint de la API
        enviarEmailVerificacion($email, $token, 'api');

        http_response_code(201);
        echo json_encode(['mensaje' => 'Usuario creado. Revisa tu correo y verifica tu cuenta para poder usarla.']);

    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo crear el usuario.']);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error inesperado.']);
}