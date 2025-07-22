<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}

require_once __DIR__ . '/../../../../backend/includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID inválido.");
}

// Eliminá también registros relacionados si hiciera falta (ej. inscripciones)
$sql = "DELETE FROM alumnos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: /users/admin/alumnos.php?ok=eliminado");
exit;