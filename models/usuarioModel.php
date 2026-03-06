<?php

require_once __DIR__ . '/../config/database.php';

class Usuario {

    private $con;

    // Recibe la conexión PDO desde el controller
    public function __construct() {
         $db = new Database();
         $this->con = $db->getConnection();
    }


    // Busca un usuario por su username y devuelve sus datos
    // Devuelve array con id, username y password hasheado
    // Devuelve false si no existe
    public function findByUsername($username) {
        try {
            // Traemos el password hasheado para verificarlo en el controller con password_verify()
            // Nunca comparamos el hash directamente en SQL — el hash es distinto cada vez
            $stmt = $this->con->prepare("SELECT id, username, `password` FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch();

        } catch (PDOException $e) {
            // Logueamos el error técnico y lanzamos uno genérico hacia arriba
            // El controller lo capturará y decidirá qué mostrar al usuario
            error_log($e->getMessage());
            throw new Exception("Error al buscar el usuario.");
        }
    }


    // Inserta un usuario nuevo en la BD
    // Recibe username y password ya hasheado — el controller hashea antes de llamar aquí
    // Devuelve true si se insertó correctamente, false si no
    public function crearUsuario($username, $password) {
        try {
            // `password` entre backticks — es palabra reservada en MySQL
            $stmt = $this->con->prepare("INSERT INTO usuarios (username, `password`) VALUES (?, ?)");
            $stmt->execute([$username, $password]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("Error al crear el usuario.");
        }
    }
}