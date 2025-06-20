<?php
// Se inicia una sesión PHP.
session_start();

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
include "../../includes/db.php";

/* Encargado de borrar una nota del pizarrón a partir de un id recibido por método POST. 
Responde en formato JSON con un mensaje de éxito o error. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');

    if (!empty($id) && is_numeric($id)) {
        $stmt = $conexion->prepare("DELETE FROM pizarron WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al borrar la nota.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID inválido o no recibido.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
