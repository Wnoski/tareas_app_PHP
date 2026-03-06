<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva tarea</title>
</head>
<body>

    <h1>Crear tarea</h1>

    <form action="index.php?controller=tarea&action=crear" method="POST" enctype="multipart/form-data">

        <label for="titulo">Título:</label>
        <input type="text" id="titulo" name="titulo" required>

        <br><br>

        <!-- accept="image/*" filtra en el explorador de archivos del SO -->
        <!-- No es una validación real — siempre validar en el servidor también -->
        <label for="imagen">Agregar imagen (opcional):</label>
        <input type="file" id="imagen" name="imagen" accept="image/*">

        <br><br>

        <button type="submit">Guardar tarea</button>

    </form>

    <div>
        <?php if ($mensaje): ?>
            <p><?= $mensaje ?></p>
        <?php endif; ?>

    </div>

    <br>
    <a href="index.php?controller=tarea&action=index">Volver a la lista</a>

</body>
</html>