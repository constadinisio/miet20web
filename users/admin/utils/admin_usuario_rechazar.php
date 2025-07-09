<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$usuario_id = $_POST['usuario_id'] ?? null;
if ($usuario_id) {
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stmt->close();
}
header("Location: ../usuarios.php");
exit;
