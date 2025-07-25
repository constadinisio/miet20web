<?php
session_start();
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 3) {
    header("Location: /login.php?error=rol");
    exit;
}

$id = $_POST['id'] ?? null;
$csrf = $_POST['csrf'] ?? '';

// Verificar si el contenido pertenece al profesor
$sql_check = "SELECT cl.id
              FROM contenidos_libro cl
              JOIN libros_temas lt ON cl.libro_id = lt.id
              WHERE cl.id = ? AND lt.profesor_id = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("ii", $id, $_SESSION['usuario']['id']);
$stmt_check->execute();
$result = $stmt_check->get_result();
if (!$result->fetch_assoc()) {
    die("No autorizado o contenido inexistente.");
}
$stmt_check->close();

$sql = "DELETE FROM contenidos_libro WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: /users/profesor/libro_temas.php?eliminado=1");
exit;
