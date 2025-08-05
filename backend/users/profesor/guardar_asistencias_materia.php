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
$profesor_id = $_SESSION['usuario']['id'] ?? null;

// 1. Convertir encabezados "Lunes 01/08" a fechas reales (Y-m-d)
$fechas = [];
foreach ($encabezados as $i => $col) {
    if ($i < 2 || !is_string($col)) continue;

    $partes = explode(' ', $col);
    if (count($partes) < 2) continue;

    $dia = $partes[1]; // 01/08
    $dia_num = explode('/', $dia);
    if (count($dia_num) < 2) continue;

    $anio = date('Y');
    $fechaStr = "$anio-{$dia_num[1]}-{$dia_num[0]}";
    $fechas[$i] = $fechaStr;
}

// Validar formato
foreach ($fechas as $i => $f) {
    $dt = DateTime::createFromFormat('Y-m-d', $f);
    if (!$dt) $fechas[$i] = null;
}

// 2. Consultar días válidos desde horarios_materia
$diasPermitidos = [];
$stmt = $conexion->prepare("SELECT DISTINCT dia_semana FROM horarios_materia WHERE profesor_id = ? AND curso_id = ? AND materia_id = ?");
$stmt->bind_param("iii", $profesor_id, $curso_id, $materia_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $mapa = [
        'Lunes' => 1,
        'Martes' => 2,
        'Miércoles' => 3,
        'Jueves' => 4,
        'Viernes' => 5,
        'Sábado' => 6,
        'Domingo' => 7
    ];
    $diasPermitidos[] = $mapa[$row['dia_semana']];
}

// 3. Filtrar fechas no permitidas
foreach ($fechas as $i => $f) {
    if (!$f) {
        unset($fechas[$i]);
        continue;
    }
    $nroDia = (int)date('N', strtotime($f));
    if (!in_array($nroDia, $diasPermitidos)) {
        unset($fechas[$i]); // ⚠️ eliminar fecha no editable
    }
}

try {
    $conexion->begin_transaction();

    // 4. Eliminar registros anteriores solo en fechas válidas
    foreach ($fechas as $f) {
        $stmt = $conexion->prepare("DELETE FROM asistencia_materia WHERE fecha = ? AND curso_id = ? AND materia_id = ?");
        $stmt->bind_param("sii", $f, $curso_id, $materia_id);
        $stmt->execute();
    }

    // 5. Insertar nuevas asistencias
    foreach ($asistencias as $fila) {
        $nro = (int)$fila['nro'];
        $estados = $fila['estados'];

        $stmt = $conexion->prepare("SELECT u.id FROM usuarios u 
            JOIN alumno_curso ac ON u.id = ac.alumno_id 
            WHERE ac.curso_id = ? AND ac.estado = 'activo' AND u.rol = 4 
            ORDER BY u.apellido, u.nombre LIMIT ?,1");
        $offset = $nro - 1;
        $stmt->bind_param("ii", $curso_id, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if (!$row) continue;
        $alumno_id = $row['id'];

        foreach ($estados as $i => $estado) {
            if ($estado === 'NC') continue;
            if (!isset($fechas[$i + 2])) continue; // i+2 por columnas Nro y Nombre
            $f = $fechas[$i + 2];

            $stmt = $conexion->prepare("INSERT INTO asistencia_materia (alumno_id, curso_id, materia_id, fecha, estado, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissi", $alumno_id, $curso_id, $materia_id, $f, $estado, $profesor_id);
            $stmt->execute();
        }
    }

    $conexion->commit();
    echo json_encode(['ok' => true, 'mensaje' => '✅ Asistencias guardadas con éxito']);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'mensaje' => '❌ Error al guardar: ' . $e->getMessage()]);
}