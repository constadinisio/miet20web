<?php
// Se inicia una sesión PHP
session_start();

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
require_once __DIR__ . '/../../../../backend/includes/db.php';

// Guarda un nuevo mensaje en la tabla pizarron cuando se recibe una solicitud POST con el contenido del mensaje y lo asocia al usuario actual de la sesión. 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (empty($mensaje)) {
        echo json_encode(['success' => false, 'error' => 'Mensaje vacío']);
        exit;
    }

    $autor = $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido'];

    $stmt = $conexion->prepare("INSERT INTO pizarron (autor, mensaje) VALUES (?, ?)");
    $stmt->bind_param("ss", $autor, $mensaje);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}