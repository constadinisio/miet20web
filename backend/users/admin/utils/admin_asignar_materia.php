<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
require_once __DIR__ . '/../../../../backend/includes/db.php';
$profesor_id = $_POST['profesor_id'] ?? null;
$curso_id = $_POST['curso_id'] ?? null;
$materia_id = $_POST['materia_id'] ?? null;

if (!$profesor_id || !$curso_id || !$materia_id) {
    header("Location: /users/admin/materias.php?error=faltan_campos&profesor_id=$profesor_id");
    exit;
}

// Verificar si ya existe la asignación
$sql = "SELECT COUNT(*) FROM profesor_curso_materia WHERE profesor_id = ? AND curso_id = ? AND materia_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iii", $profesor_id, $curso_id, $materia_id);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

if ($existe > 0) {
    header("Location: /users/admin/materias.php?error=duplicado&profesor_id=$profesor_id");
    exit;
}

// Insertar asignación
$sql = "INSERT INTO profesor_curso_materia (profesor_id, curso_id, materia_id, estado, es_contraturno) VALUES (?, ?, ?, 'activo', 0)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iii", $profesor_id, $curso_id, $materia_id);
$stmt->execute();
$stmt->close();

header("Location: /users/admin/materias.php?ok=asignada&profesor_id=$profesor_id");
exit;
