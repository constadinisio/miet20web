<?php
session_start();
require_once __DIR__ . '/../../../backend/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /users/profesor/calificaciones.php?error=metodo");
    exit;
}

$csrf = $_POST['csrf'] ?? '';
if (!isset($_SESSION['csrf']) || $csrf !== $_SESSION['csrf']) {
    die("CSRF inválido");
}

$materia_id = (int) ($_POST['materia_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo'] ?? '';

if (!$materia_id || !$nombre || !in_array($tipo, ['tp', 'actividad'])) {
    die("Datos incompletos");
}

$sql = "INSERT INTO trabajos (materia_id, nombre, tipo, fecha_creacion) VALUES (?, ?, ?, NOW())";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iss", $materia_id, $nombre, $tipo);
$stmt->execute();
$stmt->close();

header("Location: /users/profesor/trabajos.php?curso_id={$_GET['curso_id']}&materia_id=$materia_id");
exit;
