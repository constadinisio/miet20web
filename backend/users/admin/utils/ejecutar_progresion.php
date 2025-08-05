<?php
require_once __DIR__ . '/../../../../backend/includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$curso_origen_id = (int)($data['curso_origen_id'] ?? 0);
$alumnos = $data['alumnos'] ?? [];
$csrf = $data['csrf'] ?? '';

session_start();
if (!isset($_SESSION['csrf']) || $_SESSION['csrf'] !== $csrf) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF inválido']);
    exit;
}

$errores = [];
foreach ($alumnos as $al) {
    $id = (int)$al['id'];
    $destino = $al['destino'];

    if ($destino === 'EGRESO') {
        // Marcar usuario como egresado
        $conexion->query("UPDATE usuarios SET estado_academico = 'EGRESADO' WHERE id = $id");
        // Desactivar relación alumno_curso
        $conexion->query("UPDATE alumno_curso SET estado = 'inactivo' WHERE alumno_id = $id AND curso_id = $curso_origen_id");
    } elseif (is_numeric($destino)) {
        // Promoción a otro curso
        $conexion->query("UPDATE alumno_curso SET curso_id = $destino, updated_at = NOW() WHERE alumno_id = $id AND estado = 'activo'");
    } else {
        $errores[] = "Destino inválido para ID $id";
    }
}

if (count($errores)) {
    echo json_encode(['error' => implode('; ', $errores)]);
} else {
    echo json_encode(['ok' => true, 'mensaje' => 'Progresión aplicada con éxito.']);
}