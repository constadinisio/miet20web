<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$id = $_POST['id'] ?? null;
$profesor_id = $_POST['profesor_id'] ?? null;

if (!$id || !$profesor_id) {
    header("Location: ../materias.php?error=faltan_campos");
    exit;
}

$sql = "DELETE FROM profesor_curso_materia WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: ../materias.php?ok=eliminada&profesor_id=$profesor_id");
exit;
