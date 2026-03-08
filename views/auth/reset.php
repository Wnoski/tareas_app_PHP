<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
</head>
<body>
    <header>
        <h1>Cambiar Contraseña</h1>
    </header>
    <?php if($mensaje): ?>
        <p><?= $mensaje ?></p>
        <?php endif; ?>


        <form action="index.php?controller=auth&action=resetPassword&token=<?= htmlspecialchars($token) ?>" method="post">
            <label for="password">Nueva Clase:</label>
            <input type="password" name="password">
            <input type="submit" value="Enviar">
        </form>
    
    <a href="index.php?controller=auth&action=login">Iniciar Sesion</a>

</body>
</html>