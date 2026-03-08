<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olvido de contraseña</title>
</head>
<body>
    <header>
        <h1>Olvido su contraseña?</h1>
    </header>
    <?php if($mensaje): ?>
        <p><?= $mensaje ?></p>
        <?php endif; ?>


        <form action="index.php?controller=auth&action=olvidoPassword" method="post">
            <label for="email">Introduzca email:</label>
            <input type="email" name="email" required>
            <input type="submit" value="Enviar">
        </form>


</body>
</html>