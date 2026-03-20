<?php

// Cargamos todas las librerías instaladas con Composer — incluyendo firebase/php-jwt
require_once __DIR__ . '/../vendor/autoload.php';

// Cargamos la clave secreta y el tiempo de expiración
require_once __DIR__ . '/../config/jwt.php';

// Cargamos el model de usuario para verificar credenciales
require_once __DIR__ . '/../models/usuarioModel.php';

// Importamos la clase JWT del namespace de Firebase
// En PHP los namespaces son como los imports de Node
use Firebase\JWT\JWT;

// --- HEADERS ---
require_once __DIR__ . '/../config/headers.php';
// Le decimos al cliente que la respuesta es JSON
// Esto es obligatorio en cualquier API REST
header('Content-Type: application/json');

// CORS — permite que otros dominios consuman esta API
// Sin esto un frontend en otro dominio no podría hacer fetch()
header('Access-Control-Allow-Origin: *');

// Solo aceptamos POST en este endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // http_response_code() establece el código HTTP de la respuesta
    // 405 = Method Not Allowed
    http_response_code(405);

    // json_encode() convierte array PHP a string JSON
    // Es el equivalente de JSON.stringify() en JavaScript
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Leemos el cuerpo crudo de la petición
// Los datos JSON no llegan en $_POST, llegan aquí
$body = file_get_contents('php://input');

// Convertimos el JSON a array PHP — el true hace que devuelva array en vez de objeto
$data = json_decode($body, true);

// Verificamos que llegaron los campos necesarios
if (empty($data['email']) || empty($data['password'])) {
    http_response_code(400); // 400 = Bad Request — faltan datos
    echo json_encode(['error' => 'Email y password son obligatorios.']);
    exit;
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email no válido.']);
    exit;
}

try {
    $usuarioModel = new Usuario();
    $user         = $usuarioModel->findByEmail($data['email']);

    // Verificamos que el usuario existe y la contraseña es correcta
    // password_verify($password_plano, $hash_de_la_BD)
    if (!$user || !password_verify($data['password'], $user['password'])) {
        // 401 = Unauthorized — credenciales incorrectas
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales incorrectas.']);
        exit;
    }

    // Credenciales correctas — generamos el JWT
    $payload = [
        'id'       => $user['id'],       // datos que queremos guardar en el token
        'email'    => $user['email'],    // el cliente los puede leer pero no modificar
        'exp'      => time() + JWT_EXPIRATION // timestamp de expiración
    ];

    // JWT::encode($payload, $clave_secreta, $algoritmo)
    // HS256 es el algoritmo de firma — HMAC con SHA-256
    // Es el más usado, rápido y seguro para APIs
    // Otros algoritmos: RS256 (más seguro, usa clave pública/privada), HS512
    $token = JWT::encode($payload, JWT_SECRET, 'HS256');

    // 200 = OK — devolvemos el token al cliente
    http_response_code(200);
    echo json_encode([
        'token'   => $token,
        'expires' => JWT_EXPIRATION // le decimos al cliente cuándo expira
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    // 500 = Internal Server Error — algo falló en el servidor
    http_response_code(500);
    echo json_encode(['error' => 'Error inesperado, inténtalo de nuevo.']);
}

