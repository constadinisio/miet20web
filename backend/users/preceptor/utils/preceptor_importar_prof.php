<?php
// Importa asistencias de PROFESOR (asistencia_materia) a PRECEPTOR (asistencia_general)
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

date_default_timezone_set('America/Argentina/Buenos_Aires');

set_exception_handler(function($e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'mensaje'=>'Excepción: '.$e->getMessage()]);
  exit;
});
set_error_handler(function($sev,$msg,$file,$line){
  http_response_code(500);
  echo json_encode(['ok'=>false,'mensaje'=>"PHP error: $msg @ $file:$line"]);
  exit;
});

session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'mensaje'=>'Acceso denegado']);
  exit;
}

// RUTA a db.php (desde /public/users/preceptor/)
require_once __DIR__ . '/../../../includes/db.php';

$in   = json_decode(file_get_contents('php://input'), true) ?? [];
$csrf = $in['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  echo json_encode(['ok'=>false,'mensaje'=>'CSRF inválido']); exit;
}

$curso_id     = (int)($in['curso_id'] ?? 0);
$materia_ids  = array_map('intval', (array)($in['materia_ids'] ?? []));
$fecha_in     = trim((string)($in['fecha'] ?? ''));
$dry_run      = (bool)($in['dry_run'] ?? false);
$preceptor_id = (int)$_SESSION['usuario']['id'];

if ($curso_id<=0 || empty($materia_ids) || $fecha_in==='') {
  echo json_encode(['ok'=>false,'mensaje'=>'Parámetros incompletos']); exit;
}

// --- Helpers ---
function norm_fecha_arg(string $s): string {
  $s = trim($s);
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;                    // YYYY-MM-DD
  if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $s, $m))                     // DD-MM-YYYY
    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $s, $m))                     // DD/MM/YYYY
    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  return $s; // fallback
}
function norm_estado($v): string {
  $map = ['P'=>'P','PRESENTE'=>'P','A'=>'A','AUSENTE'=>'A','T'=>'T','TARDE'=>'T','AJ'=>'AJ','AP'=>'AJ','JUST'=>'AJ','JUSTIFICADA'=>'AJ','NC'=>'NC'];
  $v = mb_strtoupper(trim((string)$v));
  return $map[$v] ?? 'NC';
}
// precedencia: A > AJ > T > P > NC
function pick_estado(string $a, string $b): string {
  $ord = ['A'=>5,'AJ'=>4,'T'=>3,'P'=>2,'NC'=>1];
  return ($ord[$a] ?? 0) >= ($ord[$b] ?? 0) ? $a : $b;
}

$fecha  = norm_fecha_arg($fecha_in);
$diaN   = (int)date('N', strtotime($fecha)); // 1..7
$mapNum = ['Lunes'=>1,'Martes'=>2,'Miércoles'=>3,'Miercoles'=>3,'Jueves'=>4,'Viernes'=>5,'Sábado'=>6,'Sabado'=>6,'Domingo'=>7];

// 1) Materias del CURSO que tienen clase EXACTAMENTE ese día (según horarios_materia) + es_contraturno
if (empty($materia_ids)) {
  echo json_encode(['ok'=>false,'mensaje'=>'Sin materias seleccionadas']); exit;
}
$place = implode(',', array_fill(0, count($materia_ids), '?'));
$types = str_repeat('i', count($materia_ids));

$sqlMeta = "
  SELECT DISTINCT m.id        AS materia_id,
         m.nombre     AS materia_nombre,
         CASE WHEN h.es_contraturno=1 THEN 1 ELSE 0 END AS es_contraturno,
         h.dia_semana
  FROM horarios_materia h
  JOIN materias m ON m.id = h.materia_id
  WHERE h.curso_id = ?
    AND h.materia_id IN ($place)
