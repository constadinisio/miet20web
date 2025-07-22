<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
require_once __DIR__ . '/../../../../backend/includes/db.php';

$materia_id = $_POST['materia_id'] ?? null;
$estado = $_POST['estado'] ?? '';

if (!$materia_id || !in_array($estado, ['activo', 'inactivo'])) {
    header("Location: /users/admin/materias.php?error=estado_invalido");
    exit;
}

$nuevo_estado = $estado === 'activo' ? 'inactivo' : 'activo';

$sql = "UPDATE materias SET estado = ? WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $nuevo_estado, $materia_id);
$stmt->execute();
$stmt->close();

header("Location: /users/admin/materias.php?ok=estado_actualizado");
exit;