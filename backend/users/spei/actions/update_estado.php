<?php
// Se inicia una sesión PHP
session_start();

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
require_once __DIR__ . '/../../../../backend/includes/db.php';

// Captura los datos del id y estado de una netbook
$id = trim($_POST['id'] ?? '');
$estado = trim($_POST['estado'] ?? '');

// Verifica si la solicitud fue realizada mediante el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lista de estados válidos permitidos para una netbook
    $estadosValidos = ['En uso', 'Dañada', 'Hurto', 'Obsoleta'];

    // Valida que el estado recibido esté dentro de los permitidos
    if (!in_array($estado, $estadosValidos)) {
        echo 'Estado inválido';
        exit;
    }

    // Prepara la consulta SQL para actualizar el estado de una netbook específica
    $stmt = $conexion->prepare("UPDATE netbooks SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);

    // Ejecuta la consulta y responde según el resultado
    if ($stmt->execute()) {
        echo 'OK';
    } else {
        echo 'Error al actualizar';
    }
    // Cierra la consulta preparada
    $stmt->close();
} else {
    // Si la solicitud no es POST, muestra un mensaje de error
    echo 'Método no permitido';
}
?>