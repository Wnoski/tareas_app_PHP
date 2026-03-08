
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
</head>
<body>

    <h1>Registro</h1>

    <?php if ($mensaje): ?>
        <p><?= $mensaje ?></p>
    <?php endif; ?>

    <form action="index.php?controller=auth&action=registro" method="POST">

        <label for="email">Usuario:</label>
        <input type="text" id="email" name="email" required>

        <br><br>

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>

        <br><br>

        <button type="submit">Registrarse</button>

    </form>

    <br>
    <a href="index.php?controller=auth&action=login">¿Ya tienes cuenta? Inicia sesión</a>

</body>
</html>