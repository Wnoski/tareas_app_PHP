<?php

// Evita que tu web se cargue dentro de un iframe de otro sitio
// Protege contra clickjacking — DENY significa que nadie puede embeberte
header("X-Frame-Options: DENY");

// Evita que el navegador intente adivinar el tipo de contenido
// Sin esto el navegador podría ejecutar un archivo de texto como si fuera JS
header("X-Content-Type-Options: nosniff");

// Fuerza HTTPS durante un año (31536000 segundos)
// includeSubDomains aplica la regla también a subdominios
// El navegador guarda esta regla y redirige HTTP a HTTPS él mismo
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Define desde dónde se pueden cargar recursos (scripts, estilos, imágenes...)
// 'self' significa solo desde tu propio dominio, nada externo
// Si usas CDNs externos hay que añadirlos aquí explícitamente
header("Content-Security-Policy: default-src 'self'");