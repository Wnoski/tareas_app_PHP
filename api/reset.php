<?php

require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Usuario; // Importamos la clase Usuario del namespace App\Models definido en el model Usuario.php
require_once __DIR__ . '/../helpers/rate_limiter.php';
// --- HEADERS ---
require_once __DIR__ . '/../config/headersAPI.php';




// --- GET — validar token ---
// El cliente manda el token en la URL para verificar que es válido antes de mostrar el form
// GET api/reset.php?token=abc123
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token vacío o inválido.']);
        exit;
    }

    // Aquí $token queda disponible en el scope del archivo
    // El if POST de abajo puede usarlo directamente
}

$ip = $_SERVER["REMOTE_ADDR"];
checkRateLimit($ip,'reset');

// --- POST — cambiar contraseña ---
// Body JSON: { "password": "nuevaPassword" }
// Token en la URL: api/reset.php?token=abc123
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$ip = $_SERVER["REMOTE_ADDR"];
checkRateLimit($ip,'reset');

    try {
        $body     = json_decode(file_get_contents('php://input'), true);
        $password = $body['password'] ?? '';
        $token    = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($token) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Token y contraseña son obligatorios.']);
            exit;
        }

        $userModel = new Usuario();
        $userReset = $userModel->verificarTokenReset($token);

        if ($userReset) {
            // Hasheamos la nueva contraseña antes de guardarla
            $userModel->actualizarPassword(
                $userReset['id'],
                password_hash($password, PASSWORD_DEFAULT)
            );
            http_response_code(200);
            echo json_encode(['mensaje' => 'Contraseña cambiada correctamente.']);
            exit;

        } else {
            // Token no existe o expiró
            http_response_code(400);
            echo json_encode(['error' => 'Token expirado o inválido.']);
            exit;
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error inesperado.']);
    }
}