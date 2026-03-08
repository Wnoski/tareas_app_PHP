<?php



require_once __DIR__ . '/database.php';

// --- PASO 1: Comprobar si existe cookie de "Recuérdame" ---
// Si existe intentamos iniciar sesión automáticamente sin que el usuario tenga que loguearse
if (isset($_COOKIE['usuario_id'])) {

    try {
        $db  = new Database();
        $con = $db->getConnection();

        // Buscamos el usuario por el id guardado en la cookie
        // Solo traemos id y email, no necesitamos el password
        $stmt = $con->prepare("SELECT id, email FROM usuarios WHERE id = ?");
        $stmt->execute([$_COOKIE['usuario_id']]);

        $data = $stmt->fetch();

        if ($data) {
            // Usuario encontrado — iniciamos sesión automáticamente
            $_SESSION['id']       = $data['id'];
            $_SESSION['email'] = $data['email'];

           
        }
        // Si $data es false la cookie tenía un id que no existe en la BD
        // No hacemos nada, dejamos que el paso 2 redirija al login

    } catch (Exception $e) {
        error_log($e->getMessage());
        // No mostramos error al usuario, dejamos que el paso 2 maneje la situación
    }
}

// --- PASO 2: Verificar que hay sesión activa ---
// Llegamos aquí si no había cookie, o si la cookie falló
// Si tampoco hay sesión, redirigimos al login
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}