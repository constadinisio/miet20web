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
$encabezados  = $in['encabezados'] ?? [];
$asistencias  = $in['asistencias'] ?? [];
$profesor_id  = (int)($_SESSION['usuario']['id'] ?? 0);

if ($curso_id <= 0 || $materia_id <= 0 || empty($encabezados) || empty($asistencias)) {
    echo json_encode(['ok'=>false,'mensaje'=>'Parámetros incompletos']); exit;
}

/* Validar asignación */
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

/* Parse fecha */
function parse_fecha_col($txt) {
    if (!is_string($txt)) return null;
    $t = trim($txt);
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $t, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('#^(\d{2})/(\d{2})$#', $t, $m)) {
        return sprintf('%04d-%02d-%02d', date('Y'), $m[2], $m[1]);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) return $t;
    return null;
}

/* Map días */
$mapDias = [
    'Lunes'=>1,'Martes'=>2,'Miércoles'=>3,'Miercoles'=>3,
    'Jueves'=>4,'Viernes'=>5,'Sábado'=>6,'Sabado'=>6,'Domingo'=>7
];
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
    $valor = trim($row['dia_semana']);
    $n = is_numeric($valor) ? (int)$valor : ($mapDias[$valor] ?? 0);
    if ($n >= 1 && $n <= 7) $diasPermitidos[$n] = true;
}
$stmt->close();

if (empty($diasPermitidos)) {
    echo json_encode(['ok'=>false,'mensaje'=>'No hay horarios cargados para este curso/materia.']); exit;
}

/* Fechas guardables */
$fechas_guardables = [];
foreach ($encabezados as $i => $col) {
    $f = parse_fecha_col($col);
    if ($f && isset($diasPermitidos[(int)date('N', strtotime($f))])) {
        $fechas_guardables[] = $f;
    }
}
if (empty($fechas_guardables)) {
    echo json_encode(['ok'=>false,'mensaje'=>'No hay columnas guardables para estas fechas.']); exit;
}

/* Alumnos ordenados */
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
while ($row = $rq->fetch_assoc()) $alumnosOrden[] = (int)$row['id'];
$q->close();

/* Normalizar estado */
function norm_est($e){
  $e = mb_strtoupper(trim((string)$e));
  $map = ['P'=>'P','A'=>'A','T'=>'T','AJ'=>'AJ','AP'=>'AJ','NC'=>'NC'];
  return $map[$e] ?? 'NC';
}

try {
  $conexion->begin_transaction();
  $sql = "
    INSERT INTO asistencia_materia (alumno_id, curso_id, materia_id, fecha, estado, creado_por)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado=VALUES(estado), creado_por=VALUES(creado_por)
  ";
  $ins = $conexion->prepare($sql);
  if (!$ins) throw new Exception("Prepare failed: ".$conexion->error);

  $aplicados = 0; $ignorados = 0;
  foreach ($asistencias as $fila) {
    $alumno_id = (int)($fila['alumno_id'] ?? 0);
    if ($alumno_id <= 0) {
      $nro = (int)($fila['nro'] ?? 0);
      if ($nro < 1 || $nro > count($alumnosOrden)) { $ignorados++; continue; }
      $alumno_id = $alumnosOrden[$nro - 1];
    }
    $estados = $fila['estados'] ?? [];
    if (!empty($estados)) {
        // Tomar el primer estado y asignarlo a la primera fecha guardable
        $estado = norm_est(reset($estados));
        if ($estado !== 'NC') {
            $ins->bind_param('iiissi', $alumno_id, $curso_id, $materia_id, $fechas_guardables[0], $estado, $profesor_id);
            $ins->execute();
            $aplicados++;
        }
    }
  }
  $ins->close();
  $conexion->commit();

  echo json_encode(['ok' => true, 'mensaje' => "✅ Asistencias guardadas. Registros aplicados: $aplicados. Ignorados: $ignorados."]);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['ok'=>false,'mensaje'=>'❌ Error al guardar: '.$e->getMessage()]);
}