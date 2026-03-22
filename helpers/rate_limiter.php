<?php

require_once __DIR__ . '/../config/database.php';

function checkRateLimit(string $ip, string $accion, int $maxIntentos = 5, int $ventanaSegundos = 300, int $bloqueoSegundos = 900): void
{
    $pdo = (new Database())->getConnection(); // Como es un proyecto pequeño se puede ahcer 2 conexciones
    //a la BD pero esto habria que cambiarlo junto con los models para que sea 1 sola por cada peticion

    // Buscamos si ya existe un registro para esta IP y acción
    $stmt = $pdo->prepare("SELECT * FROM rate_limit WHERE ip = ? AND accion = ?");
    $stmt->execute([$ip, $accion]);
    $registro = $stmt->fetch();

    if ($registro) {

        // Si está bloqueado comprobamos si el bloqueo sigue vigente
        if ($registro['bloqueado_hasta'] && new DateTime() < new DateTime($registro['bloqueado_hasta'])) {
            http_response_code(429);
            echo json_encode(['error' => 'Demasiados intentos. Espera unos minutos.']);
            exit;
        }

        // Calculamos cuántos segundos han pasado desde el primer intento
        $diferencia = (new DateTime())->getTimestamp() - (new DateTime($registro['primer_intento']))->getTimestamp();

        if ($diferencia < $ventanaSegundos) {
            // Dentro de la ventana de tiempo
            if ($registro['intentos'] >= $maxIntentos) {
                // Superado el límite — calculamos hasta cuándo bloqueamos
                $bloqueadoHasta = (new DateTime())->modify("+{$bloqueoSegundos} seconds")->format('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE rate_limit SET bloqueado_hasta = ? WHERE ip = ? AND accion = ?");
                $stmt->execute([$bloqueadoHasta, $ip, $accion]);

                http_response_code(429);
                echo json_encode(['error' => 'Demasiados intentos. Espera unos minutos.']);
                exit;
            }

            // Aún no ha superado el límite — sumamos un intento
            $stmt = $pdo->prepare("UPDATE rate_limit SET intentos = intentos + 1 WHERE ip = ? AND accion = ?");
            $stmt->execute([$ip, $accion]);

        } else {
            // Fuera de la ventana — reseteamos el contador
            $stmt = $pdo->prepare("UPDATE rate_limit SET intentos = 1, primer_intento = NOW(), bloqueado_hasta = NULL WHERE ip = ? AND accion = ?");
            $stmt->execute([$ip, $accion]);
        }

    } else {
        // Primera vez que esta IP hace esta acción — creamos el registro
        $stmt = $pdo->prepare("INSERT INTO rate_limit (ip, accion, primer_intento) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $accion]);
    }
}


function checkRateLimiteWeb(string $ip, string $accion, int $maxIntentos = 5, int $ventanaSegundos = 300, int $bloqueoSegundos = 900): void
{
    // Creamos la conexión igual que en los modelos — mismo patrón
    $pdo = (new Database())->getConnection();

    // Buscamos si ya existe un registro para esta IP y acción concreta
    $stmt = $pdo->prepare("SELECT * FROM rate_limit WHERE ip = ? AND accion = ?");
    $stmt->execute([$ip, $accion]);
    $registro = $stmt->fetch();

    // match es como switch pero más conciso y estricto
    // Cada acción sabe a dónde redirigir si hay demasiados intentos
    // default apunta al login por si llega una acción no contemplada
    $redirect = match($accion) {
        'login'    => 'index.php?controller=auth&action=login',
        'registro' => 'index.php?controller=auth&action=registro',
        'olvido'   => 'index.php?controller=auth&action=olvidoPassword',
        'reset'    => 'index.php?controller=auth&action=resetPassword',
        default    => 'index.php?controller=auth&action=login'
    };

    if ($registro) {

        // Comprobamos si hay un bloqueo activo
        // $registro['bloqueado_hasta'] puede ser NULL si nunca se bloqueó
        // PHP permite comparar objetos DateTime directamente con < y >
        // porque la clase DateTime tiene el operador de comparación implementado internamente
        if ($registro['bloqueado_hasta'] && new DateTime() < new DateTime($registro['bloqueado_hasta'])) {
            $_SESSION['error'] = "Demasiados intentos. Espere unos minutos";
            header("Location: $redirect");
            exit;
        }

        // Calculamos segundos transcurridos desde el primer intento
        // Usamos getTimestamp() que devuelve un entero (segundos desde 1970)
        // Es más directo que diff() que devuelve un DateInterval y requiere cálculos adicionales
        $diferencia = (new DateTime())->getTimestamp() - (new DateTime($registro['primer_intento']))->getTimestamp();

        if ($diferencia < $ventanaSegundos) {
            // Estamos dentro de la ventana de tiempo

            if ($registro['intentos'] >= $maxIntentos) {
                // Superado el límite — calculamos hasta cuándo bloqueamos
                // modify() solo entiende inglés — "seconds" no "segundos"
                // format('Y-m-d H:i:s') convierte el objeto DateTime a string
                // que MySQL entiende para guardarlo en una columna DATETIME
                // Las llaves {} en "{$bloqueoSegundos}" delimitan la variable
                // dentro del string para evitar ambigüedad
                $bloqueadoHasta = (new DateTime())->modify("+{$bloqueoSegundos} seconds")->format("Y-m-d H:i:s");
                $stmt = $pdo->prepare("UPDATE rate_limit SET bloqueado_hasta = ? WHERE ip = ? AND accion = ?");
                $stmt->execute([$bloqueadoHasta, $ip, $accion]);

                $_SESSION['error'] = "Demasiados intentos. Espere unos minutos";
                header("Location: $redirect");
                exit;
            }

            // Aún no ha superado el límite — sumamos un intento
            $stmt = $pdo->prepare("UPDATE rate_limit SET intentos = intentos + 1 WHERE ip = ? AND accion = ?");
            $stmt->execute([$ip, $accion]);

        } else {
            // Fuera de la ventana de tiempo — reseteamos el contador
            // bloqueado_hasta = NULL porque si llegó aquí el bloqueo ya expiró
            $stmt = $pdo->prepare("UPDATE rate_limit SET intentos = 1, primer_intento = NOW(), bloqueado_hasta = NULL WHERE ip = ? AND accion = ?");
            $stmt->execute([$ip, $accion]);
        }

    } else {
        // Primera vez que esta IP hace esta acción — creamos el registro
        // intentos empieza en 1 por defecto según la definición de la tabla
        $stmt = $pdo->prepare("INSERT INTO rate_limit (ip, accion, primer_intento) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $accion]);
    }
}