<?php

// Clase responsable de gestionar la conexión a la base de datos
class Database {

    // Datos de configuración — en un proyecto real estos vendrían
    // de variables de entorno (.env), nunca hardcodeados así
    private $host    = 'localhost';   // Servidor de base de datos
    private $db      = 'tareas_app'; // Nombre de la base de datos
    private $user    = 'root';       // Usuario de MySQL
    private $clave   = '';           // Contraseña (vacía en local con XAMPP)
    private $conexion;               // Aquí se guardará el objeto PDO


    public function __construct() {
        try {

            // Opciones de configuración de PDO
            // Es mejor práctica pasarlas en la creación que usar setAttribute() por separado
            $options = [
                // Lanza excepciones en errores SQL en vez de fallar silenciosamente
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                // Todos los fetch() devuelven array asociativo por defecto
                // Sin esto vendría FETCH_BOTH: cada campo duplicado (por nombre Y por índice)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Usa prepared statements reales del motor MySQL, no simulados por PHP
                // Más eficiente y preciso con tipos de datos
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // DSN (Data Source Name) — cadena que describe dónde y cómo conectar
            // Formato obligatorio: "mysql:host=...;dbname=...;charset=..."
            // charset=utf8mb4 soporta tildes, ñ y emojis
            $this->conexion = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db . ";charset=utf8mb4",
                $this->user,  // Segundo argumento: usuario
                $this->clave, // Tercer argumento: contraseña
                $options      // Cuarto argumento: opciones de configuración
            );

        } catch (PDOException $e) {
            // Guardamos el error técnico real en el log del servidor (solo lo ves tú)
            error_log($e->getMessage());

            // Lanzamos una excepción genérica hacia arriba
            // Quien haga new Database() sabrá que falló, sin exponer detalles internos
            throw new Exception("Error al conectar la BD");
        }
    }


    // Método público para obtener la conexión PDO desde otros archivos
    // Uso: $pdo = (new Database())->getConnection();
    public function getConnection() {
        return $this->conexion;
    }
}

?>