<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar tarea</title>
</head>
<body>

    <h1>Editar tarea</h1>

    <form action="index.php?controller=tarea&action=editar" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label for="titulo">Título:</label>
        <!-- value rellena el input con el título actual de la BD -->
        <!-- htmlspecialchars evita XSS si el título contiene caracteres especiales -->
        <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo) ?>" required>

        <br><br>

        <label for="completada">Completada:</label>
        <select id="completada" name="completada">
            <!-- El ternario añade 'selected' a la opción que coincide con el valor de la BD -->
            <option value="0" <?= $completada == 0 ? 'selected' : '' ?>>No</option>
            <option value="1" <?= $completada == 1 ? 'selected' : '' ?>>Sí</option>
        </select>

        <br><br>

        <button type="submit">Guardar cambios</button>

    </form>

    <?php if (isset($mensaje)): ?>
        <p><?= $mensaje ?></p>
    <?php endif; ?>

    <br>
    <a href="index.php?controller=tarea&action=index">Volver a la lista</a>

</body>
</html>