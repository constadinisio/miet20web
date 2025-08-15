<?php
session_start();

if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    ((int)$_SESSION['usuario']['rol'] !== 3)
) {
    header("Location: /login.php?error=rol");
    exit;
}
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
    die('Token CSRF inválido.');
}

$profesor_id = $_SESSION['usuario']['id'];
$curso_id = (int)($_POST['curso_id'] ?? 0);
$materia_id = (int)($_POST['materia_id'] ?? 0);
$periodo = $_POST['periodo'] ?? '';
$alumnos = $_POST['alumno_id'] ?? [];
$notas = $_POST['nota'] ?? [];

if (!$curso_id || !$materia_id || !$periodo || empty($alumnos) || empty($notas)) {
    die('Faltan datos para cargar notas.');
}

// Validar que el profesor esté asignado a ese curso y materia (opcional pero recomendado)
$stmt = $conexion->prepare("SELECT id FROM profesor_curso_materia WHERE profesor_id = ? AND curso_id = ? AND materia_id = ?");
$stmt->bind_param("iii", $profesor_id, $curso_id, $materia_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die('No tiene permiso para cargar notas en este curso/materia.');
}
$stmt->close();

// Insertar notas una a una con promedio_actividades = 0
$stmt = $conexion->prepare("
    INSERT INTO notas_bimestrales (alumno_id, materia_id, periodo, nota, promedio_actividades, fecha_carga) VALUES (?, ?, ?, ?, 0, NOW())");

for ($i = 0; $i < count($alumnos); $i++) {
    $alumno_id = (int)$alumnos[$i];
    $nota_valor = (float)$notas[$i];
    if ($nota_valor < 1 || $nota_valor > 10) continue;
    $stmt->bind_param("iisd", $alumno_id, $materia_id, $periodo, $nota_valor);
    $stmt->execute();
}
$stmt->close();


header("Location: /users/profesor/calificaciones.php?curso_id=$curso_id&materia_id=$materia_id&periodo=" . urlencode($periodo) . "&ok=notas_cargadas");
exit;
?>