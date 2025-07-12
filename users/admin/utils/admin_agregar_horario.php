<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$asignacion_id = $_POST['asignacion_id'] ?? null;
$dia = $_POST['dia'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';

if (!$asignacion_id || !$dia || !$hora_inicio || !$hora_fin) {
    header("Location: ../horarios.php?error=faltan_campos&asignacion_id=$asignacion_id");
    exit;
}

// Obtener ids de profesor, curso y materia
$sql = "SELECT profesor_id, curso_id, materia_id FROM profesor_curso_materia WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $asignacion_id);
$stmt->execute();
$stmt->bind_result($profesor_id, $curso_id, $materia_id);
$stmt->fetch();
$stmt->close();

// Insertar horario
$sql = "INSERT INTO horarios_materia (profesor_id, curso_id, materia_id, dia_semana, hora_inicio, hora_fin) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iiisss", $profesor_id, $curso_id, $materia_id, $dia, $hora_inicio, $hora_fin);
$stmt->execute();
$stmt->close();

header("Location: ../horarios.php?ok=horario_agregado&asignacion_id=$asignacion_id");
exit;
