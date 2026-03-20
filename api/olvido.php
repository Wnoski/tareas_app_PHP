<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/usuarioModel.php';
require_once __DIR__ . '/../config/mailer.php';

// --- HEADERS ---
require_once __DIR__ . '/../config/headers.php';
// Le decimos al cliente que la respuesta es JSON
// Esto es obligatorio en cualquier API REST
header('Content-Type: application/json');

// CORS — permite que otros dominios consuman esta API
// Sin esto un frontend en otro dominio no podría hacer fetch()
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');


// --- POST — solicitar reset de contraseña ---
// Body JSON: { "email": "usuario@email.com" }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 = Method Not Allowed
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

try {
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = $body['email'] ?? '';

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'El email es obligatorio.']);
        exit;
    }

    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email no válido.']);
    exit;
}

    $usuarioModel = new Usuario();

    // Mensaje genérico independientemente de si el email existe o no
    // Evita que un atacante pueda enumerar emails registrados
    if ($usuarioModel->findByEmail($email)) {
        $token  = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $usuarioModel->guardarTokenReset($email, $token, $expira);
        enviarEmailReset($email, $token, "api");
    }

    // Siempre devolvemos 200 con el mismo mensaje
    http_response_code(200);
    echo json_encode(['mensaje' => 'Si el email está registrado recibirás un correo en breve.']);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error inesperado.']);
}