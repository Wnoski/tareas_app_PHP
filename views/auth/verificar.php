<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar cuenta</title>
</head>
<body>

    <h1>Verificación de cuenta</h1>

    <?php if ($mensaje): ?>
        <p><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <a href="index.php?controller=auth&action=login">Ir al login</a>

</body>
</html>