<?php

require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../helpers/rate_limiter.php';

// Importamos la clase Usuario del namespace App\Models definido en usuarioModel.php
// Al tener namespace ya no necesitamos require_once del model — Composer lo carga automáticamente
use App\Models\Usuario;

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

            // Rate limiting — máximo 5 intentos en 5 minutos por IP
            // Va después de verificar el método para no contar peticiones GET
            // Va antes de cualquier lógica para ahorrar recursos si ya está bloqueado
            $ip = $_SERVER["REMOTE_ADDR"];
            checkRateLimiteWeb($ip, 'login');

            try {
                // FILTER_VALIDATE_EMAIL valida Y devuelve el email limpio
                // FILTER_SANITIZE_SPECIAL_CHARS elimina caracteres peligrosos del password
                $email    = filter_input(INPUT_POST, 'email',    FILTER_VALIDATE_EMAIL);
                $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
                $recordar = filter_input(INPUT_POST, 'recordar', FILTER_VALIDATE_INT);

                $usuarioModel = new Usuario();
                $user         = $usuarioModel->findByEmail($email);

                if (!$user || !password_verify($password, $user['password'])) {
                    // Mensaje genérico — no revelamos si el email existe o no
                    // Evita que un atacante pueda enumerar emails registrados
                    $error = "Usuario o contraseña incorrectos.";

                } elseif (!$user['verificado']) {
                    // Credenciales correctas pero cuenta sin verificar
                    $error = "Debes verificar tu cuenta antes de iniciar sesión.";

                } else {
                    // Credenciales correctas y cuenta verificada — guardamos sesión
                    $_SESSION['id']    = $user['id'];
                    $_SESSION['email'] = $user['email'];

                    // Si marcó "Recuérdame" guardamos cookie por 7 días
                    // strtotime('+7 days') calcula el timestamp de expiración
                    if ($recordar == 1) {
                        setcookie('usuario_id', $user['id'], strtotime('+7 days'));
                    }

                    header("Location: index.php?controller=tarea&action=index");
                    exit;
                }

            } catch (Exception $e) {
                error_log($e->getMessage());
                $error = "Error inesperado, inténtalo de nuevo.";
            }
        }

        // Cargamos la vista — $error está disponible en ella
        // Si no hubo POST $error es null y la vista no muestra ningún error
        require_once __DIR__ . '/../views/auth/login.php';
    }

    // --- REGISTRO ---
    // Se ejecuta cuando: index.php?controller=auth&action=registro
    public function registro() {
        $mensaje = null;

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Rate limiting — evita registro masivo de cuentas
            $ip = $_SERVER["REMOTE_ADDR"];
            checkRateLimiteWeb($ip, 'registro');

            // FILTER_VALIDATE_EMAIL devuelve false si no es válido
            // el if de abajo lo gestiona
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

            if (!empty($email)) {

                try {
                    $usuarioModel = new Usuario();

                    // Verificamos si el email ya existe antes de intentar insertarlo
                    // El controller decide la lógica, el model solo consulta
                    if ($usuarioModel->findByEmail($email)) {
                        throw new Exception("Ese email ya está registrado.");
                    }

                    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

                    // Hasheamos antes de pasar al model — el model recibe el hash, nunca el texto plano
                    // PASSWORD_DEFAULT usa bcrypt, el algoritmo recomendado actualmente
                    $password = password_hash($password, PASSWORD_DEFAULT);

                    $creado = $usuarioModel->crearUsuario($email, $password);

                    if ($creado) {
                        // bin2hex(random_bytes(32)) genera un token seguro de 64 caracteres hexadecimales
                        // random_bytes usa el generador criptográfico del sistema operativo
                        $token = bin2hex(random_bytes(32));
                        $usuarioModel->guardarToken($creado, $token);
                        enviarEmailVerificacion($email, $token);
                        $mensaje = "Usuario creado correctamente, verifique su cuenta para poder usarla";

                    } else {
                        $mensaje = "Error al crear el usuario";
                    }

                } catch (Exception $e) {
                    // Capturamos tanto errores del model como los que lanzamos nosotros
                    // $e->getMessage() devuelve el mensaje de la excepción
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

    // --- VERIFICAR ---
    // Se ejecuta cuando el usuario hace clic en el enlace del email
    // index.php?controller=auth&action=verificar&token=abc123...
    public function verificar() {
        $mensaje = null;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {

            try {
                $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

                if (!$token) {
                    $mensaje = "Token inválido.";
                    require_once __DIR__ . '/../views/auth/verificar.php';
                    exit;
                }

                $usuarioModel = new Usuario();
                $verificado   = $usuarioModel->verificarCuenta($token);

                if ($verificado) {
                    // Redirigimos al login con parámetro msg para mostrar mensaje de éxito
                    header("Location: index.php?controller=auth&action=login&msg=verificado");
                    exit;
                } else {
                    throw new Exception("No se pudo verificar la cuenta.");
                }

            } catch (Exception $e) {
                error_log($e->getMessage());
                $mensaje = "Error inesperado, inténtalo de nuevo.";
            }
        }

        require_once __DIR__ . '/../views/auth/verificar.php';
    }

    // --- LOGOUT ---
    // Se ejecuta cuando: index.php?controller=auth&action=logout
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

    // --- OLVIDO PASSWORD ---
    // Se ejecuta cuando: index.php?controller=auth&action=olvidoPassword
    // GET — muestra el formulario para introducir el email
    // POST — genera el token y manda el email de reset
    public function olvidoPassword() {
        $mensaje = null;

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Rate limiting — evita spam de emails de reset
            $ip = $_SERVER["REMOTE_ADDR"];
            checkRateLimiteWeb($ip, 'olvido');

            try {
                // FILTER_VALIDATE_EMAIL valida que sea un email real
                $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

                if (empty($email)) {
                    $mensaje = "El email no puede estar vacío.";
                    require_once __DIR__ . '/../views/auth/olvido.php';
                    exit;
                }

                // date() formatea el timestamp para guardarlo en MySQL como DATETIME
                // strtotime('+1 hour') calcula el timestamp de expiración
                $token  = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $usuarioModel = new Usuario();

                if (!$usuarioModel->guardarTokenReset($email, $token, $expira)) {
                    // Si no actualizó ninguna fila el email no existe en la BD
                    // Mensaje genérico — no revelamos si el email está registrado
                    $mensaje = "Si el email está registrado recibirás un correo en breve.";
                    require_once __DIR__ . '/../views/auth/olvido.php';
                    exit;
                }

                enviarEmailReset($email, $token, "web");
                $mensaje = "Se envió un correo con las instrucciones para resetear tu contraseña.";

            } catch (Exception $e) {
                error_log($e->getMessage());
                $mensaje = "Ha ocurrido un error inesperado.";
            }
        }

        require_once __DIR__ . '/../views/auth/olvido.php';
    }

    // --- RESET PASSWORD ---
    // Se ejecuta cuando: index.php?controller=auth&action=resetPassword&token=abc123
    // GET — muestra el formulario para introducir la nueva contraseña
    // POST — verifica el token y actualiza la contraseña
    public function resetPassword() {
        $mensaje = null;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

            if (empty($token)) {
                $mensaje = "Asegúrese de tener todos los datos.";
                require_once __DIR__ . '/../views/auth/reset.php';
                exit;
            }
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Rate limiting — evita fuerza bruta de tokens de reset
            $ip = $_SERVER["REMOTE_ADDR"];
            checkRateLimiteWeb($ip, 'reset');

            try {
                $nuevaPassword = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
                // El token viene en la URL, no en el body — por eso INPUT_GET
                $token         = filter_input(INPUT_GET,  'token',    FILTER_SANITIZE_SPECIAL_CHARS);

                if (empty($token) || empty($nuevaPassword)) {
                    $mensaje = "Asegúrese de tener todos los datos.";
                    require_once __DIR__ . '/../views/auth/reset.php';
                    exit;
                }

                $usuarioModel = new Usuario();
                $userReset    = $usuarioModel->verificarTokenReset($token);

                if (!$userReset) {
                    // Token no existe o expiró — el model comprueba token_reset_expira > NOW()
                    $mensaje = "El enlace ha expirado o no es válido.";
                    require_once __DIR__ . '/../views/auth/reset.php';
                    exit;
                }

                // Hasheamos la nueva contraseña antes de guardarla
                // actualizarPassword() también elimina el token para que el enlace no pueda usarse dos veces
                $reseteada = $usuarioModel->actualizarPassword(
                    $userReset['id'],
                    password_hash($nuevaPassword, PASSWORD_DEFAULT)
                );

                // Operador ternario — asigna mensaje según si se reseteó o no
                $mensaje = $reseteada
                    ? "Contraseña cambiada correctamente."
                    : "No se pudo cambiar la contraseña.";

            } catch (Exception $e) {
                error_log($e->getMessage());
                $mensaje = "Ha ocurrido un error inesperado.";
            }
        }

        // Se ejecuta siempre — tanto GET (mostrar form) como POST (resultado)
        require_once __DIR__ . '/../views/auth/reset.php';
    }
}