<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$curso_id = $_POST['curso_id'] ?? null;
$estado = $_POST['estado'] ?? null;

if (!$curso_id || !$estado || !in_array($estado, ['activo', 'inactivo'])) {
    header("Location: ../cursos.php?error=estado_invalido");
    exit;
}

// Alternar estado
$nuevo_estado = ($estado === 'activo') ? 'inactivo' : 'activo';

$sql = "UPDATE cursos SET estado = ? WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $nuevo_estado, $curso_id);
$stmt->execute();
$stmt->close();

header("Location: ../cursos.php?ok=estado_cambiado");
exit;