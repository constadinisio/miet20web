<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

// Traer asignaciones existentes
$asignaciones = [];
$sql = "SELECT pcm.id, u.nombre AS prof_nombre, u.apellido AS prof_apellido,
               c.anio, c.division, m.nombre AS materia
        FROM profesor_curso_materia pcm
        JOIN usuarios u ON pcm.profesor_id = u.id
        JOIN cursos c ON pcm.curso_id = c.id
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.estado = 'activo'
        ORDER BY u.apellido, c.anio, c.division, m.nombre";
$res = $conexion->query($sql);
while ($row = $res->fetch_assoc()) {
    $asignaciones[] = $row;
}

$asignacion_id = $_GET['asignacion_id'] ?? null;

// Horarios actuales
$horarios = [];
if ($asignacion_id) {
    $sql = "SELECT id, dia_semana, hora_inicio, hora_fin FROM horarios_materia WHERE profesor_id = (
                SELECT profesor_id FROM profesor_curso_materia WHERE id = ?
            ) AND curso_id = (
                SELECT curso_id FROM profesor_curso_materia WHERE id = ?
            ) AND materia_id = (
                SELECT materia_id FROM profesor_curso_materia WHERE id = ?
            ) ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $asignacion_id, $asignacion_id, $asignacion_id);
    $stmt->execute();
    $horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>