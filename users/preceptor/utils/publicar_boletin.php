<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: ../../../login.php?error=rol");
    exit;
}
require_once '../../../includes/db.php';

$boletin_id = $_GET['id'] ?? null;
if ($boletin_id) {
    $sql = "UPDATE boletin SET estado='publicado', fecha_emision=NOW() WHERE id=? AND estado='borrador'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $boletin_id);
    $stmt->execute();
    $stmt->close();
}
header("Location: preceptor_boletines.php");
exit;