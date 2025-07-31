<?php
session_start();
require_once __DIR__ . '/../../../backend/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$csrf = $_POST['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || $csrf !== $_SESSION['csrf']) {
    echo json_encode(['error' => 'CSRF inválido']);
    exit;
}

file_put_contents(__DIR__ . '/debug.log', json_encode($_POST, JSON_PRETTY_PRINT));

$alumno_id = (int) ($_POST['alumno_id'] ?? 0);
$materia_id = (int) ($_POST['materia_id'] ?? 0);
$notas = $_POST['nota'] ?? [];

if (!$alumno_id || !is_array($notas)) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

foreach ($notas as $trabajo_id => $valor) {
    $trabajo_id = (int)$trabajo_id;
    $nota = is_numeric($valor) ? (float)$valor : null;

    if ($nota !== null && $nota >= 1 && $nota <= 10) {
        // Verificar si ya existe
        $sql_check = "SELECT id FROM notas WHERE alumno_id = ? AND trabajo_id = ?";
        $stmt = $conexion->prepare($sql_check);
        $stmt->bind_param("ii", $alumno_id, $trabajo_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Actualizar
            $stmt->close();
            $sql_update = "UPDATE notas SET nota = ?, fecha_carga = NOW() WHERE alumno_id = ? AND trabajo_id = ? AND materia_id = ?";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bind_param("diii", $nota, $alumno_id, $trabajo_id, $materia_id);
        } else {
            // Insertar
            $stmt->close();
            $sql_insert = "INSERT INTO notas (alumno_id, trabajo_id, materia_id, nota, fecha_carga) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conexion->prepare($sql_insert);
            $stmt->bind_param("iiid", $alumno_id, $trabajo_id, $materia_id, $nota);
        }
        $stmt->execute();
        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Error en DB: ' . $stmt->error]);
            exit;
        }
        $stmt->close();
    }
}

echo json_encode(['ok' => true]);
exit;
