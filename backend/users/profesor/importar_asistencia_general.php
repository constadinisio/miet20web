<?php
require_once __DIR__ . '/../../../backend/includes/db.php';
session_start();

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$csrf = $data['csrf'] ?? '';
if (!hash_equals($_SESSION['csrf'], $csrf)) {
    echo json_encode(['ok' => false, 'mensaje' => 'CSRF inválido']);
    exit;
}

$curso_id = (int)($data['curso_id'] ?? 0);
$materia_id = (int)($data['materia_id'] ?? 0);
$fecha = $data['fecha'] ?? date('Y-m-d');
$profesor_id = $_SESSION['usuario']['id'];

if (!$curso_id || !$materia_id || !$fecha) {
    echo json_encode(['ok' => false, 'mensaje' => 'Parámetros incompletos']);
    exit;
}

try {
    // Traer asistencias del preceptor
    $stmt = $conexion->prepare("SELECT alumno_id, estado FROM asistencia_general WHERE curso_id = ? AND fecha = ?");
    $stmt->bind_param("is", $curso_id, $fecha);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'No hay asistencias generales para esta fecha.']);
        exit;
    }

    // Eliminar asistencias existentes para esa fecha
    $stmt = $conexion->prepare("DELETE FROM asistencia_materia WHERE fecha = ? AND curso_id = ? AND materia_id = ?");
    if (!$stmt) {
        throw new Exception("Error en prepare(): " . $conexion->error);
    }
    $stmt->bind_param("sii", $fecha, $curso_id, $materia_id);
    $stmt->execute();

    // Insertar asistencias importadas
    $insert = $conexion->prepare("INSERT INTO asistencia_materia (alumno_id, curso_id, materia_id, fecha, estado, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $insertados = 0;

    while ($row = $res->fetch_assoc()) {
        $insert->bind_param("iiissi", $row['alumno_id'], $curso_id, $materia_id, $fecha, $row['estado'], $profesor_id);
        $insert->execute();
        $insertados++;
    }

    echo json_encode(['ok' => true, 'mensaje' => "Se importaron $insertados asistencias del preceptor."]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al importar: ' . $e->getMessage()]);
}
