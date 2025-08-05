<?php
require_once __DIR__ . '/../../../backend/includes/db.php';
session_start();

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$curso_id = (int)($_GET['curso_id'] ?? 0);
$materia_id = (int)($_GET['materia_id'] ?? 0);
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$profesor_id = $_SESSION['usuario']['id'];

if (!$curso_id || !$materia_id || !$fecha) {
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

// Fechas de la semana
$inicio = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
$fin = date('Y-m-d', strtotime('friday this week', strtotime($fecha)));

// Encabezados y fechas
$columnas = ['Nro', 'Nombre'];
$fechas = [];
$editable = [];

// Mapeo manual de días de la semana en español
$diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

for ($i = 0; $i < 5; $i++) {
    $f = date('Y-m-d', strtotime("$inicio +$i days"));
    $fechas[] = $f;
    $nombreDia = $diasSemana[date('w', strtotime($f))]; // 0=Domingo, 6=Sábado
    $columnas[] = $nombreDia . ' ' . date('d/m', strtotime($f));
}

// Obtener días válidos del profesor
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

// Marcar días editables
foreach ($fechas as $f) {
    $nroDia = (int)date('N', strtotime($f)); // 1 = Lunes ... 7 = Domingo
    $editable[] = in_array($nroDia, $diasPermitidos);
}

// Alumnos
$alumnos = [];
$stmt = $conexion->prepare("SELECT u.id, u.nombre, u.apellido FROM usuarios u JOIN alumno_curso ac ON u.id = ac.alumno_id WHERE ac.curso_id = ? AND ac.estado = 'activo' AND u.rol = 4 ORDER BY u.apellido, u.nombre");
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$res = $stmt->get_result();
$contador = 1;
while ($row = $res->fetch_assoc()) {
    $alumnos[] = [
        'id' => $row['id'],
        'nombre' => $row['apellido'] . ", " . $row['nombre'],
        'nro' => $contador++
    ];
}

// Asistencias
$asistencias = [];
$stmt = $conexion->prepare("SELECT alumno_id, fecha, estado FROM asistencia_materia WHERE curso_id = ? AND materia_id = ? AND fecha BETWEEN ? AND ?");
$stmt->bind_param("iiss", $curso_id, $materia_id, $inicio, $fin);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $asistencias[$row['alumno_id']][$row['fecha']] = $row['estado'];
}

// Filas
$filas = [];
foreach ($alumnos as $al) {
    $fila = [$al['nro'], $al['nombre']];
    foreach ($fechas as $f) {
        $fila[] = $asistencias[$al['id']][$f] ?? 'NC';
    }
    $filas[] = $fila;
}

echo json_encode([
    'columnas' => $columnas,
    'fechas' => $fechas,
    'editable' => $editable, // <== NUEVO ARRAY PARA VALIDACIÓN EN JS
    'filas' => $filas
]);