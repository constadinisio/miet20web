<?php
session_start();

// Validar rol (por ejemplo, 1 = admin)
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}

require_once __DIR__ . '/../../../../backend/includes/db.php';

// Validar CSRF
if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
    die('Token CSRF inválido.');
}

// Validar id de alumno recibido
$alumno_id = $_POST['alumno_id'] ?? null;
if (!$alumno_id || !is_numeric($alumno_id)) {
    die('ID de alumno inválido.');
}

// Ejecutar eliminación
$stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$stmt->close();

// Redirigir a donde quieras (por ejemplo, a la lista de alumnos)
header("Location: /users/admin/alumnos.php?ok=eliminado");
exit;
?>
