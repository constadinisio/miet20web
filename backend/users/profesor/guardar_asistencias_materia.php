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
$encabezados = $data['encabezados'] ?? [];
$asistencias = $data['asistencias'] ?? [];
$profesor_id = $_SESSION['usuario']['id'];

$fechas = [];
for ($i = 2; $i < count($encabezados); $i++) {
    // Convertir encabezado "Lunes 01/08" a Y-m-d
    $dia = explode(' ', $encabezados[$i])[1];
    $dia_num = explode('/', $dia);
    $anio = date('Y');
    $fechas[] = "$anio-{$dia_num[1]}-{$dia_num[0]}";
}

// Reasignar fechas si alguna se pasa de diciembre o no existe
foreach ($fechas as &$f) {
    $dt = DateTime::createFromFormat('Y-m-d', $f);
    if (!$dt) $f = date('Y-m-d');
}

try {
    $conexion->begin_transaction();

    // Eliminar registros anteriores
    foreach ($fechas as $f) {
        $stmt = $conexion->prepare("DELETE FROM asistencia_materia WHERE fecha = ? AND curso_id = ? AND materia_id = ?");
        $stmt->bind_param("sii", $f, $curso_id, $materia_id);
        $stmt->execute();
    }

    // Insertar nuevos registros
    foreach ($asistencias as $fila) {
        $nro = (int)$fila['nro'];
        $estados = $fila['estados'];

        // Buscar ID real
        $stmt = $conexion->prepare("SELECT u.id FROM usuarios u JOIN alumno_curso ac ON u.id = ac.alumno_id WHERE ac.curso_id = ? AND ac.estado = 'activo' AND u.rol = 4 ORDER BY u.apellido, u.nombre LIMIT ?,1");
        $offset = $nro - 1;
        $stmt->bind_param("ii", $curso_id, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if (!$row) continue;
        $alumno_id = $row['id'];

        foreach ($estados as $i => $estado) {
            if ($estado === 'NC') continue;
            $f = $fechas[$i] ?? null;
            if (!$f) continue;
            $stmt = $conexion->prepare("INSERT INTO asistencia_materia (alumno_id, curso_id, materia_id, fecha, estado, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissi", $alumno_id, $curso_id, $materia_id, $f, $estado, $profesor_id);
            $stmt->execute();
        }
    }

    $conexion->commit();
    echo json_encode(['ok' => true, 'mensaje' => 'Asistencias guardadas con éxito']);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
}
