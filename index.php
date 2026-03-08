<?php

// Iniciamos la sesión antes de cualquier lógica
// Necesario para que $_SESSION esté disponible en controllers y auth.php
session_start();

require_once __DIR__ . '/vendor/autoload.php';


// Leemos controller y action de la URL
// Por defecto van al login — si no hay parámetros el usuario ve el login
// Sanitizamos y validamos que solo contengan letras y números
$controller = filter_input(INPUT_GET, 'controller', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'auth';
$action     = filter_input(INPUT_GET, 'action',     FILTER_SANITIZE_SPECIAL_CHARS) ?? 'login';

// Validamos contra una lista blanca de valores permitidos
$controllersPermitidos = ['auth', 'tarea'];
$actionsPermitidas     = ['login', 'registro', 'logout', 'index', 'crear', 'editar', 'eliminar','verificar','olvidoPassword','resetPassword'];

if (!in_array($controller, $controllersPermitidos) || !in_array($action, $actionsPermitidas)) {
    header("Location: index.php?controller=auth&action=login");
    exit;
}


// Decidimos qué controller cargar según el parámetro de la URL
switch ($controller) {

    case 'auth':
        require_once __DIR__ . '/controllers/authController.php';
        $ctrl = new AuthController();
        break;

    case 'tarea':
        require_once __DIR__ . '/controllers/tareaController.php';
        $ctrl = new TareaController();
        break;

    default:
        // Controller no reconocido — redirigimos al login
        header("Location: index.php?controller=auth&action=login");
        exit;
}

// Ejecutamos el método del controller cuyo nombre está en $action
// Si $action es 'login' ejecuta $ctrl->login()
// Si $action es 'index' ejecuta $ctrl->index()
$ctrl->$action();