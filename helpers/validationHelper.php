<?php

// Namespace del helper — agrupa funciones utilitarias bajo App\Helpers
// Al ser una función suelta (no clase) se carga via "files" en composer.json
// PSR-4 solo carga clases automáticamente, las funciones necesitan carga explícita
namespace App\Helpers;

// Función de validación de email
// string $email — type hint, PHP rechaza cualquier cosa que no sea string
// :bool — siempre devuelve true o false, nunca null ni otro tipo
function validarEmail(string $email): bool
{
    // filter_var con FILTER_VALIDATE_EMAIL devuelve:
    // - el email como string si es válido
    // - false si no tiene formato de email válido
    // Comparamos !== false en vez de usar directamente el resultado
    // porque un email válido es un string truthy pero no es true estrictamente
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}