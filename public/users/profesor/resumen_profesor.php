<?php
// users/profesor/resumen_profesor.php
session_start();
header('Content-Type: application/json');

// ====== DEBUG opcional ======
// Cambiá a true si querés ver logs en PHP error_log
const DEBUG = false;

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../../../backend/includes/db.php';

// --------- Parámetros ---------
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
// Normalizamos la fecha a YYYY-MM-DD; si viene vacía, hoy
$in_fecha = $_GET['fecha'] ?? date('Y-m-d');
$ts = strtotime($in_fecha);
$fecha = $ts ? date('Y-m-d', $ts) : date('Y-m-d');

if ($curso_id <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'curso_id inválido']);
    exit;
}

if (DEBUG) {
    error_log("[resumen_profesor] params: curso_id={$curso_id}, in_fecha={$in_fecha}, fecha_norm={$fecha}");
}

// --------- Query ---------
// Usamos DATE(fecha)=? por si la columna fuera DATETIME.
// Limpiamos estado con UPPER(TRIM(estado)) para contar bien ' p ', 'P', 'presente', etc. (solo cuenta 'P')
$sql = "
    SELECT es_contraturno,
           SUM(CASE WHEN UPPER(TRIM(estado))='P'  THEN 1 ELSE 0 END) AS presentes,
           SUM(CASE WHEN UPPER(TRIM(estado))='A'  THEN 1 ELSE 0 END) AS ausentes,
           SUM(CASE WHEN UPPER(TRIM(estado))='T'  THEN 1 ELSE 0 END) AS tarde,
           COUNT(*) AS total
    FROM asistencia_general
    WHERE curso_id = ?
      AND DATE(fecha) = ?
    GROUP BY es_contraturno
";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    if (DEBUG) error_log("[resumen_profesor] prepare error: " . $conexion->error);
    echo json_encode(['ok' => false, 'mensaje' => 'Error preparando SQL']);
    exit;
}
$stmt->bind_param('is', $curso_id, $fecha);
$stmt->execute();
$res = $stmt->get_result();

$R = [
  0 => ['presentes'=>0,'ausentes'=>0,'tarde'=>0,'total'=>0], // Turno
  1 => ['presentes'=>0,'ausentes'=>0,'tarde'=>0,'total'=>0], // Contraturno
];
$rowcount = 0;
while ($row = $res->fetch_assoc()) {
    $k = (int)$row['es_contraturno'];
    $R[$k]['presentes'] = (int)$row['presentes'];
    $R[$k]['ausentes']  = (int)$row['ausentes'];
    $R[$k]['tarde']     = (int)$row['tarde'];
    $R[$k]['total']     = (int)$row['total'];
    $rowcount++;
}
$stmt->close();

$tot = [
  'presentes' => $R[0]['presentes'] + $R[1]['presentes'],
  'ausentes'  => $R[0]['ausentes']  + $R[1]['ausentes'],
  'tarde'     => $R[0]['tarde']     + $R[1]['tarde'],
  'total'     => $R[0]['total']     + $R[1]['total'],
];

if (DEBUG) {
    error_log("[resumen_profesor] rows={$rowcount} - R0=" . json_encode($R[0]) . " R1=" . json_encode($R[1]));
}

// --------- Respuesta ---------
echo json_encode([
  'ok'               => true,
  'curso_id'         => $curso_id,
  'fecha'            => $fecha,
  'fecha_formateada' => date('d/m/Y', strtotime($fecha)),
  'turno'            => $R[0],
  'contraturno'      => $R[1],
  'totales'          => $tot,
]);