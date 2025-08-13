<?php
// public/users/preceptor/preceptor_materias_curso.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../../includes/db.php';

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'mensaje'=>'Acceso denegado']);
    exit;
}

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$fecha    = $_GET['fecha'] ?? date('Y-m-d');

if ($curso_id <= 0) {
    echo json_encode(['ok'=>false,'mensaje'=>'curso_id inválido']);
    exit;
}

// Día de la semana (1=Lunes..7=Domingo) usando AR
$tz = new DateTimeZone('America/Argentina/Buenos_Aires');
$dt = DateTime::createFromFormat('Y-m-d', $fecha, $tz);
if (!$dt) $dt = new DateTime('now', $tz);
$diaN = (int)$dt->format('N'); // 1..7

/* 
   Normalizamos dia_semana en SQL:
   - si es numérico => lo convertimos a int (1..7) aceptando también 0..6 con domingo=0
   - si es texto => lo llevamos a minúsculas y sin tildes y mapeamos a 1..7

   Nota: reemplazamos tildes con REPLACE anidado (compatible MySQL).
*/
$sql = "
SELECT DISTINCT
    m.id,
    m.nombre,
    COALESCE(m.es_contraturno, 0) AS es_contraturno
FROM horarios_materia h
JOIN materias m ON m.id = h.materia_id
WHERE h.curso_id = ?
  AND (
    /* numérico como '5' o 5 */
    (
      h.dia_semana REGEXP '^[0-9]+$' AND
      (
        CAST(h.dia_semana AS UNSIGNED) = ?    /* 1..5 */
        OR (CAST(h.dia_semana AS UNSIGNED) % 7) = (? % 5)  /* 0..6 permitiendo domingo=0 */
      )
    )
    OR
    /* texto: mapeo a 1..5 después de quitar tildes y pasar a minúsculas */
    (
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(h.dia_semana),
        'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u') IN
        ('lunes','martes','miercoles','jueves','viernes','sabado','domingo')
      AND CASE
            WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(h.dia_semana),
              'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u') = 'lunes' THEN 1
            WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(h.dia_semana),
              'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u') = 'martes' THEN 2
            WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(h.dia_semana),
              'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u') = 'miercoles' THEN 3
            WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(h.dia_semana),
              'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u') = 'jueves' THEN 4
            WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(h.dia_semana),
              'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u') = 'viernes' THEN 5
          END = ?
    )
  )
ORDER BY es_contraturno, m.nombre
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('iiii', $curso_id, $diaN, $diaN, $diaN);
$stmt->execute();
$res = $stmt->get_result();

$materias = [];
while ($r = $res->fetch_assoc()) {
    $materias[] = [
        'id' => (int)$r['id'],
        'nombre' => $r['nombre'],
        'es_contraturno' => (int)$r['es_contraturno'],
        'tiene_clase' => true
    ];
}
$stmt->close();

echo json_encode([
    'ok' => true,
    'curso_id' => $curso_id,
    'fecha' => $fecha,
    'diaN' => $diaN,
    'materias' => $materias
], JSON_UNESCAPED_UNICODE);