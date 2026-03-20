<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/usuarioModel.php';

// --- HEADERS ---
require_once __DIR__ . '/../config/headers.php';
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Solo aceptamos GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// --- GET — verificar cuenta ---
// Token en la URL: api/verificar.php?token=abc123
try {
    $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token vacío o inválido.']);
        exit;
    }

    $userModel  = new Usuario();
    $verificado = $userModel->verificarCuenta($token);

    if ($verificado) {
        http_response_code(200);
        echo json_encode(['mensaje' => 'Cuenta verificada correctamente. Ya puedes iniciar sesión.']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Token inválido o cuenta ya verificada.']);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error inesperado.']);
}