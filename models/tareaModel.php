<?php

require_once __DIR__ . '/../config/database.php';

class TareaModel {

    private $con;

    // Crea su propia conexión PDO al instanciarse
    public function __construct() {
        $db        = new Database();
        $this->con = $db->getConnection();
    }


    // Devuelve todas las tareas del usuario logueado
    // Devuelve array vacío si no hay tareas
    public function getTareas($usuario_id) {
        try {
            $stmt = $this->con->prepare("SELECT * FROM tareas WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("Error al cargar las tareas.");
        }
    }


    // Busca una tarea por id verificando que pertenece al usuario logueado
    // Evita IDOR — un usuario no puede ver tareas de otro aunque adivine el id
    // Devuelve array con los datos de la tarea o false si no existe
    public function getTareaById($id, $usuario_id) {
        try {
            $stmt = $this->con->prepare("SELECT titulo, completada, id FROM tareas WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuario_id]);
            return $stmt->fetch(); 

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("Error al obtener la tarea.");
        }
    }


    // Inserta una tarea nueva en la BD
    // $nombreArchivo es opcional — si no se sube imagen se guarda null
    // Devuelve true si se insertó correctamente, false si no
    public function crearTarea($titulo, $usuario_id, ?string $nombreArchivo = null) {
        try {
            $stmt = $this->con->prepare("INSERT INTO tareas (titulo, usuario_id, imagen) VALUES (?, ?, ?)");
            $stmt->execute([$titulo, $usuario_id, $nombreArchivo]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("Error al crear la tarea.");
        }
    }


    // Actualiza título y estado de una tarea
    // AND usuario_id en el WHERE evita que un usuario edite tareas de otro
    // $completada es opcional — por defecto 0 (no completada)
    // Devuelve true si se modificó algo, false si no hubo cambios
    public function editarTarea($titulo, $idTarea, $usuario_id, ?int $completada = 0) {
        try {
            $stmt = $this->con->prepare(
                "UPDATE tareas SET titulo = ?, completada = ? WHERE id = ? AND usuario_id = ?"
            );
            $stmt->execute([$titulo, $completada, $idTarea, $usuario_id]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("Error al editar la tarea.");
        }
    }

    public function eliminarTarea($idTarea, $usuario_id) {
        try {
            $stmt = $this->con->prepare("DELETE FROM tareas WHERE id = ? AND usuario_id = ?"
            );
            $stmt->execute([$idTarea, $usuario_id]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("Error al eliminar la tarea.");
        }
    }
}