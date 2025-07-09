<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$curso_id = $_POST['curso_id'] ?? null;
$alumno_id = $_POST['alumno_id'] ?? null;

if ($curso_id && $alumno_id) {
    $sql = "DELETE FROM alumno_curso WHERE alumno_id = ? AND curso_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $alumno_id, $curso_id);
    $stmt->execute();
    $stmt->close();
}
header("Location: ../cursos.php?curso_id=$curso_id");
exit;
