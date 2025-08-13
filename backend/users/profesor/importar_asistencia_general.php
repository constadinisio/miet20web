<?php
require_once __DIR__ . '/../../../backend/includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$csrf = $data['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    echo json_encode(['ok' => false, 'mensaje' => 'CSRF inválido']);
    exit;
}

$curso_id    = (int)($data['curso_id'] ?? 0);
$materia_id  = (int)($data['materia_id'] ?? 0);
$fecha       = $data['fecha'] ?? date('Y-m-d');
$profesor_id = (int)$_SESSION['usuario']['id'];

if ($curso_id <= 0 || $materia_id <= 0 || !$fecha) {
    echo json_encode(['ok' => false, 'mensaje' => 'Parámetros incompletos']);
    exit;
}

// --- Helpers ---
function normalizar_estado($valor) {
    $v = mb_strtoupper(trim((string)$valor));
    $map = [
        'PRESENTE' => 'P', 'P' => 'P',
        'AUSENTE'  => 'A', 'A' => 'A',
        'TARDE'    => 'T', 'T' => 'T',
        'NC'       => 'NC',
        'AP'       => 'AP',
        'JUSTIFICADA' => 'AP',
        'JUST'        => 'AP'
    ];
    return $map[$v] ?? 'NC';
}

// 1) Validar que el profesor tenga asignado ese curso+materia (seguridad básica)
$asignado = 0;
if ($stmt = $conexion->prepare("
    SELECT 1
    FROM horarios_materia
    WHERE profesor_id = ? AND curso_id = ? AND materia_id = ?
    LIMIT 1
")) {
    $stmt->bind_param('iii', $profesor_id, $curso_id, $materia_id);
    $stmt->execute();
    $stmt->store_result();
    $asignado = $stmt->num_rows > 0 ? 1 : 0;
    $stmt->close();
}
if (!$asignado) {
    echo json_encode(['ok' => false, 'mensaje' => 'No tenés asignada esa combinación curso + materia.']);
    exit;
}

// 2) Determinar si la materia es Contraturno
$es_contraturno = 0;
if ($stmt = $conexion->prepare("SELECT es_contraturno FROM materias WHERE id = ?")) {
    $stmt->bind_param('i', $materia_id);
    $stmt->execute();
    $stmt->bind_result($es_ct);
    if ($stmt->fetch()) { $es_contraturno = (int)$es_ct; }
    $stmt->close();
}
$turnoNecesario = $es_contraturno ? 'CONTRATURNO' : 'TURNO';

// 3) Saber si la tabla asistencia_general tiene columna 'turno'
$tiene_col_turno = 0;
$sqlCol = "
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'asistencia_general'
      AND COLUMN_NAME = 'turno'
";
$resCol = $conexion->query($sqlCol);
if ($resCol) {
    $tiene_col_turno = (int)$resCol->fetch_row()[0];
    $resCol->close();
}

try {
    $conexion->begin_transaction();

    // 4) Traer asistencias del preceptor (filtrando por turno si existe)
    if ($tiene_col_turno) {
        $stmt = $conexion->prepare("
            SELECT alumno_id, estado
            FROM asistencia_general
            WHERE curso_id = ? AND fecha = ? AND turno = ?
        ");
        $stmt->bind_param("iss", $curso_id, $fecha, $turnoNecesario);
    } else {
        // Compatibilidad: sin columna 'turno'
        $stmt = $conexion->prepare("
            SELECT alumno_id, estado
            FROM asistencia_general
            WHERE curso_id = ? AND fecha = ?
        ");
        $stmt->bind_param("is", $curso_id, $fecha);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $stmt->close();
        $conexion->rollback();
        echo json_encode([
            'ok' => false,
            'mensaje' => $tiene_col_turno
                ? "No hay asistencias generales para esa fecha en el $turnoNecesario."
                : "No hay asistencias generales para esta fecha."
        ]);
        exit;
    }

    // 5) Borrar lo existente para esa fecha (de esa materia) y reinsertar lo importado
    $stmtDel = $conexion->prepare("
        DELETE FROM asistencia_materia
        WHERE fecha = ? AND curso_id = ? AND materia_id = ?
    ");
    if (!$stmtDel) {
        throw new Exception("Error en prepare() DELETE: " . $conexion->error);
    }
    $stmtDel->bind_param("sii", $fecha, $curso_id, $materia_id);
    $stmtDel->execute();
    $stmtDel->close();

    $stmtIns = $conexion->prepare("
        INSERT INTO asistencia_materia
            (alumno_id, curso_id, materia_id, fecha, estado, creado_por)
        VALUES
            (?,         ?,        ?,          ?,     ?,      ?)
    ");
    if (!$stmtIns) {
        throw new Exception("Error en prepare() INSERT: " . $conexion->error);
    }

    $insertados = 0;
    while ($row = $res->fetch_assoc()) {
        $alumno_id = (int)$row['alumno_id'];
        $estado    = normalizar_estado($row['estado']);
        $stmtIns->bind_param("iiissi", $alumno_id, $curso_id, $materia_id, $fecha, $estado, $profesor_id);
        $stmtIns->execute();
        $insertados += (int)$stmtIns->affected_rows;
    }
    $stmtIns->close();
    $stmt->close();

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => $tiene_col_turno
            ? "Se importaron $insertados asistencias del preceptor ($turnoNecesario)."
            : "Se importaron $insertados asistencias del preceptor."
    ]);
} catch (Exception $e) {
    if ($conexion->errno) { $conexion->rollback(); }
    echo json_encode(['ok' => false, 'mensaje' => 'Error al importar: ' . $e->getMessage()]);
}