";
$stmt = $conexion->prepare($sqlMeta);
$params = array_merge([$curso_id], $materia_ids);
$stmt->bind_param('i'.$types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$materiasDia = []; // materia_id => ['nombre','es_contraturno']
$warnings = [];

while ($r = $res->fetch_assoc()) {
  $n = $mapNum[$r['dia_semana']] ?? null; // columna TEXT en español
  if ($n === null) {
    $warnings[] = "Día inválido en horarios para {$r['materia_nombre']} ({$r['dia_semana']}).";
    continue;
  }
  if ($n !== $diaN) {
    $warnings[] = "La materia {$r['materia_nombre']} no tiene clase el ".date('d/m/Y', strtotime($fecha))." ({$r['dia_semana']}).";
    continue;
  }
  $materiasDia[(int)$r['materia_id']] = [
    'nombre' => $r['materia_nombre'],
    'es_contraturno' => (int)$r['es_contraturno']
  ];
}
$stmt->close();

if (empty($materiasDia)) {
  echo json_encode(['ok'=>true,'simulacion'=>$dry_run,'fecha'=>$fecha,'warnings'=>$warnings,'mensaje'=>'No hay materias del curso que coincidan con ese día.']);
  exit;
}

// 2) Alumnos del curso
$alu = [];
$qAlu = $conexion->prepare("
  SELECT u.id
  FROM alumno_curso ac
  JOIN usuarios u ON u.id = ac.alumno_id
  WHERE ac.curso_id = ?
");
$qAlu->bind_param('i',$curso_id);
$qAlu->execute();
$ra = $qAlu->get_result();
while ($row = $ra->fetch_assoc()) $alu[] = (int)$row['id'];
$qAlu->close();

if (empty($alu)) {
  echo json_encode(['ok'=>false,'mensaje'=>'El curso no tiene alumnos asignados']); exit;
}
$aluPlace = implode(',', array_fill(0, count($alu), '?'));
$aluTypes = str_repeat('i', count($alu));

// 3) Leer asistencias de PROFESOR (asistencia_materia) para esas materias y esa fecha
$idsDia = array_keys($materiasDia);
$place2 = implode(',', array_fill(0, count($idsDia), '?'));
$types2 = str_repeat('i', count($idsDia));

$sqlA = "
  SELECT am.alumno_id, am.materia_id, am.estado
  FROM asistencia_materia am
  WHERE am.curso_id = ?
    AND am.fecha = ?
    AND am.materia_id IN ($place2)
    AND am.alumno_id IN ($aluPlace)
";
$stmtA = $conexion->prepare($sqlA);
$bindTypes = 'is' . $types2 . $aluTypes;
$bindParams = array_merge([$curso_id, $fecha], $idsDia, $alu);
$stmtA->bind_param($bindTypes, ...$bindParams);
$stmtA->execute();
$rA = $stmtA->get_result();

// Agregar por alumno y por turno (según la materia) con precedencia
$preview = [
  0=>['P'=>0,'A'=>0,'AJ'=>0,'T'=>0,'NC'=>0,'total'=>0], // turno
  1=>['P'=>0,'A'=>0,'AJ'=>0,'T'=>0,'NC'=>0,'total'=>0], // contraturno
];
$porTurnoEstados = [0=>[], 1=>[]];

while ($row = $rA->fetch_assoc()) {
  $aid = (int)$row['alumno_id'];
  $mid = (int)$row['materia_id'];
  if (!isset($materiasDia[$mid])) continue; // safety

  $ct = $materiasDia[$mid]['es_contraturno'] ? 1 : 0;
  $e  = norm_estado($row['estado']);
  $porTurnoEstados[$ct][$aid] = isset($porTurnoEstados[$ct][$aid]) ? pick_estado($porTurnoEstados[$ct][$aid], $e) : $e;
}
$stmtA->close();

// Completar con NC para quienes no aparecieron
foreach ([0,1] as $ct) {
  foreach ($alu as $aid) {
    $final = $porTurnoEstados[$ct][$aid] ?? 'NC';
    $porTurnoEstados[$ct][$aid] = $final;
    $preview[$ct][$final] = ($preview[$ct][$final] ?? 0) + 1;
    $preview[$ct]['total']++;
  }
}

// 4) Simulación
if ($dry_run) {
  echo json_encode([
    'ok'=>true,
    'simulacion'=>true,
    'fecha'=>$fecha,
    'warnings'=>$warnings,
    'turno'=>$preview[0],
    'contraturno'=>$preview[1]
  ]);
  exit;
}

// 5) Guardado real en asistencia_general (UPSERT)
try {
  $conexion->begin_transaction();

  $up = $conexion->prepare("
    INSERT INTO asistencia_general (alumno_id, curso_id, fecha, estado, creado_por, es_contraturno)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado=VALUES(estado), creado_por=VALUES(creado_por)
  ");
  if (!$up) throw new Exception("Prepare failed: ".$conexion->error);

  $insCount = 0;
  foreach ([0,1] as $ct) {
    foreach ($porTurnoEstados[$ct] as $aid=>$est) {
      $up->bind_param('iissii', $aid, $curso_id, $fecha, $est, $preceptor_id, $ct);
      $up->execute();
      $insCount += ($up->affected_rows >= 0 ? 1 : 0);
    }
  }
  $up->close();

  $conexion->commit();

  echo json_encode([
    'ok'=>true,
    'simulacion'=>false,
    'fecha'=>$fecha,
    'warnings'=>$warnings,
    'resumen'=>[
      'turno'=>$preview[0],
      'contraturno'=>$preview[1],
      'registros'=>$insCount
    ],
    'mensaje'=>"Importación realizada para ".date('d/m/Y', strtotime($fecha))."."
  ]);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['ok'=>false,'mensaje'=>'Error al guardar: '.$e->getMessage()]);
}