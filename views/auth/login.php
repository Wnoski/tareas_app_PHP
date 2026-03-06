<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

    <h1>Iniciar sesión</h1>

    <?php if ($error): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- El formulario apunta al router con controller y action -->
    <form action="index.php?controller=auth&action=login" method="POST">

        <label for="username">Usuario:</label>
        <input type="text" id="username" name="username" required>

        <br><br>

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>

        <br><br>

        <button type="submit">Entrar</button>
        <label>
    <input type="checkbox" name="recordar" value="1">
    Recuérdame
</label>

    </form>

    <a href="index.php?controller=auth&action=registro">¿No tienes cuenta? Regístrate</a>

</body>
</html>


