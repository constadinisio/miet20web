<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$id = $_POST['id'] ?? null;
$asignacion_id = $_POST['asignacion_id'] ?? null;

if (!$id || !$asignacion_id) {
    header("Location: ../horarios.php?error=faltan_campos");
    exit;
}

$sql = "DELETE FROM horarios_materia WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: ../horarios.php?ok=horario_eliminado&asignacion_id=$asignacion_id");
exit;
