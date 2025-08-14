<?php
require_once __DIR__ . '/../../../backend/includes/db.php';
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?? [];
$csrf = $in['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    echo json_encode(['ok' => false, 'mensaje' => 'CSRF inválido']);
    exit;
}

$curso_id     = (int)($in['curso_id'] ?? 0);
$materia_id   = (int)($in['materia_id'] ?? 0);
$encabezados  = $in['encabezados'] ?? [];   // Texto de headers (DD-MM-YYYY / YYYY-MM-DD / DD/MM)
$asistencias  = $in['asistencias'] ?? [];   // [{ alumno_id? , nro?, estados:[...] }]
$profesor_id  = (int)($_SESSION['usuario']['id'] ?? 0);
$fecha_baseIn = $in['fecha'] ?? null;

if ($curso_id <= 0 || $materia_id <= 0 || empty($encabezados) || empty($asistencias)) {
    echo json_encode(['ok'=>false,'mensaje'=>'Parámetros incompletos']); exit;
}

/* 1) Seguridad: validar asignación del profesor a curso+materia */
$stmt = $conexion->prepare("
  SELECT 1 FROM horarios_materia
  WHERE profesor_id = ? AND curso_id = ? AND materia_id = ?
  LIMIT 1
");
$stmt->bind_param('iii', $profesor_id, $curso_id, $materia_id);
$stmt->execute(); $stmt->store_result();
if ($stmt->num_rows === 0) {
  echo json_encode(['ok'=>false,'mensaje'=>'No tenés asignada esa materia en ese curso.']); exit;
}
$stmt->close();

/* 2) Parser de fechas de encabezado -> YYYY-MM-DD */
function parse_fecha_col($txt) {
    if (!is_string($txt)) return null;
    $t = trim($txt);

    // DD-MM-YYYY (Argentina)
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $t, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    // DD/MM (completa con año actual)
    if (preg_match('#^(\d{2})/(\d{2})$#', $t, $m)) {
        $anio = date('Y');
        return sprintf('%04d-%02d-%02d', $anio, $m[2], $m[1]);
    }
    // ISO YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) return $t;

    return null;
}

/* 3) Días válidos (1..7) de la materia según horarios (OBLIGATORIO tener al menos 1) */
$diasPermitidos = [];
$stmt = $conexion->prepare("
  SELECT DISTINCT dia_semana
  FROM horarios_materia
  WHERE profesor_id=? AND curso_id=? AND materia_id=?
");
$stmt->bind_param('iii', $profesor_id, $curso_id, $materia_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $n = (int)$row['dia_semana']; // 1..7
  if ($n >= 1 && $n <= 7) $diasPermitidos[$n] = true;
}
$stmt->close();

if (empty($diasPermitidos)) {
    echo json_encode(['ok'=>false,'mensaje'=>'No hay horarios cargados para este curso/materia.']); exit;
}

/* 4) Validar fecha base vs. horario (si vino en el payload) */
if ($fecha_baseIn) {
    $fecha_base_parsed = parse_fecha_col($fecha_baseIn);
    if ($fecha_base_parsed) {
        $dowBase = (int)date('N', strtotime($fecha_base_parsed)); // 1..7
        if (!isset($diasPermitidos[$dowBase])) {
            echo json_encode(['ok'=>false,'mensaje'=>'La fecha seleccionada no coincide con tus días de clase para este curso/materia.']); exit;
        }
    }
}

/* 5) Parsear todas las fechas recibidas en encabezados -> YYYY-MM-DD (solo columnas de fechas) */
$fechasTodas = [];
foreach ($encabezados as $i => $col) {
    $fechasTodas[$i] = parse_fecha_col($col); // puede quedar null si no es una fecha válida
}

/* 6) Filtrar SOLO las fechas guardables (válidas + día de clase) y RE-INDEXAR (0..k-1) */
$fechas_guardables = [];
$mapIdx = []; // mapea índice original de encabezado -> índice nuevo 0..k-1
foreach ($fechasTodas as $i => $f) {
    if (!$f) continue;
    $dow = (int)date('N', strtotime($f)); // 1..7
    if (isset($diasPermitidos[$dow])) {
        $mapIdx[$i] = count($fechas_guardables);
        $fechas_guardables[] = $f;  // re-indexado 0..k-1 para alinear con selects de columnas editables
    }
}
if (count($fechas_guardables) === 0) {
    echo json_encode(['ok'=>false,'mensaje'=>'No hay columnas editables/guardables para la materia en esas fechas (fuera de tu horario).']);
    exit;
}

/* 7) (Compat) map nro de lista -> alumno_id en el mismo orden de la grilla */
$alumnosOrden = [];
$q = $conexion->prepare("
  SELECT u.id
  FROM usuarios u
  JOIN alumno_curso ac ON ac.alumno_id = u.id
  WHERE ac.curso_id = ? AND ac.estado = 'activo' AND u.rol = 4
  ORDER BY u.apellido, u.nombre
");
$q->bind_param('i', $curso_id);
$q->execute();
$rq = $q->get_result();
while ($row = $rq->fetch_assoc()) { $alumnosOrden[] = (int)$row['id']; }
$q->close();

/* helper normalización de estado */
function norm_est($e){
  $e = mb_strtoupper(trim((string)$e));
  $map = ['P'=>'P','A'=>'A','T'=>'T','AJ'=>'AJ','AP'=>'AJ','NC'=>'NC'];
  return $map[$e] ?? 'NC';
}

try {
  $conexion->begin_transaction();

  // UNIQUE KEY sugerida: (alumno_id, curso_id, materia_id, fecha)
  $sql = "
    INSERT INTO asistencia_materia (alumno_id, curso_id, materia_id, fecha, estado, creado_por)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado=VALUES(estado), creado_por=VALUES(creado_por)
  ";
  $ins = $conexion->prepare($sql);
  if (!$ins) throw new Exception("Prepare failed: ".$conexion->error);

  $aplicados = 0; $ignorados = 0;

  foreach ($asistencias as $fila) {
    // Preferir alumno_id si viene en el payload; si no, usar nro (1-based) en el orden de la grilla
    $alumno_id = (int)($fila['alumno_id'] ?? 0);
    if ($alumno_id <= 0) {
      $nro = (int)($fila['nro'] ?? 0);
      if ($nro < 1 || $nro > count($alumnosOrden)) { $ignorados++; continue; }
      $alumno_id = $alumnosOrden[$nro - 1];
    }

    $estados = $fila['estados'] ?? [];

    // Recorremos estados por índice de columna original
    foreach ($estados as $idxOrig => $estadoSel) {
      // Debe existir mapeo a índice guardable
      if (!array_key_exists($idxOrig, $mapIdx)) continue;

      $idxGuard = $mapIdx[$idxOrig];       // 0..k-1
      $f = $fechas_guardables[$idxGuard];  // YYYY-MM-DD
      $estado = norm_est($estadoSel);
      if ($estado === 'NC') continue;      // no guardamos NC

      $ins->bind_param('iiissi', $alumno_id, $curso_id, $materia_id, $f, $estado, $profesor_id);
      $ins->execute();
      // affected_rows puede ser 0 si el valor es idéntico; consideramos como aplicado igualmente
      $aplicados += 1;
    }
  }

  $ins->close();
  $conexion->commit();

  echo json_encode([
    'ok' => true,
    'mensaje' => "✅ Asistencias guardadas. Registros aplicados: $aplicados. Ignorados: $ignorados."
  ]);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['ok'=>false,'mensaje'=>'❌ Error al guardar: '.$e->getMessage()]);
}