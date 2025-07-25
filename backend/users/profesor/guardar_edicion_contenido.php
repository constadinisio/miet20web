<?php
session_start();
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 3) {
    header("Location: /login.php?error=rol");
    exit;
}

$id = $_POST['id'] ?? null;
$csrf = $_POST['csrf'] ?? '';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$contenido = trim($_POST['contenido'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

$sql = "UPDATE contenidos_libro
        SET fecha = ?, contenido = ?, observaciones = ?
        WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssi", $fecha, $contenido, $observaciones, $id);
$stmt->execute();
$stmt->close();

header("Location: /users/profesor/libro_temas.php?editado=1");
exit;