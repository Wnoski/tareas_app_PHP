<?php

// Cargamos todas las librerías instaladas con Composer — incluyendo firebase/php-jwt
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/tareaModel.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

// --- HEADERS ---
// Siempre al principio, antes de cualquier output
header('Content-Type: application/json');

// CORS — permite peticiones desde otros dominios
// En producción cambiarías * por el dominio específico del frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Authorization, Content-Type');


// --- VERIFICAR TOKEN ---
// Función reutilizable que verifica el JWT y devuelve el usuario_id
// Si el token es inválido o expiró para la ejecución con error
function verificarToken() {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? '';

    if (empty($auth)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado.']);
        exit;
    }

    // Quitamos "Bearer " para quedarnos solo con el token
    $token = str_replace('Bearer ', '', $auth);

    try {
        // JWT::decode verifica automáticamente:
        // 1. Que la firma es válida con JWT_SECRET
        // 2. Que el token no ha expirado (campo 'exp' del payload)
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return $decoded->id;

    } catch (ExpiredException $e) {
        // Token expirado — el cliente debe hacer login de nuevo
        http_response_code(401);
        echo json_encode(['error' => 'Token expirado, inicia sesión de nuevo.']);
        exit;

    } catch (Exception $e) {
        // Token inválido — firma incorrecta, formato incorrecto, etc.
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido.']);
        exit;
    }
}


// --- GET — listar tareas ---
// Se ejecuta cuando: GET api/tareas.php
// Header requerido: Authorization: Bearer {token}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $usuario_id = verificarToken();

    try {
        $tareaModel = new TareaModel();
        $tareas     = $tareaModel->getTareas($usuario_id);

        // Aunque no haya tareas devolvemos 200 con array vacío
        // No encontrar tareas no es un error, es un resultado válido
        http_response_code(200);
        echo json_encode($tareas);

    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar las tareas.']);
    }
    exit;
}


// --- POST — crear tarea ---
// Se ejecuta cuando: POST api/tareas.php
// Header requerido: Authorization: Bearer {token}
// Body JSON: { "titulo": "Mi tarea" }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario_id    = verificarToken();
    $nombreArchivo = null;

    try {
        // Los datos JSON no llegan en $_POST
        // Se leen del cuerpo crudo de la petición
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        $titulo = $data['titulo'] ?? '';

        if (empty($titulo)) {
            http_response_code(400);
            echo json_encode(['error' => 'El título no puede estar vacío.']);
            exit;
        }

        $tareaModel = new TareaModel();
        $nuevaTarea = $tareaModel->crearTarea($titulo, $usuario_id, $nombreArchivo);

        if ($nuevaTarea) {
            http_response_code(201); // 201 = Created
            echo json_encode(['mensaje' => 'Tarea creada correctamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear la tarea.']);
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error inesperado.']);
    }
    exit;
}


// --- PUT — editar tarea ---
// Se ejecuta cuando: PUT api/tareas.php
// Header requerido: Authorization: Bearer {token}
// Body JSON: { "id": 1, "titulo": "Nuevo título", "completada": 1 }
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $usuario_id = verificarToken();
    $body       = json_decode(file_get_contents('php://input'), true);

    try {
        $titulo     = $body['titulo']     ?? '';
        $id         = $body['id']         ?? null;
        $completada = $body['completada'] ?? 0;

        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tarea incorrecto o inexistente.']);
            exit;
        }

        if (empty($titulo)) {
            http_response_code(400);
            echo json_encode(['error' => 'El título no puede estar vacío.']);
            exit;
        }

        $tareaModel = new TareaModel();

        // Verificamos que la tarea existe y pertenece al usuario — evita IDOR
        if (!$tareaModel->getTareaById($id, $usuario_id)) {
            http_response_code(404); // 404 = Not Found
            echo json_encode(['error' => 'Tarea no encontrada.']);
            exit;
        }

        $editada = $tareaModel->editarTarea($titulo, $id, $usuario_id, $completada);

        if ($editada) {
            http_response_code(200);
            echo json_encode(['mensaje' => 'Tarea editada correctamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo editar la tarea.']);
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error inesperado.']);
    }
    exit;
}


// --- DELETE — eliminar tarea ---
// Se ejecuta cuando: DELETE api/tareas.php
// Header requerido: Authorization: Bearer {token}
// Body JSON: { "id": 1 }
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    $usuario_id = verificarToken();
    $body       = json_decode(file_get_contents('php://input'), true);

    try {
        $id = $body['id'] ?? null;

        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tarea incorrecto o inexistente.']);
            exit;
        }

        $tareaModel = new TareaModel();

        // Verificamos que la tarea existe y pertenece al usuario — evita IDOR
        if (!$tareaModel->getTareaById($id, $usuario_id)) {
            http_response_code(404);
            echo json_encode(['error' => 'Tarea no encontrada.']);
            exit;
        }

        $eliminada = $tareaModel->eliminarTarea($id, $usuario_id);

        if ($eliminada) {
            http_response_code(200);
            echo json_encode(['mensaje' => 'Tarea eliminada correctamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo eliminar la tarea.']);
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error inesperado.']);
    }
    exit;
}


// --- MÉTODO NO PERMITIDO ---
// Si llega cualquier otro método HTTP no contemplado
http_response_code(405); // 405 = Method Not Allowed
echo json_encode(['error' => 'Método no permitido.']);