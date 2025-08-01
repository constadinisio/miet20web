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

$inicio = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
$fin = date('Y-m-d', strtotime('friday this week', strtotime($fecha)));

// Encabezados: Nro, Nombre, Lunes 01/08, Martes 02/08, etc.
$columnas = ['Nro', 'Nombre'];
$fechas = [];
for ($i = 0; $i < 5; $i++) {
    $f = date('Y-m-d', strtotime("$inicio +$i days"));
    $fechas[] = $f;
    setlocale(LC_TIME, 'es_ES.UTF-8');
    $columnas[] = ucfirst(strftime('%A %d/%m', strtotime($f)));
}

// Obtener alumnos activos del curso
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

// Obtener asistencias por materia en ese rango
$asistencias = [];
$stmt = $conexion->prepare("SELECT alumno_id, fecha, estado FROM asistencia_materia WHERE curso_id = ? AND materia_id = ? AND fecha BETWEEN ? AND ?");
$stmt->bind_param("iiss", $curso_id, $materia_id, $inicio, $fin);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $asistencias[$row['alumno_id']][$row['fecha']] = $row['estado'];
}

// Armar las filas
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
    'filas' => $filas
]);
