<?php

// auth.php verifica que hay sesión activa
// Si no hay sesión redirige al login antes de que se ejecute nada más
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/tareaModel.php';

class TareaController {

    private $user_id;

    // user_id se obtiene de la sesión — auth.php garantiza que existe
    public function __construct() {
        $this->user_id = $_SESSION['id'];
    }


    // --- INDEX ---
    // Se ejecuta cuando: index.php?controller=tarea&action=index
    public function index() {
        $mensaje = null;

        try {
            $msg = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS) ?? null;
            $tareaModel = new TareaModel();
            $tareas     = $tareaModel->getTareas($this->user_id);

            if (!$tareas) {
                $mensaje = match($msg){
                    "deleted" => "Tarea eliminada correctamente",
                    "error" => "Error al intentar eliminar la tarea",
                    default => null
                };
                

                
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            $mensaje = "Error al cargar las tareas.";
        }

        require_once __DIR__ . '/../views/tareas/index.php';
    }


    // --- CREAR ---
    // Se ejecuta cuando: index.php?controller=tarea&action=crear
    // GET — muestra el formulario vacío
    // POST — procesa y crea la tarea
    public function crear() {
        $mensaje       = null;
        $nombreArchivo = null;

        try {

            // GET — solo mostramos el formulario
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                require_once __DIR__ . '/../views/tareas/crear.php';
                exit;
            }

            $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);

            if (empty($titulo)) {
                $mensaje = "El título no puede estar vacío.";
                require_once __DIR__ . '/../views/tareas/crear.php';
                exit;
            }

            // --- IMAGEN (opcional) ---
            $archivo = $_FILES['imagen'];

            if ($archivo['error'] == UPLOAD_ERR_OK) {

                // Detectamos tipo MIME real — más seguro que $_FILES['type']
                // que lo manda el navegador y puede ser falsificado
                $finfo       = new finfo(FILEINFO_MIME_TYPE);
                $tipoArchivo = $finfo->file($archivo['tmp_name']);

                $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($tipoArchivo, $tiposPermitidos)) {
                    $mensaje = "Tipo de archivo no permitido.";
                    require_once __DIR__ . '/../views/tareas/crear.php';
                    exit;
                }

                // 2MB máximo — 2 * 1024 * 1024 bytes
                if ($archivo['size'] > 2 * 1024 * 1024) {
                    $mensaje = "El archivo supera el tamaño máximo permitido (2MB).";
                    require_once __DIR__ . '/../views/tareas/crear.php';
                    exit;
                }

                // Nombre único — evita colisiones y Remote Code Execution
                $extension     = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $nombreArchivo = uniqid() . '.' . strtolower($extension);
                $ruta          = __DIR__ . '/../uploads/' . $nombreArchivo;

                if (!move_uploaded_file($archivo['tmp_name'], $ruta)) {
                    throw new Exception("Error al mover el archivo al servidor.");
                }
            }

            // Creamos la tarea — con o sin imagen ($nombreArchivo puede ser null)
            $tareaModel = new TareaModel();
            $nuevaTarea = $tareaModel->crearTarea($titulo, $this->user_id, $nombreArchivo);

            $mensaje = $nuevaTarea
                ? "Tarea creada exitosamente."
                : "No se pudo crear la tarea.";

        } catch (Exception $e) {
            error_log($e->getMessage());
            $mensaje = "Error al crear la tarea.";
        }

        require_once __DIR__ . '/../views/tareas/crear.php';
    }


    // --- EDITAR ---
    // Se ejecuta cuando: index.php?controller=tarea&action=editar&id=X
    // GET — carga la tarea y muestra el formulario relleno
    // POST — procesa y actualiza la tarea
    public function editar() {
        $mensaje = null;

        try {

            // GET — cargamos la tarea y mostramos el formulario relleno
            if ($_SERVER["REQUEST_METHOD"] == "GET") {

                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                 
                if (!$id || $id <= 0) {
                    throw new Exception("ID incorrecto o inexistente.");
                }

                $tareaModel = new TareaModel();
                $tarea      = $tareaModel->getTareaById($id, $this->user_id);

                if (!$tarea) {
                    throw new Exception("Tarea no encontrada.");
                }

                // Disponibles en la vista para rellenar el formulario
                $titulo     = $tarea['titulo'];
                $completada = $tarea['completada'];
                $id = $tarea['id'];
                require_once __DIR__ . '/../views/tareas/editar.php';
                exit;
            }

            // POST — procesamos el UPDATE
            if ($_SERVER["REQUEST_METHOD"] == "POST") {

                $titulo     = filter_input(INPUT_POST, 'titulo',     FILTER_SANITIZE_SPECIAL_CHARS);
                $completada = filter_input(INPUT_POST, 'completada', FILTER_VALIDATE_INT);
                $id    = filter_input(INPUT_POST,  'id',         FILTER_VALIDATE_INT);

                if (!$id || $id <= 0) {
                    throw new Exception("ID incorrecto o inexistente.");
                }

                if (empty($titulo)) {
                    $mensaje = "El título no puede estar vacío.";
                    require_once __DIR__ . '/../views/tareas/editar.php';
                    exit;
                }

                $tareaModel = new TareaModel();
                $editada    = $tareaModel->editarTarea($titulo, $id, $this->user_id, $completada);

                $mensaje = $editada
                    ? "Tarea editada correctamente."
                    : "No se pudo editar la tarea.";
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            $mensaje = "Error al intentar editar la tarea.";
        }

        require_once __DIR__ . '/../views/tareas/editar.php';
    }




    public function eliminar(){
        try{
        // POST — procesamos el DELETE
            if ($_SERVER["REQUEST_METHOD"] == "POST") {

                $id    = filter_input(INPUT_POST,  'id',         FILTER_VALIDATE_INT);

                if (!$id || $id <= 0) {
                    throw new Exception("ID incorrecto o inexistente.");
                }

                $tareaModel = new TareaModel();
                $existe = $tareaModel->getTareaById($id,$this->user_id);
                if(!$existe){
                    throw new Exception("ID incorrecto o inexistente");
                }
                $eliminada    = $tareaModel->eliminarTarea($id, $this->user_id);
                if($eliminada){
                    header("Location: index.php?controller=tarea&action=index&msg=deleted");
                    exit();
                }else{
                    header("Location:index.php?controller=tarea&action=index&msg=error");
                    exit();
                }
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            $mensaje = "Error al intentar eliminar la tarea.";
        }


       

    }
}