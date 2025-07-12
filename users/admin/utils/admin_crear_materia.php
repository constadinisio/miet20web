<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$nombre = trim($_POST['nombre'] ?? '');
$codigo = trim($_POST['codigo'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$es_contraturno = isset($_POST['es_contraturno']) ? 1 : 0;

if (!$nombre) {
    header("Location: ../materias.php?error=faltan_campos");
    exit;
}

// Evitar duplicados por nombre
$sql = "SELECT COUNT(*) FROM materias WHERE nombre = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

if ($existe > 0) {
    header("Location: ../materias.php?error=duplicado");
    exit;
}

// Insertar
$sql = "INSERT INTO materias (nombre, codigo, categoria, es_contraturno, estado) VALUES (?, ?, ?, ?, 'activo')";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssi", $nombre, $codigo, $categoria, $es_contraturno);
$stmt->execute();
$stmt->close();

header("Location: ../materias.php?ok=nueva");
exit;