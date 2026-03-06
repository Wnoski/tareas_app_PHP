<?php

require_once __DIR__ . '/../models/usuarioModel.php';

class AuthController {


    // --- LOGIN ---
    // Se ejecuta cuando: index.php?controller=auth&action=login
    public function login() {
        $error = null;

        // Si ya hay sesión o cookie activa redirigimos al dashboard
        // No tiene sentido mostrar el login a alguien ya autenticado
        if (isset($_SESSION['id']) || isset($_COOKIE['usuario_id'])) {
            header("Location: index.php?controller=tarea&action=index");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            try {
                $usuario  = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
                $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
                $recordar = filter_input(INPUT_POST, 'recordar',  FILTER_VALIDATE_INT);

                // Instanciamos el model — el controller no toca la BD directamente
                $usuarioModel = new Usuario();
                $user         = $usuarioModel->findByUsername($usuario);

                if ($user && password_verify($password, $user['password'])) {

                    // Credenciales correctas — guardamos datos en sesión
                    // Desde aquí $_SESSION estará disponible en todas las páginas
                    $_SESSION['id']       = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Si marcó "Recuérdame" guardamos cookie por 7 días
                    if ($recordar == 1) {
                        setcookie('usuario_id', $user['id'], strtotime('+7 days'));
                    }

                    header("Location: index.php?controller=tarea&action=index");
                    exit;

                } else {
                    // Mensaje genérico — no revelamos si el usuario existe o no
                    // Evita que un atacante pueda enumerar usuarios válidos
                    $error = "Usuario o contraseña incorrectos.";
                }

            } catch (Exception $e) {
                // Error técnico inesperado — logueamos el real, mostramos uno genérico
                error_log($e->getMessage());
                $error = "Error inesperado, inténtalo de nuevo.";
            }
        }

        // Se ejecuta siempre — tanto en GET como si el login falló
        // $error está disponible en la vista porque require_once corre en este mismo scope
        require_once __DIR__ . '/../views/auth/login.php';
    }


    // --- REGISTRO ---
    // Se ejecuta cuando: index.php?controller=auth&action=registro
    public function registro() {
        $mensaje = null;

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $usuario = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);

            if (!empty($usuario)) {

                try {
                    $usuarioModel = new Usuario();

                    // Verificamos si el username ya existe antes de intentar insertarlo
                    // El controller decide la lógica, el model solo consulta
                    if ($usuarioModel->findByUsername($usuario)) {
                        throw new Exception("Ese nombre de usuario ya existe.");
                    }

                    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

                    // Hasheamos antes de pasar al model — el model recibe el hash, nunca el texto plano
                    // PASSWORD_DEFAULT usa bcrypt, el algoritmo recomendado actualmente
                    $password = password_hash($password, PASSWORD_DEFAULT);

                    $creado  = $usuarioModel->crearUsuario($usuario, $password);
                    $mensaje = $creado
                        ? "Usuario creado correctamente."
                        : "Error al crear el usuario.";

                } catch (Exception $e) {
                    // Si el model lanza una excepción también llega aquí
                    error_log($e->getMessage());
                    $mensaje = $e->getMessage();
                }

            } else {
                $mensaje = "El nombre de usuario no puede estar vacío.";
            }
        }

        // $mensaje está disponible en la vista
        require_once __DIR__ . '/../views/auth/registro.php';
    }


    // --- LOGOUT ---
    // Se ejecuta cuando: index.php?controller=auth&action=logout
    // Destruye la sesión y la cookie de "Recuérdame" si existe
    public function logout() {
        try {
            // Destruimos todos los datos de sesión del servidor
            session_destroy();

            // Eliminamos la cookie poniendo fecha en el pasado
            // El navegador la borra inmediatamente al recibirla
            if (isset($_COOKIE['usuario_id'])) {
                setcookie('usuario_id', '', time() - 3600);
            }

            header("Location: index.php?controller=auth&action=login");
            exit;

        } catch (Exception $e) {
            error_log($e->getMessage());
            header("Location: index.php?controller=auth&action=login");
            exit;
        }
    }
}