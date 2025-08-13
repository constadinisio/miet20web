<?php
require_once __DIR__ . '/../../../backend/includes/db.php';
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$curso_id    = (int)($_GET['curso_id']   ?? 0);
$materia_id  = (int)($_GET['materia_id'] ?? 0);
$fecha_base  =        $_GET['fecha']     ?? date('Y-m-d');
$profesor_id = (int)($_SESSION['usuario']['id'] ?? 0);

if ($curso_id <= 0 || $materia_id <= 0 || !$fecha_base) {
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

// Semana (lunes a viernes) según la fecha base
$ts_base = strtotime($fecha_base);
$inicio  = date('Y-m-d', strtotime('monday this week', $ts_base));
$fin     = date('Y-m-d', strtotime('friday this week', $ts_base));

// Encabezados y fechas
$columnas     = ['Nro', 'Nombre'];
$fechas_iso   = []; // YYYY-MM-DD
$fechas_ars   = []; // DD-MM-YYYY (lo que se muestra)
$editable     = [];

// construir las 5 fechas hábiles
for ($i = 0; $i < 5; $i++) {
    $f_iso = date('Y-m-d', strtotime("$inicio +$i days"));
    $fechas_iso[] = $f_iso;

    // Mostrar en Argentina: DD-MM-YYYY
    $fechas_ars[] = date('d-m-Y', strtotime($f_iso));
    $columnas[]   = $fechas_ars[$i];
}

// Días válidos del profesor (NÚMEROS 1..7)
$diasPermitidos = [];
$stmt = $conexion->prepare("
    SELECT DISTINCT dia_semana
    FROM horarios_materia
    WHERE profesor_id = ? AND curso_id = ? AND materia_id = ?
");
$stmt->bind_param("iii", $profesor_id, $curso_id, $materia_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $n = (int)$row['dia_semana']; // 1..7 (Lunes=1)
    if ($n >= 1 && $n <= 7) $diasPermitidos[$n] = true;
}
$stmt->close();

// Marcar días editables según horarios
foreach ($fechas_iso as $f_iso) {
    $nroDia = (int)date('N', strtotime($f_iso)); // 1=Lunes .. 7=Domingo
    $editable[] = empty($diasPermitidos) ? true : isset($diasPermitidos[$nroDia]);
}

// Alumnos del curso (ordenados como en la grilla)
$alumnos   = [];
$alumnoIds = [];
$stmt = $conexion->prepare("
    SELECT u.id, u.nombre, u.apellido
    FROM usuarios u
    JOIN alumno_curso ac ON u.id = ac.alumno_id
    WHERE ac.curso_id = ? AND ac.estado = 'activo' AND u.rol = 4
    ORDER BY u.apellido, u.nombre
");
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$res = $stmt->get_result();
$contador = 1;
while ($row = $res->fetch_assoc()) {
    $alumnos[] = [
        'id'     => (int)$row['id'],
        'nombre' => $row['apellido'] . ", " . $row['nombre'],
        'nro'    => $contador++
    ];
    $alumnoIds[] = (int)$row['id'];
}
$stmt->close();

// Asistencias de la semana (materia)
$asistencias = [];
$stmt = $conexion->prepare("
    SELECT alumno_id, fecha, estado
    FROM asistencia_materia
    WHERE curso_id = ? AND materia_id = ? AND fecha BETWEEN ? AND ?
");
$stmt->bind_param("iiss", $curso_id, $materia_id, $inicio, $fin);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $aid = (int)$row['alumno_id'];
    $f   = $row['fecha']; // YYYY-MM-DD en DB
    $asistencias[$aid][$f] = $row['estado'];
}
$stmt->close();

// Construcción de filas para la grilla
$filas = [];
foreach ($alumnos as $al) {
    $fila = [$al['nro'], $al['nombre']];
    foreach ($fechas_iso as $f_iso) {
        $fila[] = $asistencias[$al['id']][$f_iso] ?? 'NC';
    }
    $filas[] = $fila;
}

echo json_encode([
    // Cabecera visible (ARG): 'DD-MM-YYYY'
    'columnas'     => $columnas,

    // Fechas internas (por si las necesitás): 'YYYY-MM-DD'
    'fechas_iso'   => $fechas_iso,

    // También en ARG si querés usarlo en UI: 'DD-MM-YYYY'
    'fechas'       => $fechas_ars,

    // Para alinear qué columnas son editables (mismo orden que fechas/columnas.slice(2))
    'editable'     => $editable,

    // Filas (solo valores a renderizar)
    'filas'        => $filas,

    // Mapeo por fila → alumno_id (mismo orden que 'filas')
    'alumno_ids'   => $alumnoIds
], JSON_UNESCAPED_UNICODE);