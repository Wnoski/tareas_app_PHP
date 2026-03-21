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