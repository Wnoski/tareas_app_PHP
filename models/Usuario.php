<?php
namespace App\Models;

// Database no tiene namespace, se referencia con \ para indicar espacio global
require_once __DIR__ . '/../config/database.php';

class Usuario {

    private $con;

    // Crea su propia conexión PDO al instanciarse
    public function __construct() {
        // \Database — la barra indica que Database está en el espacio global
        // sin ella PHP buscaría App\Models\Database y no la encontraría
        $db        = new \Database();
        $this->con = $db->getConnection();
    }

/** 
 * Busca un usuario por su email
 * 
 * @param string $email El elmail a buscar
 * @return array|false Los datos del usuario o false si no existe
 * @throws \Exception Si hay error en la BD
 */
    // Busca un usuario por su email y devuelve sus datos
    // Devuelve array con id, email y password hasheado
    // Devuelve false si no existe
    public function findByEmail($email) {
        try {
            // Traemos el password hasheado para verificarlo en el controller con password_verify()
            // Nunca comparamos el hash directamente en SQL — el hash es distinto cada vez
            $stmt = $this->con->prepare("SELECT id, email, `password`,verificado FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();

        } catch (\PDOException $e) {
            // \PDOException — misma razón, es una clase del espacio global de PHP
            // Logueamos el error técnico y lanzamos uno genérico hacia arriba
            // El controller lo capturará y decidirá qué mostrar al usuario
            error_log($e->getMessage());
            throw new \Exception("Error al buscar el usuario.");
        }
    }


    // Inserta un usuario nuevo en la BD
    // Recibe email y password ya hasheado — el controller hashea antes de llamar aquí
    // Devuelve el id del usuario recién creado — necesario para guardar el token después
    public function crearUsuario($email, $password) {
        try {
            // `password` entre backticks — es palabra reservada en MySQL
            $stmt = $this->con->prepare("INSERT INTO usuarios (email, `password`) VALUES (?, ?)");
            $stmt->execute([$email, $password]);
            return $this->con->lastInsertId();

        } catch (\PDOException $e) {
            error_log($e->getMessage());
            throw new \Exception("Error al crear el usuario.");
        }
    }


    // Guarda el token de verificación en la BD para el usuario recién creado
    // El token se genera en el controller con bin2hex(random_bytes(32))
    // Devuelve true si se guardó correctamente, false si no
    public function guardarToken($id, $token) {
        try {
            $stmt = $this->con->prepare("UPDATE usuarios SET token_verificacion = ? WHERE id = ?");
            $stmt->execute([$token, $id]);
            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            error_log($e->getMessage());
            throw new \Exception("Error al guardar el token de verificación.");
        }
    }


    // Verifica la cuenta del usuario buscando el token en la BD
    // Si lo encuentra actualiza verificado = 1 y elimina el token
    // Eliminar el token evita que el enlace pueda usarse dos veces
    // Devuelve true si se verificó correctamente, false si el token no existe
    public function verificarCuenta($token) {
        try {
            $stmt = $this->con->prepare(
                "UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE token_verificacion = ?"
            );
            $stmt->execute([$token]);
            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            error_log($e->getMessage());
            throw new \Exception("Error al verificar la cuenta.");
        }
    }

// Guarda el token de reset y su fecha de expiración en la BD
// $expira — datetime límite generado en el controller con strtotime('+1 hour')
// Devuelve true si se guardó correctamente, false si no
public function guardarTokenReset($email, $token, $expira) {
    try {
        $stmt = $this->con->prepare(
            "UPDATE usuarios SET token_reset = ?, token_reset_expira = ? WHERE email = ?"
        );
        $stmt->execute([$token, $expira, $email]);
        return $stmt->rowCount() > 0;

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        throw new \Exception("Error al guardar el token de reset.");
    }
}


// Busca el token en la BD verificando que no haya expirado
// NOW() devuelve la fecha y hora actual del servidor MySQL
// Si token_reset_expira > NOW() el token sigue vigente
// Devuelve array con datos del usuario o false si el token no existe o expiró
public function verificarTokenReset($token) {
    try {
        $stmt = $this->con->prepare(
            "SELECT id, email FROM usuarios WHERE token_reset = ? AND token_reset_expira > NOW()"
        );
        $stmt->execute([$token]);
        return $stmt->fetch();

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        throw new \Exception("Error al verificar el token de reset.");
    }
}


// Actualiza la contraseña del usuario y elimina el token de reset
// El token se elimina para que el enlace no pueda usarse dos veces
// Devuelve true si se actualizó correctamente, false si no
public function actualizarPassword($id, $password) {
    try {
        $stmt = $this->con->prepare(
            "UPDATE usuarios SET `password` = ?, token_reset = NULL, token_reset_expira = NULL WHERE id = ?"
        );
        $stmt->execute([$password, $id]);
        return $stmt->rowCount() > 0;

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        throw new \Exception("Error al actualizar la contraseña.");
    }
}




    }







