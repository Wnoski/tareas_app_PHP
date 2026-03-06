
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de tareas</title>
</head>
<body>

    <h1>Lista de tareas</h1>

    <!-- Enlace para ir al formulario de crear una tarea nueva -->
    <a href="index.php?controller=tarea&action=crear">+ Nueva tarea</a>

    <br><br>
    <div>
         <?php if ($msg): ?>
            <p><?= $mensaje ?></p>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($mensaje): ?>
            <p><?= $mensaje ?></p>
        <?php endif; ?>
    </div>

    <ul>
        <?php if ($tareas): ?>

            <?php foreach ($tareas as $t): ?>

                <?php
                    // Operador ternario — forma corta de if/else
                    // Si completada es 1 (true) muestra "Sí", si es 0 (false) muestra "No"
                    $full = $t['completada'] ? 'Sí' : 'No';
                    $img = $t['imagen'];
                ?>

                <li>
                    <?php
                        // htmlspecialchars() convierte caracteres especiales a entidades HTML
                        // Evita XSS: si alguien guardó "<script>alert()</script>" como título
                        // se mostraría como texto, no se ejecutaría como código
                    ?>
                    Título: <?= htmlspecialchars($t['titulo']) ?>

                    <ul>
                        <li>Completada: <?= $full ?></li>
                        <?php if($img):?>
                            <li><img src="uploads/<?=htmlspecialchars($img) ?>" alt="imagen_tarea<?= $t['id'] ?>"></li>
                        <?php endif; ?>
                    </ul>

                    <!-- El id viaja en la URL por GET, no necesita input hidden -->
                    <a href="index.php?controller=tarea&action=editar&id=<?= $t['id'] ?>">Editar tarea</a>
                    <!-- Formulario mínimo — el DELETE siempre por POST, nunca por enlace GET -->
            <form action="index.php?controller=tarea&action=eliminar" method="POST" style="display:inline">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" onclick="return confirm('¿Seguro que quieres eliminar esta tarea?')">
                    Eliminar tarea
                </button>
            </form>
                </li>

            <?php endforeach; ?>

        <?php else: ?>
            <?php // Si $data está vacío mostramos un mensaje en vez de una lista vacía ?>
            <li>No hay tareas todavía.</li>

        <?php endif; ?>
    </ul>


    <form action="index.php?controller=auth&action=logout" method="POST">
        <button type = "submit">Cerrar Session</button>
    </form>
            
</body>
</html>
